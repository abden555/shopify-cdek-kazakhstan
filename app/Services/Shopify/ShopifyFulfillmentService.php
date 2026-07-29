<?php

namespace App\Services\Shopify;

use App\Models\Shipment;
use App\Models\ShopSession;
use RuntimeException;

final readonly class ShopifyFulfillmentService
{
    public function __construct(private ShopifyGraphqlService $graphql) {}

    public function syncShipment(Shipment $shipment): void
    {
        $shipment->loadMissing('order', 'shop');

        if ($shipment->order === null || blank($shipment->order->external_id) || blank($shipment->tracking_number)) {
            return;
        }

        $session = ShopSession::query()
            ->where('shop_id', $shipment->shop_id)
            ->where('token_type', 'offline')
            ->latest('updated_at')
            ->first();

        if ($session === null) {
            throw new RuntimeException('No offline Shopify session is available for fulfillment synchronization.');
        }

        $metadata = $shipment->metadata ?? [];
        $tracking = [
            'company' => 'CDEK',
            'number' => $shipment->tracking_number,
            'url' => 'https://www.cdek.ru/ru/tracking/?order_id='.$shipment->tracking_number,
        ];

        if (filled($metadata['shopify_fulfillment_id'] ?? null)) {
            $this->updateTracking($session, $metadata['shopify_fulfillment_id'], $tracking);

            return;
        }

        $orderData = $this->graphql->query($session, self::FULFILLMENT_ORDERS_QUERY, ['id' => $shipment->order->external_id]);
        $fulfillmentOrders = $orderData['order']['fulfillmentOrders']['nodes'] ?? [];
        $lineItems = [];

        foreach ($fulfillmentOrders as $fulfillmentOrder) {
            $items = collect($fulfillmentOrder['lineItems']['nodes'] ?? [])
                ->filter(static fn (array $item): bool => (int) ($item['remainingQuantity'] ?? 0) > 0)
                ->map(static fn (array $item): array => ['id' => $item['id'], 'quantity' => (int) $item['remainingQuantity']])
                ->values()
                ->all();

            if ($items !== []) {
                $lineItems[] = ['fulfillmentOrderId' => $fulfillmentOrder['id'], 'fulfillmentOrderLineItems' => $items];
            }
        }

        if ($lineItems === []) {
            return;
        }

        $result = $this->graphql->query($session, self::CREATE_FULFILLMENT_MUTATION, [
            'fulfillment' => ['lineItemsByFulfillmentOrder' => $lineItems, 'trackingInfo' => $tracking, 'notifyCustomer' => false],
        ]);
        $payload = $result['fulfillmentCreate'] ?? [];
        $errors = $payload['userErrors'] ?? [];

        if ($errors !== []) {
            throw new RuntimeException((string) ($errors[0]['message'] ?? 'Shopify fulfillment could not be created.'));
        }

        $metadata['shopify_fulfillment_id'] = $payload['fulfillment']['id'] ?? null;
        $shipment->update(['metadata' => $metadata]);
    }

    /** @param array<string, string> $tracking */
    private function updateTracking(ShopSession $session, string $fulfillmentId, array $tracking): void
    {
        $result = $this->graphql->query($session, self::UPDATE_TRACKING_MUTATION, [
            'fulfillmentId' => $fulfillmentId,
            'trackingInfoInput' => $tracking,
            'notifyCustomer' => false,
        ]);
        $errors = $result['fulfillmentTrackingInfoUpdate']['userErrors'] ?? [];

        if ($errors !== []) {
            throw new RuntimeException((string) ($errors[0]['message'] ?? 'Shopify tracking could not be updated.'));
        }
    }

    private const FULFILLMENT_ORDERS_QUERY = <<<'GRAPHQL'
        query FulfillmentOrders($id: ID!) {
          order(id: $id) {
            fulfillmentOrders(first: 50) {
              nodes {
                id
                lineItems(first: 100) { nodes { id remainingQuantity } }
              }
            }
          }
        }
        GRAPHQL;

    private const CREATE_FULFILLMENT_MUTATION = <<<'GRAPHQL'
        mutation CreateFulfillment($fulfillment: FulfillmentInput!) {
          fulfillmentCreate(fulfillment: $fulfillment) {
            fulfillment { id }
            userErrors { field message }
          }
        }
        GRAPHQL;

    private const UPDATE_TRACKING_MUTATION = <<<'GRAPHQL'
        mutation UpdateFulfillmentTracking($fulfillmentId: ID!, $trackingInfoInput: FulfillmentTrackingInput!, $notifyCustomer: Boolean!) {
          fulfillmentTrackingInfoUpdate(fulfillmentId: $fulfillmentId, trackingInfoInput: $trackingInfoInput, notifyCustomer: $notifyCustomer) {
            fulfillment { id }
            userErrors { field message }
          }
        }
        GRAPHQL;
}

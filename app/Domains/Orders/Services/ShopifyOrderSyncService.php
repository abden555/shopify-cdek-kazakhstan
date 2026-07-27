<?php

namespace App\Domains\Orders\Services;

use App\Domains\Orders\Events\ShopifyOrdersSynced;
use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopSession;
use App\Services\Shopify\ShopifyGraphqlService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class ShopifyOrderSyncService
{
    public function __construct(private ShopifyGraphqlService $graphql) {}

    public function sync(Shop $shop, int $batchSize = 50): int
    {
        $session = ShopSession::query()->where('shop_id', $shop->id)->where('token_type', 'offline')->latest('updated_at')->first();

        if ($session === null) {
            throw new RuntimeException("No offline Shopify session exists for {$shop->domain}.");
        }

        $synced = 0;
        $cursor = null;

        do {
            $result = $this->graphql->query($session, self::ORDERS_QUERY, ['first' => min(max($batchSize, 1), 100), 'after' => $cursor]);
            $connection = $result['orders'] ?? ['nodes' => [], 'pageInfo' => ['hasNextPage' => false]];

            foreach ($connection['nodes'] ?? [] as $order) {
                $this->persist($shop, $order);
                $synced++;
            }

            $cursor = $connection['pageInfo']['endCursor'] ?? null;
        } while (($connection['pageInfo']['hasNextPage'] ?? false) && $cursor !== null);

        ShopifyOrdersSynced::dispatch($shop, $synced);

        return $synced;
    }

    /** @param array<string, mixed> $source */
    private function persist(Shop $shop, array $source): void
    {
        DB::transaction(function () use ($shop, $source): void {
            $order = Order::query()->updateOrCreate(
                ['shop_id' => $shop->id, 'external_id' => (string) $source['id']],
                [
                    'order_number' => $source['name'] ?? null,
                    'email' => $source['email'] ?? null,
                    'currency' => $source['currencyCode'] ?? 'KZT',
                    'financial_status' => $source['displayFinancialStatus'] ?? null,
                    'fulfillment_status' => $source['displayFulfillmentStatus'] ?? null,
                    'subtotal_amount' => $source['currentSubtotalPriceSet']['shopMoney']['amount'] ?? 0,
                    'discount_amount' => $source['currentTotalDiscountsSet']['shopMoney']['amount'] ?? 0,
                    'shipping_amount' => $source['currentShippingPriceSet']['shopMoney']['amount'] ?? 0,
                    'tax_amount' => $source['currentTotalTaxSet']['shopMoney']['amount'] ?? 0,
                    'total_amount' => $source['currentTotalPriceSet']['shopMoney']['amount'] ?? 0,
                    'billing_address' => $source['billingAddress'] ?? null,
                    'shipping_address' => $source['shippingAddress'] ?? null,
                    'metadata' => ['shopify' => ['requires_shipping' => $source['requiresShipping'] ?? false]],
                    'ordered_at' => isset($source['processedAt']) ? CarbonImmutable::parse($source['processedAt']) : null,
                ],
            );

            foreach ($source['lineItems']['nodes'] ?? [] as $item) {
                $order->items()->updateOrCreate(
                    ['external_id' => (string) $item['id']],
                    [
                        'sku' => $item['sku'] ?? null,
                        'title' => $item['title'] ?? 'Untitled item',
                        'variant_title' => $item['variantTitle'] ?? null,
                        'quantity' => $item['quantity'] ?? 0,
                        'fulfilled_quantity' => max(0, (int) ($item['quantity'] ?? 0) - (int) ($item['currentQuantity'] ?? 0)),
                        'unit_price' => $item['originalUnitPriceSet']['shopMoney']['amount'] ?? 0,
                        'discount_amount' => $item['discountedTotalSet']['shopMoney']['amount'] ?? 0,
                        'total_amount' => $item['discountedTotalSet']['shopMoney']['amount'] ?? 0,
                        // Product inventory data requires additional Shopify scopes; order sync needs only read_orders.
                        'weight_grams' => null,
                        'metadata' => ['shopify' => ['requires_shipping' => $item['requiresShipping'] ?? false]],
                    ],
                );
            }
        });
    }

    private const ORDERS_QUERY = <<<'GRAPHQL'
        query SyncOrders($first: Int!, $after: String) {
          orders(first: $first, after: $after, sortKey: PROCESSED_AT, reverse: true) {
            pageInfo { hasNextPage endCursor }
            nodes {
              id name email currencyCode processedAt requiresShipping displayFinancialStatus displayFulfillmentStatus
              currentSubtotalPriceSet { shopMoney { amount } }
              currentTotalDiscountsSet { shopMoney { amount } }
              currentShippingPriceSet { shopMoney { amount } }
              currentTotalTaxSet { shopMoney { amount } }
              currentTotalPriceSet { shopMoney { amount } }
              billingAddress { firstName lastName company address1 address2 city province zip countryCodeV2 phone }
              shippingAddress { firstName lastName company address1 address2 city province zip countryCodeV2 phone }
              lineItems(first: 100) {
                nodes {
                  id sku title variantTitle quantity currentQuantity requiresShipping
                  originalUnitPriceSet { shopMoney { amount } }
                  discountedTotalSet { shopMoney { amount } }
                }
              }
            }
          }
        }
        GRAPHQL;
}

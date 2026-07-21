<?php

namespace App\Services\Shopify;

use App\Models\FailedApiLog;
use App\Models\ShopSession;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyGraphqlService
{
    /** @param array<string, mixed> $variables
     *  @return array<string, mixed> */
    public function query(ShopSession $session, string $query, array $variables = []): array
    {
        $response = Http::acceptJson()->withHeaders([
            'X-Shopify-Access-Token' => $session->access_token,
        ])->post('https://'.$session->shop->domain.'/admin/api/'.config('shopify.api_version').'/graphql.json', [
            'query' => $query,
            'variables' => $variables,
        ]);

        if ($response->failed() || $response->json('errors')) {
            FailedApiLog::create([
                'shop_id' => $session->shop_id,
                'service' => 'shopify',
                'operation' => 'graphql',
                'request_method' => 'POST',
                'request_url' => 'https://'.$session->shop->domain.'/admin/api/'.config('shopify.api_version').'/graphql.json',
                'request_payload' => ['query' => $query, 'variables' => $variables],
                'response_code' => $response->status(),
                'response_body' => $response->body(),
                'error_message' => 'Shopify GraphQL request failed.',
            ]);

            throw new RuntimeException('Shopify GraphQL request failed.');
        }

        return $response->json('data', []);
    }

    /** @return array<string, mixed> */
    public function shop(ShopSession $session): array
    {
        $result = $this->query($session, 'query { shop { id name myshopifyDomain email currencyCode ianaTimezone } }');

        return $result['shop'] ?? [];
    }

    public function subscribeToUninstallWebhook(ShopSession $session): void
    {
        $this->query($session, <<<'GRAPHQL'
            mutation SubscribeToUninstall($topic: WebhookSubscriptionTopic!, $webhook: WebhookSubscriptionInput!) {
                webhookSubscriptionCreate(topic: $topic, webhookSubscription: $webhook) {
                    userErrors { field message }
                }
            }
            GRAPHQL, [
            'topic' => 'APP_UNINSTALLED',
            'webhook' => ['callbackUrl' => route('shopify.webhooks.uninstalled')],
        ]);
    }
}

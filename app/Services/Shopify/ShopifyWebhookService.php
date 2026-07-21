<?php

namespace App\Services\Shopify;

use App\Models\Shop;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use RuntimeException;

class ShopifyWebhookService
{
    public function __construct(private readonly ShopifyInstallationService $installation) {}

    public function handleUninstalled(Request $request): void
    {
        $body = $request->getContent();
        $provided = (string) $request->header('X-Shopify-Hmac-Sha256');
        $calculated = base64_encode(hash_hmac('sha256', $body, (string) config('shopify.api_secret'), true));

        if (blank($provided) || ! hash_equals($calculated, $provided)) {
            throw new RuntimeException('The Shopify webhook signature is invalid.');
        }

        $domain = (string) $request->header('X-Shopify-Shop-Domain');
        $payload = $request->json()->all();

        WebhookLog::create([
            'shop_id' => Shop::where('domain', $domain)->value('id'),
            'provider' => 'shopify',
            'event_id' => $request->header('X-Shopify-Webhook-Id'),
            'topic' => 'app/uninstalled',
            'headers' => $request->headers->all(),
            'payload' => $payload,
            'status' => 'processed',
            'attempts' => 1,
            'response_code' => 200,
            'processed_at' => now(),
        ]);

        $this->installation->uninstall($domain);
    }
}

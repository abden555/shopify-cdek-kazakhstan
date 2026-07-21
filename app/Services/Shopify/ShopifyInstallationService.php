<?php

namespace App\Services\Shopify;

use App\Models\Shop;
use App\Models\ShopSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShopifyInstallationService
{
    public function __construct(
        private readonly ShopifyAuthenticationService $authentication,
        private readonly ShopifyGraphqlService $graphql,
    ) {}

    /** @param array<string, mixed> $token
     *  @return array{shop: Shop, session: ShopSession} */
    public function completeAuthorization(string $domain, array $token, string $tokenType = 'offline', ?array $claims = null): array
    {
        return DB::transaction(function () use ($domain, $token, $tokenType, $claims): array {
            $shop = $this->activateShop($domain);
            $session = $this->storeSession($shop, $token, $tokenType, $claims);

            if ($tokenType === 'offline') {
                $details = $this->graphql->shop($session);
                $shop->update([
                    'name' => $details['name'] ?? $shop->name,
                    'external_id' => $details['id'] ?? $shop->external_id,
                    'currency' => $details['currencyCode'] ?? $shop->currency,
                    'timezone' => $details['ianaTimezone'] ?? $shop->timezone,
                    'metadata' => array_merge($shop->metadata ?? [], ['shopify' => $details]),
                ]);
                $this->graphql->subscribeToUninstallWebhook($session);
            }

            return compact('shop', 'session');
        });
    }

    public function handleEmbeddedSession(string $sessionToken, string $tokenType): ShopSession
    {
        $claims = $this->authentication->validateSessionToken($sessionToken);
        $token = $this->authentication->exchangeSessionToken($claims['shop'], $sessionToken, $tokenType);

        return $this->completeAuthorization($claims['shop'], $token, $tokenType, $claims)['session'];
    }

    public function uninstall(string $domain): void
    {
        $shop = Shop::where('domain', $this->authentication->normalizeShop($domain))->first();

        if (! $shop) {
            return;
        }

        DB::transaction(function () use ($shop): void {
            $shop->sessions()->delete();
            $shop->update(['is_active' => false, 'uninstalled_at' => now()]);
        });
    }

    /** @param array<string, mixed> $token
     *  @param array<string, mixed>|null $claims */
    private function storeSession(Shop $shop, array $token, string $tokenType, ?array $claims): ShopSession
    {
        $shopifyUserId = $token['associated_user']['id'] ?? ($claims['sub'] ?? null);
        $sessionKey = $tokenType === 'offline'
            ? $shop->id.':offline'
            : $shop->id.':online:'.($shopifyUserId ?: hash('sha256', $token['access_token']));

        return ShopSession::withTrashed()->updateOrCreate(
            ['session_key' => $sessionKey],
            [
                'shop_id' => $shop->id,
                'token_type' => $tokenType,
                'shopify_user_id' => $shopifyUserId ? (string) $shopifyUserId : null,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'scopes' => isset($token['scope']) ? explode(',', $token['scope']) : [],
                'associated_user' => $token['associated_user'] ?? null,
                'expires_at' => isset($token['expires_in']) ? now()->addSeconds((int) $token['expires_in']) : null,
                'refresh_token_expires_at' => isset($token['refresh_token_expires_in']) ? now()->addSeconds((int) $token['refresh_token_expires_in']) : null,
                'last_used_at' => now(),
                'deleted_at' => null,
            ],
        );
    }

    private function activateShop(string $domain): Shop
    {
        $shop = Shop::withTrashed()->firstOrNew(['domain' => $domain]);
        $owner = $shop->user_id ? null : $this->administrator();

        $shop->fill([
            'user_id' => $shop->user_id ?? $owner?->id,
            'name' => $shop->name ?? $domain,
            'platform' => 'shopify',
            'currency' => $shop->currency ?? 'KZT',
            'timezone' => $shop->timezone ?? 'Asia/Almaty',
            'is_active' => true,
            'installed_at' => now(),
            'uninstalled_at' => null,
        ]);

        if (! $shop->user_id) {
            throw new RuntimeException('An administrator account is required before a Shopify shop can be installed.');
        }

        $shop->exists && $shop->trashed() ? $shop->restore() : $shop->save();

        return $shop->refresh();
    }

    private function administrator(): ?User
    {
        return User::role('administrator')->orderBy('id')->first();
    }
}

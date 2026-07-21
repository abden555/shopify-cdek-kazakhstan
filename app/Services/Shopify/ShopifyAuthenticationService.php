<?php

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ShopifyAuthenticationService
{
    public function normalizeShop(string $shop): string
    {
        $shop = Str::lower(trim($shop));

        if (! preg_match('/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/', $shop)) {
            throw new RuntimeException('The Shopify shop domain is invalid.');
        }

        return $shop;
    }

    public function validateOAuthHmac(array $parameters): void
    {
        $providedHmac = $parameters['hmac'] ?? null;
        $timestamp = $parameters['timestamp'] ?? null;

        if (! is_string($providedHmac) || ! is_numeric($timestamp) || abs(now()->timestamp - (int) $timestamp) > 300) {
            throw new RuntimeException('The Shopify request is expired or incomplete.');
        }

        unset($parameters['hmac'], $parameters['signature']);
        ksort($parameters);
        $calculatedHmac = hash_hmac('sha256', http_build_query($parameters, '', '&', PHP_QUERY_RFC3986), $this->secret());

        if (! hash_equals($calculatedHmac, $providedHmac)) {
            throw new RuntimeException('The Shopify request signature is invalid.');
        }
    }

    public function authorizationUrl(string $shop, string $state, bool $online = false): string
    {
        $this->ensureConfigured();

        $parameters = [
            'client_id' => $this->key(),
            'scope' => implode(',', config('shopify.scopes')),
            'redirect_uri' => route('shopify.callback'),
            'state' => $state,
        ];

        if ($online) {
            $parameters['access_mode'] = 'per-user';
        }

        return 'https://'.$this->normalizeShop($shop).'/admin/oauth/authorize?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    /** @return array<string, mixed> */
    public function exchangeAuthorizationCode(string $shop, string $code, bool $expiringOffline = false): array
    {
        $this->ensureConfigured();

        $payload = ['client_id' => $this->key(), 'client_secret' => $this->secret(), 'code' => $code];

        if ($expiringOffline) {
            $payload['expiring'] = 1;
        }

        return $this->accessTokenRequest($shop, $payload);
    }

    /** @return array<string, mixed> */
    public function exchangeSessionToken(string $shop, string $sessionToken, string $tokenType): array
    {
        $this->ensureConfigured();

        $requestedTokenType = $tokenType === 'online'
            ? 'urn:shopify:params:oauth:token-type:online-access-token'
            : 'urn:shopify:params:oauth:token-type:offline-access-token';

        $payload = [
            'client_id' => $this->key(),
            'client_secret' => $this->secret(),
            'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
            'subject_token' => $sessionToken,
            'subject_token_type' => 'urn:ietf:params:oauth:token-type:id_token',
            'requested_token_type' => $requestedTokenType,
        ];

        if ($tokenType === 'offline' && config('shopify.offline_sessions_expire')) {
            $payload['expiring'] = 1;
        }

        return $this->accessTokenRequest($shop, $payload);
    }

    /** @return array<string, mixed> */
    public function validateSessionToken(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new RuntimeException('The Shopify session token is malformed.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;
        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (! is_array($header) || ! is_array($payload) || ($header['alg'] ?? null) !== 'HS256') {
            throw new RuntimeException('The Shopify session token header is invalid.');
        }

        $expectedSignature = hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $this->secret(), true);
        $signature = $this->base64UrlDecode($encodedSignature);

        if (! hash_equals($expectedSignature, $signature)
            || ($payload['aud'] ?? null) !== $this->key()
            || ! isset($payload['exp'])
            || (int) $payload['exp'] < now()->timestamp
            || ! isset($payload['dest'])) {
            throw new RuntimeException('The Shopify session token is invalid or expired.');
        }

        $host = parse_url((string) $payload['dest'], PHP_URL_HOST);
        $payload['shop'] = $this->normalizeShop((string) $host);

        return $payload;
    }

    private function ensureConfigured(): void
    {
        if (blank($this->key()) || blank($this->secret())) {
            throw new RuntimeException('Shopify API credentials are not configured.');
        }
    }

    private function key(): string
    {
        return (string) config('shopify.api_key');
    }

    private function secret(): string
    {
        return (string) config('shopify.api_secret');
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed> */
    private function accessTokenRequest(string $shop, array $payload): array
    {
        $response = Http::asForm()->acceptJson()->post('https://'.$this->normalizeShop($shop).'/admin/oauth/access_token', $payload);

        if ($response->failed() || ! is_string($response->json('access_token'))) {
            throw new RuntimeException('Shopify token exchange failed.');
        }

        return $response->json();
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;

        return base64_decode(strtr($value.str_repeat('=', $padding === 0 ? 0 : 4 - $padding), '-_', '+/'), true) ?: '';
    }
}

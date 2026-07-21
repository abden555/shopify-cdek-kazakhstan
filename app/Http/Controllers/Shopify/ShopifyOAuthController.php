<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyAuthenticationService;
use App\Services\Shopify\ShopifyInstallationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopifyOAuthController extends Controller
{
    public function __construct(
        private readonly ShopifyAuthenticationService $authentication,
        private readonly ShopifyInstallationService $installation,
    ) {}

    public function install(Request $request): RedirectResponse
    {
        try {
            $this->authentication->validateOAuthHmac($request->query());
            $shop = $this->authentication->normalizeShop((string) $request->query('shop'));
            $state = Str::random(64);
            $request->session()->put('shopify.oauth.state', $state);
            $request->session()->put('shopify.oauth.shop', $shop);
            $request->session()->put('shopify.oauth.host', $request->query('host'));

            return redirect()->away($this->authentication->authorizationUrl($shop, $state));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['shop' => $exception->getMessage()]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $this->authentication->validateOAuthHmac($request->query());

            if (! hash_equals((string) $request->session()->pull('shopify.oauth.state'), (string) $request->query('state'))
                || $request->session()->pull('shopify.oauth.shop') !== $request->query('shop')) {
                throw new \RuntimeException('The Shopify OAuth state is invalid.');
            }

            $shop = $this->authentication->normalizeShop((string) $request->query('shop'));
            $token = $this->authentication->exchangeAuthorizationCode($shop, (string) $request->query('code'), config('shopify.offline_sessions_expire'));
            $this->installation->completeAuthorization($shop, $token);
            $host = $request->session()->pull('shopify.oauth.host');

            return redirect()->route('shopify.app', array_filter(['shop' => $shop, 'host' => $host]));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['shop' => $exception->getMessage()]);
        }
    }

    public function app(Request $request): View
    {
        return view('shopify.app', ['shop' => $request->query('shop'), 'host' => $request->query('host')]);
    }

    public function exchangeSession(Request $request): JsonResponse
    {
        $sessionToken = (string) $request->attributes->get('shopify.session_token');
        $offline = $this->installation->handleEmbeddedSession($sessionToken, 'offline');
        $online = $this->installation->handleEmbeddedSession($sessionToken, 'online');

        return response()->json([
            'shop' => $offline->shop->only(['id', 'name', 'domain', 'is_active']),
            'online_session_expires_at' => $online->expires_at?->toIso8601String(),
        ]);
    }
}

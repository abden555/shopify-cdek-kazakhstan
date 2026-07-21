<?php

namespace App\Http\Middleware;

use App\Services\Shopify\ShopifyAuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifySessionToken
{
    public function __construct(private readonly ShopifyAuthenticationService $authentication) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (blank($token)) {
            return response()->json(['message' => 'A Shopify session token is required.'], 401);
        }

        try {
            $request->attributes->set('shopify.session_token', $token);
            $request->attributes->set('shopify.session', $this->authentication->validateSessionToken($token));
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        return $next($request);
    }
}

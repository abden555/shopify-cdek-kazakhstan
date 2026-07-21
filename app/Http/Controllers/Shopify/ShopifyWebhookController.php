<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShopifyWebhookController extends Controller
{
    public function uninstalled(Request $request, ShopifyWebhookService $webhooks): Response
    {
        $webhooks->handleUninstalled($request);

        return response()->noContent();
    }
}

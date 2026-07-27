<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Orders\Services\ShopifyOrderSyncService;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Throwable;

final class ShopifyOrderSyncController extends Controller
{
    public function __invoke(ShopifyOrderSyncService $sync): RedirectResponse
    {
        $count = 0;

        try {
            foreach (Shop::query()->where('is_active', true)->get() as $shop) {
                $count += $sync->sync($shop);
            }
        } catch (Throwable $exception) {
            report($exception);

            return to_route('admin.dashboard')->with('error', 'Shopify order synchronization failed. Review Failed API Logs and Shopify access scopes.');
        }

        return to_route('admin.dashboard')->with('status', "Synced {$count} Shopify order(s).");
    }
}

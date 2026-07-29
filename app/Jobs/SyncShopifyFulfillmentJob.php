<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Services\Shopify\ShopifyFulfillmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncShopifyFulfillmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $shipmentId) {}

    public function handle(ShopifyFulfillmentService $fulfillments): void
    {
        $shipment = Shipment::query()->find($this->shipmentId);

        if ($shipment !== null && $shipment->provider === 'cdek' && $shipment->status !== 'cancelled') {
            $fulfillments->syncShipment($shipment);
        }
    }
}

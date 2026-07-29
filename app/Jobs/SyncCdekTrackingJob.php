<?php

namespace App\Jobs;

use App\Domains\Carriers\Exceptions\CarrierRequestException;
use App\Domains\Carriers\Services\CdekTrackingSynchronizer;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncCdekTrackingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $shipmentId) {}

    public function handle(CdekTrackingSynchronizer $synchronizer): void
    {
        $shipment = Shipment::query()->find($this->shipmentId);

        if ($shipment === null || $shipment->provider !== 'cdek' || blank($shipment->external_id)) {
            return;
        }

        try {
            $synchronizer->sync($shipment);
        } catch (CarrierRequestException $exception) {
            Log::warning('CDEK tracking synchronization failed.', ['shipment_id' => $shipment->id, 'message' => $exception->getMessage()]);

            throw $exception;
        }
    }
}

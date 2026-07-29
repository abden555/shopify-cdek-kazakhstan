<?php

namespace App\Services\Cdek;

use App\Jobs\SyncCdekTrackingJob;
use App\Models\Shipment;
use App\Models\WebhookLog;
use Illuminate\Http\Request;

final class CdekWebhookService
{
    public function handle(Request $request): void
    {
        $payload = $request->json()->all();
        $shipmentUuid = $payload['uuid'] ?? $payload['order_uuid'] ?? null;
        $eventId = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $shipment = is_string($shipmentUuid)
            ? Shipment::query()->where('provider', 'cdek')->where('external_id', $shipmentUuid)->first()
            : null;

        $log = WebhookLog::query()->firstOrCreate(
            ['provider' => 'cdek', 'event_id' => $eventId],
            [
                'shop_id' => $shipment?->shop_id,
                'topic' => (string) ($payload['type'] ?? 'ORDER_STATUS'),
                'headers' => $request->headers->all(),
                'payload' => $payload,
                'status' => 'received',
                'attempts' => 1,
                'response_code' => 202,
            ],
        );

        if ($shipment === null) {
            $log->update(['status' => 'ignored', 'processed_at' => now()]);

            return;
        }

        SyncCdekTrackingJob::dispatch($shipment->id);
        $log->update(['status' => 'queued', 'processed_at' => now()]);
    }
}

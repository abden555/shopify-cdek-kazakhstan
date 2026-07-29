<?php

namespace App\Domains\Carriers\Services;

use App\Domains\Carriers\Actions\TrackShipmentAction;
use App\Models\Shipment;
use App\Models\Tracking;
use Carbon\CarbonImmutable;

final readonly class CdekTrackingSynchronizer
{
    public function __construct(private TrackShipmentAction $trackShipment) {}

    public function sync(Shipment $shipment): int
    {
        if (blank($shipment->external_id)) {
            return 0;
        }

        $tracking = $this->trackShipment->handle('cdek', $shipment->external_id);

        foreach ($tracking->events as $event) {
            $occurredAt = isset($event['date_time']) ? CarbonImmutable::parse($event['date_time']) : now();
            $externalId = sha1((string) ($event['code'] ?? 'unknown').'|'.$occurredAt->toIso8601String());

            Tracking::query()->updateOrCreate(
                ['shipment_id' => $shipment->id, 'external_id' => $externalId],
                [
                    'status' => (string) ($event['code'] ?? 'unknown'),
                    'description' => $event['name'] ?? null,
                    'location' => $event['city'] ?? null,
                    'metadata' => $event,
                    'occurred_at' => $occurredAt,
                ],
            );
        }

        $codes = collect($tracking->events)->pluck('code');
        $status = match (true) {
            $codes->contains('INVALID') => 'invalid',
            $codes->contains('DELIVERED') => 'delivered',
            $codes->contains('CANCELED'), $codes->contains('CANCELLED') => 'cancelled',
            $codes->isNotEmpty() => 'in_transit',
            default => $shipment->status,
        };
        $shipment->update(['tracking_number' => $tracking->trackingNumber, 'status' => $status]);

        return count($tracking->events);
    }
}

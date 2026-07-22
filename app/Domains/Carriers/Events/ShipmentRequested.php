<?php

namespace App\Domains\Carriers\Events;

use App\Domains\Carriers\DTOs\ShipmentData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ShipmentRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $carrierCode,
        public readonly ShipmentData $shipment,
    ) {}
}

<?php

namespace App\Domains\Carriers\DTOs;

final readonly class ShipmentResultData
{
    public function __construct(
        public string $carrierShipmentId,
        public ?string $trackingNumber = null,
    ) {}
}

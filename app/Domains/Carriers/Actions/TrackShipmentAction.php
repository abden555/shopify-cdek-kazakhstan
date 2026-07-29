<?php

namespace App\Domains\Carriers\Actions;

use App\Domains\Carriers\Contracts\CarrierRegistryInterface;
use App\Domains\Carriers\DTOs\TrackingData;

final readonly class TrackShipmentAction
{
    public function __construct(private CarrierRegistryInterface $carriers) {}

    public function handle(string $carrierCode, string $carrierShipmentId): TrackingData
    {
        return $this->carriers->for($carrierCode)->trackShipment($carrierShipmentId);
    }
}

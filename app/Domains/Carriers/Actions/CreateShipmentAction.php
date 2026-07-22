<?php

namespace App\Domains\Carriers\Actions;

use App\Domains\Carriers\Contracts\CarrierRegistryInterface;
use App\Domains\Carriers\DTOs\ShipmentData;
use App\Domains\Carriers\DTOs\ShipmentResultData;

final readonly class CreateShipmentAction
{
    public function __construct(private CarrierRegistryInterface $carriers) {}

    public function handle(string $carrierCode, ShipmentData $shipment): ShipmentResultData
    {
        return $this->carriers->for($carrierCode)->createShipment($shipment);
    }
}

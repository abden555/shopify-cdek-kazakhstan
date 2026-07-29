<?php

namespace App\Domains\Carriers\Actions;

use App\Domains\Carriers\Contracts\CarrierRegistryInterface;

final readonly class CancelShipmentAction
{
    public function __construct(private CarrierRegistryInterface $carriers) {}

    public function handle(string $carrierCode, string $carrierShipmentId): void
    {
        $this->carriers->for($carrierCode)->cancelShipment($carrierShipmentId);
    }
}

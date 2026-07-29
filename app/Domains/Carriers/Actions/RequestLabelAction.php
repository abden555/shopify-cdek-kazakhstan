<?php

namespace App\Domains\Carriers\Actions;

use App\Domains\Carriers\Contracts\CarrierRegistryInterface;

final readonly class RequestLabelAction
{
    public function __construct(private CarrierRegistryInterface $carriers) {}

    public function handle(string $carrierCode, string $carrierShipmentId): string
    {
        return $this->carriers->for($carrierCode)->requestLabel($carrierShipmentId);
    }
}

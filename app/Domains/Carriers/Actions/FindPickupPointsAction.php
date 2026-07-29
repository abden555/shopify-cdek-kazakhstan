<?php

namespace App\Domains\Carriers\Actions;

use App\Domains\Carriers\Contracts\CarrierRegistryInterface;
use App\Domains\Carriers\DTOs\PickupPointData;

final readonly class FindPickupPointsAction
{
    public function __construct(private CarrierRegistryInterface $carriers) {}

    /** @return array<int, PickupPointData> */
    public function handle(string $carrierCode, string $locationCode): array
    {
        return $this->carriers->for($carrierCode)->pickupPoints($locationCode);
    }
}

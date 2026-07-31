<?php

namespace App\Domains\Carriers\Actions;

use App\Domains\Carriers\Contracts\CarrierRegistryInterface;
use App\Domains\Carriers\DTOs\LocationData;

final readonly class FindLocationsAction
{
    public function __construct(private CarrierRegistryInterface $carriers) {}

    /** @return array<int, LocationData> */
    public function handle(string $carrierCode, string $city, string $countryCode): array
    {
        return $this->carriers->for($carrierCode)->locations($city, $countryCode);
    }
}

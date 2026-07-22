<?php

namespace App\Domains\Carriers\Services;

use App\Domains\Carriers\Contracts\CarrierInterface;
use App\Domains\Carriers\Contracts\CarrierRegistryInterface;
use App\Domains\Carriers\Exceptions\CarrierNotFoundException;
use Illuminate\Container\Attributes\Tag;

final class CarrierRegistry implements CarrierRegistryInterface
{
    /** @param iterable<CarrierInterface> $carriers */
    public function __construct(#[Tag('carriers')] private readonly iterable $carriers) {}

    public function for(string $carrierCode): CarrierInterface
    {
        foreach ($this->carriers as $carrier) {
            if ($carrier->code() === $carrierCode) {
                return $carrier;
            }
        }

        throw new CarrierNotFoundException("Carrier [{$carrierCode}] is not registered.");
    }
}

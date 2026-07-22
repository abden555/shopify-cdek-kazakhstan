<?php

namespace App\Domains\Carriers\Contracts;

interface CarrierRegistryInterface
{
    public function for(string $carrierCode): CarrierInterface;
}

<?php

namespace App\Domains\Carriers\DTOs;

final readonly class CarrierCredentialsData
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {}
}

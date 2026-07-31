<?php

namespace App\Domains\Carriers\DTOs;

final readonly class LocationData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $countryCode = null,
    ) {}
}

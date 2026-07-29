<?php

namespace App\Domains\Carriers\DTOs;

final readonly class PickupPointData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $address,
        public ?string $workTime = null,
    ) {}
}

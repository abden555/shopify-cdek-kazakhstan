<?php

namespace App\Domains\Carriers\DTOs;

final readonly class RateRequestData
{
    public function __construct(
        public array $origin,
        public array $destination,
        public array $parcels = [],
        public ?int $tariffCode = null,
    ) {}
}

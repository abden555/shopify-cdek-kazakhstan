<?php

namespace App\Domains\Carriers\DTOs;

use DateTimeImmutable;

final readonly class CarrierAuthenticationData
{
    public function __construct(
        public string $accessToken,
        public ?DateTimeImmutable $expiresAt = null,
    ) {}
}

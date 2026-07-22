<?php

namespace App\Domains\Carriers\DTOs;

final readonly class TrackingData
{
    /** @param array<int, array<string, mixed>> $events */
    public function __construct(
        public string $trackingNumber,
        public array $events = [],
    ) {}
}

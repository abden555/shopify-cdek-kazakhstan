<?php

namespace App\Domains\Carriers\DTOs;

final readonly class ShipmentData
{
    /** @param array<int, array<string, mixed>> $items */
    public function __construct(
        public string $reference,
        public array $sender,
        public array $recipient,
        public array $items = [],
    ) {}
}

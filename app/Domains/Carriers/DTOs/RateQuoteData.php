<?php

namespace App\Domains\Carriers\DTOs;

final readonly class RateQuoteData
{
    public function __construct(
        public string $currency,
        public int $amountMinor,
        public ?string $serviceCode = null,
        public ?string $serviceName = null,
        public ?int $deliveryDaysMin = null,
        public ?int $deliveryDaysMax = null,
    ) {}
}

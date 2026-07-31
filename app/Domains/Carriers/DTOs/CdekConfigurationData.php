<?php

namespace App\Domains\Carriers\DTOs;

final readonly class CdekConfigurationData
{
    public function __construct(
        public string $baseUrl,
        public ?string $clientId,
        public ?string $clientSecret,
        public ?string $senderCompany,
        public ?string $senderPhone,
        public ?string $senderCity,
        public ?string $senderLocationCode,
        public ?string $senderAddress,
        public ?string $senderPickupPointCode,
        public ?int $defaultTariffCode,
        public ?int $defaultWeightGrams,
        public ?int $defaultLengthCm,
        public ?int $defaultWidthCm,
        public ?int $defaultHeightCm,
    ) {}
}

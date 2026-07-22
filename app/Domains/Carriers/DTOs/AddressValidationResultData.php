<?php

namespace App\Domains\Carriers\DTOs;

final readonly class AddressValidationResultData
{
    /** @param array<int, string> $errors */
    public function __construct(
        public bool $isValid,
        public array $errors = [],
    ) {}
}

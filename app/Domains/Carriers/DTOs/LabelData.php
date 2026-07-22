<?php

namespace App\Domains\Carriers\DTOs;

final readonly class LabelData
{
    public function __construct(
        public string $content,
        public string $mimeType,
        public ?string $fileName = null,
    ) {}
}

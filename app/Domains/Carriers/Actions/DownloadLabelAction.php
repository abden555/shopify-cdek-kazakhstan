<?php

namespace App\Domains\Carriers\Actions;

use App\Domains\Carriers\Contracts\CarrierRegistryInterface;
use App\Domains\Carriers\DTOs\LabelData;

final readonly class DownloadLabelAction
{
    public function __construct(private CarrierRegistryInterface $carriers) {}

    public function handle(string $carrierCode, string $printRequestId): LabelData
    {
        return $this->carriers->for($carrierCode)->downloadLabel($printRequestId);
    }
}

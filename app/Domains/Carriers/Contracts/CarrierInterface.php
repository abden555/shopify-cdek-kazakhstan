<?php

namespace App\Domains\Carriers\Contracts;

use App\Domains\Carriers\DTOs\AddressValidationResultData;
use App\Domains\Carriers\DTOs\CarrierAuthenticationData;
use App\Domains\Carriers\DTOs\CarrierCredentialsData;
use App\Domains\Carriers\DTOs\LabelData;
use App\Domains\Carriers\DTOs\RateQuoteData;
use App\Domains\Carriers\DTOs\RateRequestData;
use App\Domains\Carriers\DTOs\ShipmentData;
use App\Domains\Carriers\DTOs\ShipmentResultData;
use App\Domains\Carriers\DTOs\TrackingData;

interface CarrierInterface
{
    public function code(): string;

    public function authenticate(CarrierCredentialsData $credentials): CarrierAuthenticationData;

    public function createShipment(ShipmentData $shipment): ShipmentResultData;

    public function cancelShipment(string $carrierShipmentId): void;

    public function downloadLabel(string $carrierShipmentId): LabelData;

    public function trackShipment(string $trackingNumber): TrackingData;

    public function calculateRate(RateRequestData $rateRequest): RateQuoteData;

    public function validateAddress(ShipmentData $shipment): AddressValidationResultData;
}

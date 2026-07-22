<?php

namespace App\Domains\Carriers\Services;

use App\Domains\Carriers\Contracts\CarrierInterface;
use App\Domains\Carriers\DTOs\AddressValidationResultData;
use App\Domains\Carriers\DTOs\CarrierAuthenticationData;
use App\Domains\Carriers\DTOs\CarrierCredentialsData;
use App\Domains\Carriers\DTOs\LabelData;
use App\Domains\Carriers\DTOs\RateQuoteData;
use App\Domains\Carriers\DTOs\RateRequestData;
use App\Domains\Carriers\DTOs\ShipmentData;
use App\Domains\Carriers\DTOs\ShipmentResultData;
use App\Domains\Carriers\DTOs\TrackingData;
use App\Domains\Carriers\Exceptions\CarrierOperationNotImplementedException;

final class CdekCarrier implements CarrierInterface
{
    public function code(): string
    {
        return 'cdek';
    }

    public function authenticate(CarrierCredentialsData $credentials): CarrierAuthenticationData
    {
        throw new CarrierOperationNotImplementedException('CDEK authentication is not implemented.');
    }

    public function createShipment(ShipmentData $shipment): ShipmentResultData
    {
        throw new CarrierOperationNotImplementedException('CDEK shipment creation is not implemented.');
    }

    public function cancelShipment(string $carrierShipmentId): void
    {
        throw new CarrierOperationNotImplementedException('CDEK shipment cancellation is not implemented.');
    }

    public function downloadLabel(string $carrierShipmentId): LabelData
    {
        throw new CarrierOperationNotImplementedException('CDEK label download is not implemented.');
    }

    public function trackShipment(string $trackingNumber): TrackingData
    {
        throw new CarrierOperationNotImplementedException('CDEK tracking is not implemented.');
    }

    public function calculateRate(RateRequestData $rateRequest): RateQuoteData
    {
        throw new CarrierOperationNotImplementedException('CDEK rate calculation is not implemented.');
    }

    public function validateAddress(ShipmentData $shipment): AddressValidationResultData
    {
        throw new CarrierOperationNotImplementedException('CDEK address validation is not implemented.');
    }
}

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
use App\Domains\Carriers\Exceptions\CarrierRequestException;
use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class CdekCarrier implements CarrierInterface
{
    public function __construct(private readonly FailedCarrierApiLogger $failedApiLogger) {}

    public function code(): string
    {
        return 'cdek';
    }

    public function authenticate(CarrierCredentialsData $credentials): CarrierAuthenticationData
    {
        $url = $this->url('/oauth/token');
        $payload = [
            'grant_type' => 'client_credentials',
            'client_id' => $credentials->clientId,
            'client_secret' => $credentials->clientSecret,
        ];

        try {
            $response = $this->client()->asForm()->post($url, $payload);

            if ($response->failed() || ! is_string($response->json('access_token'))) {
                $this->throwRequestException('authenticate', 'POST', $url, ['grant_type' => 'client_credentials'], $response);
            }

            $expiresIn = max(60, (int) $response->json('expires_in', 3600));

            return new CarrierAuthenticationData(
                accessToken: (string) $response->json('access_token'),
                expiresAt: new DateTimeImmutable("+{$expiresIn} seconds"),
            );
        } catch (ConnectionException $exception) {
            $this->failedApiLogger->log('authenticate', 'POST', $url, ['grant_type' => 'client_credentials'], $exception);

            throw new CarrierRequestException('CDEK authentication connection failed.', previous: $exception);
        }
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
        if ($rateRequest->tariffCode === null) {
            throw new CarrierRequestException('A CDEK tariff code is required to calculate a rate.');
        }

        $url = $this->url('/calculator/tariff');
        $payload = [
            'type' => 1,
            'tariff_code' => $rateRequest->tariffCode,
            'from_location' => $rateRequest->origin,
            'to_location' => $rateRequest->destination,
            'packages' => $rateRequest->parcels,
        ];

        try {
            $response = $this->authenticatedClient()->post($url, $payload);

            if ($response->failed() || ! is_array($response->json('entity'))) {
                $this->throwRequestException('calculate_rate', 'POST', $url, $payload, $response);
            }

            /** @var array<string, mixed> $entity */
            $entity = $response->json('entity');

            return new RateQuoteData(
                currency: (string) ($entity['currency'] ?? 'KZT'),
                amountMinor: (int) round((float) ($entity['delivery_sum'] ?? 0) * 100),
                serviceCode: (string) ($entity['tariff_code'] ?? $rateRequest->tariffCode),
            );
        } catch (ConnectionException $exception) {
            $this->failedApiLogger->log('calculate_rate', 'POST', $url, $payload, $exception);

            throw new CarrierRequestException('CDEK rate calculation connection failed.', previous: $exception);
        }
    }

    public function validateAddress(ShipmentData $shipment): AddressValidationResultData
    {
        $errors = [];

        foreach (['country_code', 'city'] as $field) {
            if (blank($shipment->recipient[$field] ?? null)) {
                $errors[] = "Recipient {$field} is required.";
            }
        }

        return new AddressValidationResultData($errors === [], $errors);
    }

    private function authenticatedClient(): PendingRequest
    {
        return $this->client()->withToken($this->accessToken());
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout((int) config('carriers.cdek.timeout', 15));
    }

    private function accessToken(): string
    {
        $credentials = new CarrierCredentialsData(
            clientId: (string) config('carriers.cdek.client_id'),
            clientSecret: (string) config('carriers.cdek.client_secret'),
        );

        if (blank($credentials->clientId) || blank($credentials->clientSecret)) {
            throw new CarrierRequestException('CDEK credentials are not configured.');
        }

        $cacheKey = 'carriers.cdek.token.'.hash('sha256', $credentials->clientId);
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $authentication = $this->authenticate($credentials);
        $ttl = max(60, ($authentication->expiresAt?->getTimestamp() ?? time() + 3600) - time() - 60);
        Cache::put($cacheKey, $authentication->accessToken, $ttl);

        return $authentication->accessToken;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('carriers.cdek.base_url'), '/').'/'.ltrim($path, '/');
    }

    /** @param array<string, mixed> $payload */
    private function throwRequestException(string $operation, string $method, string $url, array $payload, Response $response): never
    {
        $exception = new CarrierRequestException("CDEK {$operation} failed with HTTP {$response->status()}.");
        $this->failedApiLogger->log($operation, $method, $url, $payload, $exception, $response->status(), $response->body());

        throw $exception;
    }
}

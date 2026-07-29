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
    public function __construct(
        private readonly FailedCarrierApiLogger $failedApiLogger,
        private readonly CdekSettingsService $settings,
    ) {}

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
        if ($shipment->serviceCode === null) {
            throw new CarrierRequestException('A CDEK tariff code is required to create a shipment.');
        }

        $url = $this->url('/orders');
        $payload = [
            'type' => 1,
            'number' => $shipment->reference,
            'tariff_code' => $shipment->serviceCode,
            'from_location' => array_filter([
                'code' => $shipment->sender['location_code'] ?? null,
                'address' => $shipment->sender['address'] ?? null,
            ]),
            'to_location' => array_filter([
                'code' => $shipment->recipient['location_code'] ?? null,
                'address' => $shipment->recipient['address'] ?? null,
            ]),
            'sender' => [
                'name' => $shipment->sender['name'] ?? null,
                'phones' => [['number' => $shipment->sender['phone'] ?? null]],
            ],
            'recipient' => [
                'name' => $shipment->recipient['name'] ?? null,
                'phones' => [['number' => $shipment->recipient['phone'] ?? null]],
            ],
            'packages' => $shipment->items,
        ];

        try {
            $response = $this->authenticatedClient()->post($url, $payload);

            if ($response->failed() || ! is_string($response->json('entity.uuid'))) {
                $this->throwRequestException('create_shipment', 'POST', $url, $payload, $response);
            }

            return new ShipmentResultData(
                carrierShipmentId: (string) $response->json('entity.uuid'),
                trackingNumber: is_string($response->json('entity.cdek_number')) ? $response->json('entity.cdek_number') : null,
            );
        } catch (ConnectionException $exception) {
            $this->failedApiLogger->log('create_shipment', 'POST', $url, $payload, $exception);

            throw new CarrierRequestException('CDEK shipment creation connection failed.', previous: $exception);
        }
    }

    public function cancelShipment(string $carrierShipmentId): void
    {
        $url = $this->url('/orders/'.$carrierShipmentId);

        try {
            $response = $this->authenticatedClient()->delete($url);

            if ($response->failed()) {
                $this->throwRequestException('cancel_shipment', 'DELETE', $url, [], $response);
            }
        } catch (ConnectionException $exception) {
            $this->failedApiLogger->log('cancel_shipment', 'DELETE', $url, [], $exception);

            throw new CarrierRequestException('CDEK shipment cancellation connection failed.', previous: $exception);
        }
    }

    public function downloadLabel(string $carrierShipmentId): LabelData
    {
        throw new CarrierOperationNotImplementedException('CDEK label download is not implemented.');
    }

    public function trackShipment(string $trackingNumber): TrackingData
    {
        $url = $this->url('/orders/'.$trackingNumber);

        try {
            $response = $this->authenticatedClient()->get($url);

            if ($response->failed() || ! is_array($response->json('entity'))) {
                $this->throwRequestException('track_shipment', 'GET', $url, [], $response);
            }

            /** @var array<string, mixed> $entity */
            $entity = $response->json('entity');
            $statuses = $entity['statuses'] ?? [];

            return new TrackingData(
                trackingNumber: (string) ($entity['cdek_number'] ?? $trackingNumber),
                events: is_array($statuses) ? array_values(array_filter($statuses, 'is_array')) : [],
            );
        } catch (ConnectionException $exception) {
            $this->failedApiLogger->log('track_shipment', 'GET', $url, [], $exception);

            throw new CarrierRequestException('CDEK tracking connection failed.', previous: $exception);
        }
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

    /** @return array<int, RateQuoteData> */
    public function calculateRates(RateRequestData $rateRequest): array
    {
        $url = $this->url('/calculator/tarifflist');
        $payload = [
            'type' => 1,
            'from_location' => $rateRequest->origin,
            'to_location' => $rateRequest->destination,
            'packages' => $rateRequest->parcels,
        ];

        try {
            $response = $this->authenticatedClient()->post($url, $payload);

            if ($response->failed() || ! is_array($response->json('tariff_codes'))) {
                $this->throwRequestException('calculate_rates', 'POST', $url, $payload, $response);
            }

            return array_map(function (array $quote): RateQuoteData {
                return new RateQuoteData(
                    currency: (string) ($quote['currency'] ?? 'KZT'),
                    amountMinor: (int) round((float) ($quote['delivery_sum'] ?? 0) * 100),
                    serviceCode: isset($quote['tariff_code']) ? (string) $quote['tariff_code'] : null,
                    serviceName: $quote['tariff_name'] ?? null,
                    deliveryDaysMin: isset($quote['period_min']) ? (int) $quote['period_min'] : null,
                    deliveryDaysMax: isset($quote['period_max']) ? (int) $quote['period_max'] : null,
                );
            }, $response->json('tariff_codes'));
        } catch (ConnectionException $exception) {
            $this->failedApiLogger->log('calculate_rates', 'POST', $url, $payload, $exception);

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
        $configuration = $this->settings->configuration();
        $credentials = new CarrierCredentialsData(
            clientId: (string) $configuration->clientId,
            clientSecret: (string) $configuration->clientSecret,
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
        return rtrim($this->settings->configuration()->baseUrl, '/').'/'.ltrim($path, '/');
    }

    /** @param array<string, mixed> $payload */
    private function throwRequestException(string $operation, string $method, string $url, array $payload, Response $response): never
    {
        $exception = new CarrierRequestException("CDEK {$operation} failed with HTTP {$response->status()}.");
        $this->failedApiLogger->log($operation, $method, $url, $payload, $exception, $response->status(), $response->body());

        throw $exception;
    }
}

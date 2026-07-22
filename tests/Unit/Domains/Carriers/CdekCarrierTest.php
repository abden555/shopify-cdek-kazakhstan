<?php

namespace Tests\Unit\Domains\Carriers;

use App\Domains\Carriers\Contracts\CarrierInterface;
use App\Domains\Carriers\DTOs\RateRequestData;
use App\Domains\Carriers\DTOs\ShipmentData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CdekCarrierTest extends TestCase
{
    public function test_it_authenticates_once_and_calculates_a_cdek_rate(): void
    {
        config()->set('carriers.cdek.client_id', 'test-client');
        config()->set('carriers.cdek.client_secret', 'test-secret');
        Cache::flush();

        Http::fake([
            'https://api.edu.cdek.ru/v2/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ]),
            'https://api.edu.cdek.ru/v2/calculator/tariff' => Http::response([
                'entity' => [
                    'currency' => 'KZT',
                    'delivery_sum' => 1250.50,
                    'tariff_code' => 136,
                ],
            ]),
        ]);

        $quote = app(CarrierInterface::class)->calculateRate(new RateRequestData(
            origin: ['code' => 44],
            destination: ['code' => 137],
            parcels: [['weight' => 1000, 'length' => 10, 'width' => 10, 'height' => 10]],
            tariffCode: 136,
        ));

        $this->assertSame('KZT', $quote->currency);
        $this->assertSame(125050, $quote->amountMinor);
        $this->assertSame('136', $quote->serviceCode);
        Http::assertSentCount(2);
    }

    public function test_it_validates_required_recipient_location_fields(): void
    {
        $result = app(CarrierInterface::class)->validateAddress(new ShipmentData(
            reference: 'order-1',
            sender: [],
            recipient: ['country_code' => 'KZ'],
        ));

        $this->assertFalse($result->isValid);
        $this->assertSame(['Recipient city is required.'], $result->errors);
    }
}

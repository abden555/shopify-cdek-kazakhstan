<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Carriers\DTOs\RateRequestData;
use App\Domains\Carriers\Exceptions\CarrierRequestException;
use App\Domains\Carriers\Services\CdekCarrier;
use App\Domains\Carriers\Services\CdekSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PrepareShipmentRequest;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class ShipmentPreparationController extends Controller
{
    public function create(Order $order, CdekSettingsService $settings): View
    {
        $order->loadMissing('shop');
        $draft = $this->draft($order);
        $address = $order->shipping_address ?? [];
        $configuration = $settings->configuration();

        return view('admin.orders.prepare-shipment', compact('order', 'draft', 'address', 'configuration'));
    }

    public function store(PrepareShipmentRequest $request, Order $order, CdekSettingsService $settings): RedirectResponse
    {
        $order->loadMissing('shop');
        $data = $request->validated();
        $configuration = $settings->configuration();
        $shipment = $this->draft($order) ?? new Shipment(['shop_id' => $order->shop_id, 'order_id' => $order->id, 'provider' => 'cdek']);

        $shipment->fill([
            'status' => 'draft',
            'service_code' => $data['tariff_code'] ?? $configuration->defaultTariffCode,
            'shipping_cost' => null,
            'currency' => $order->currency,
            'recipient' => [
                'name' => $data['recipient_name'],
                'phone' => $data['recipient_phone'],
                'country_code' => strtoupper($data['recipient_country_code']),
            ],
            'origin_address' => array_filter([
                'company' => $configuration->senderCompany,
                'phone' => $configuration->senderPhone,
                'city' => $configuration->senderCity,
                'address' => $configuration->senderAddress,
                'pickup_point_code' => $configuration->senderPickupPointCode,
            ]),
            'destination_address' => [
                'country_code' => strtoupper($data['recipient_country_code']),
                'city' => $data['recipient_city'],
                'address' => $data['recipient_address'],
            ],
            'metadata' => [
                'parcel' => [
                    'weight_grams' => (int) $data['weight_grams'],
                    'length_cm' => (int) $data['length_cm'],
                    'width_cm' => (int) $data['width_cm'],
                    'height_cm' => (int) $data['height_cm'],
                    'declared_value' => $data['declared_value'] ?? null,
                ],
            ],
        ]);
        $shipment->save();

        return to_route('admin.orders.shipments.prepare', $order)->with('status', 'Shipment draft saved. Review it before creating a CDEK shipment.');
    }

    public function rates(Order $order, CdekSettingsService $settings, CdekCarrier $carrier): RedirectResponse
    {
        $draft = $this->draft($order);
        $configuration = $settings->configuration();

        if ($draft === null || blank($configuration->senderCity)) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'Save a shipment draft and configure the CDEK sender city before calculating rates.');
        }

        $parcel = $draft->metadata['parcel'] ?? [];
        $destination = $draft->destination_address ?? [];

        try {
            $quotes = $carrier->calculateRates(new RateRequestData(
                origin: ['city' => $configuration->senderCity],
                destination: ['city' => $destination['city'] ?? null, 'country_code' => $destination['country_code'] ?? null],
                parcels: [[
                    'weight' => $parcel['weight_grams'] ?? null,
                    'length' => $parcel['length_cm'] ?? null,
                    'width' => $parcel['width_cm'] ?? null,
                    'height' => $parcel['height_cm'] ?? null,
                ]],
            ));
        } catch (CarrierRequestException) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'CDEK did not return rates. Check the sender profile, recipient address, and Failed API Logs.');
        }

        $metadata = $draft->metadata ?? [];
        $metadata['rate_quotes'] = array_map(static fn ($quote): array => [
            'service_code' => $quote->serviceCode,
            'service_name' => $quote->serviceName,
            'amount_minor' => $quote->amountMinor,
            'currency' => $quote->currency,
            'delivery_days_min' => $quote->deliveryDaysMin,
            'delivery_days_max' => $quote->deliveryDaysMax,
        ], $quotes);
        $draft->update(['metadata' => $metadata]);

        return to_route('admin.orders.shipments.prepare', $order)->with('status', count($quotes).' CDEK rate option(s) retrieved. Select one and save the draft.');
    }

    private function draft(Order $order): ?Shipment
    {
        return $order->shipments()->where('provider', 'cdek')->where('status', 'draft')->latest()->first();
    }
}

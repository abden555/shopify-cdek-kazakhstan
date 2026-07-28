<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Carriers\Actions\CreateShipmentAction;
use App\Domains\Carriers\DTOs\RateRequestData;
use App\Domains\Carriers\DTOs\ShipmentData;
use App\Domains\Carriers\Exceptions\CarrierRequestException;
use App\Domains\Carriers\Services\CdekCarrier;
use App\Domains\Carriers\Services\CdekSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCdekShipmentRequest;
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
        $draft = $this->shipment($order);
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
                'location_code' => $data['recipient_location_code'],
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

        if ($draft === null || blank($configuration->senderLocationCode)) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'Save a shipment draft and configure the CDEK sender location code before calculating rates.');
        }

        $parcel = $draft->metadata['parcel'] ?? [];
        $destination = $draft->destination_address ?? [];

        try {
            $quotes = $carrier->calculateRates(new RateRequestData(
                origin: ['code' => $configuration->senderLocationCode],
                destination: ['code' => $destination['location_code'] ?? null],
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

    public function submit(CreateCdekShipmentRequest $request, Order $order, CdekSettingsService $settings, CreateShipmentAction $createShipment): RedirectResponse
    {
        $order->loadMissing('items');
        $shipment = $this->draft($order);
        $configuration = $settings->configuration();

        if ($shipment === null || blank($shipment->service_code) || blank($configuration->senderLocationCode) || blank($configuration->senderCompany) || blank($configuration->senderPhone)) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'Save a complete draft, select a tariff, and configure the CDEK sender profile before creating a shipment.');
        }

        $parcel = $shipment->metadata['parcel'] ?? [];
        $destination = $shipment->destination_address ?? [];

        if (blank($destination['location_code'] ?? null)) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'The recipient CDEK location code is required before creating a shipment.');
        }

        $totalQuantity = max(1, (int) $order->items->sum('quantity'));
        $weightPerItem = max(1, (int) floor(((int) $parcel['weight_grams']) / $totalQuantity));
        $packageItems = $order->items->map(static fn ($item): array => [
            'name' => $item->title,
            'ware_key' => $item->sku ?: $item->id,
            'amount' => (int) $item->quantity,
            'cost' => (float) $item->unit_price,
            'weight' => $weightPerItem,
        ])->all();

        try {
            $result = $createShipment->handle('cdek', new ShipmentData(
                reference: 'CDEK-'.$shipment->id,
                sender: [
                    'name' => $configuration->senderCompany,
                    'phone' => $configuration->senderPhone,
                    'location_code' => $configuration->senderLocationCode,
                ],
                recipient: [
                    'name' => $shipment->recipient['name'] ?? null,
                    'phone' => $shipment->recipient['phone'] ?? null,
                    'location_code' => $destination['location_code'],
                ],
                items: [[
                    'number' => '1',
                    'weight' => (int) $parcel['weight_grams'],
                    'length' => (int) $parcel['length_cm'],
                    'width' => (int) $parcel['width_cm'],
                    'height' => (int) $parcel['height_cm'],
                    'items' => $packageItems,
                ]],
                serviceCode: (int) $shipment->service_code,
            ));
        } catch (CarrierRequestException) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'CDEK could not create the shipment. No local shipment status was changed. Review Failed API Logs before retrying.');
        }

        $metadata = $shipment->metadata ?? [];
        $metadata['cdek_reference'] = 'CDEK-'.$shipment->id;
        $shipment->update([
            'external_id' => $result->carrierShipmentId,
            'tracking_number' => $result->trackingNumber,
            'status' => 'created',
            'metadata' => $metadata,
        ]);

        return to_route('admin.orders.shipments.prepare', $order)->with('status', 'CDEK shipment created successfully.'.($result->trackingNumber ? ' Tracking number: '.$result->trackingNumber.'.' : ''));
    }

    private function draft(Order $order): ?Shipment
    {
        return $order->shipments()->where('provider', 'cdek')->where('status', 'draft')->latest()->first();
    }

    private function shipment(Order $order): ?Shipment
    {
        return $order->shipments()->where('provider', 'cdek')->latest()->first();
    }
}

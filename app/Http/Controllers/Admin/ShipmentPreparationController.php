<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Carriers\Actions\CancelShipmentAction;
use App\Domains\Carriers\Actions\CreateShipmentAction;
use App\Domains\Carriers\Actions\DownloadLabelAction;
use App\Domains\Carriers\Actions\FindPickupPointsAction;
use App\Domains\Carriers\Actions\RequestLabelAction;
use App\Domains\Carriers\DTOs\RateRequestData;
use App\Domains\Carriers\DTOs\ShipmentData;
use App\Domains\Carriers\Exceptions\CarrierRequestException;
use App\Domains\Carriers\Services\CdekCarrier;
use App\Domains\Carriers\Services\CdekSettingsService;
use App\Domains\Carriers\Services\CdekTrackingSynchronizer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelCdekShipmentRequest;
use App\Http\Requests\Admin\CreateCdekShipmentRequest;
use App\Http\Requests\Admin\PrepareShipmentRequest;
use App\Models\Label;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'delivery_point_code' => $data['recipient_delivery_point_code'] ?? null,
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

    public function pickupPoints(Request $request, FindPickupPointsAction $findPickupPoints): JsonResponse
    {
        $data = $request->validate(['location_code' => ['required', 'string', 'max:50']]);

        try {
            $points = $findPickupPoints->handle('cdek', $data['location_code']);
        } catch (CarrierRequestException) {
            return response()->json(['message' => 'CDEK pickup points could not be loaded.'], 422);
        }

        return response()->json(['data' => array_map(static fn ($point): array => [
            'code' => $point->code,
            'name' => $point->name,
            'address' => $point->address,
            'work_time' => $point->workTime,
        ], $points)]);
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
        $cdekReference = 'CDEK'.strtoupper(Str::random(24));
        $packageItems = $order->items->map(static fn ($item): array => [
            'name' => $item->title,
            'ware_key' => $item->sku ?: $item->id,
            'amount' => (int) $item->quantity,
            'cost' => (float) $item->unit_price,
            'weight' => $weightPerItem,
            'payment' => ['value' => 0],
        ])->all();

        try {
            $result = $createShipment->handle('cdek', new ShipmentData(
                reference: $cdekReference,
                sender: [
                    'name' => $configuration->senderCompany,
                    'phone' => $configuration->senderPhone,
                    'location_code' => $configuration->senderLocationCode,
                    'address' => $configuration->senderAddress,
                    'pickup_point_code' => $configuration->senderPickupPointCode,
                ],
                recipient: [
                    'name' => $shipment->recipient['name'] ?? null,
                    'phone' => $shipment->recipient['phone'] ?? null,
                    'location_code' => $destination['location_code'],
                    'address' => $destination['address'] ?? null,
                    'delivery_point_code' => $destination['delivery_point_code'] ?? null,
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
        $metadata['cdek_reference'] = $cdekReference;
        $shipment->update([
            'external_id' => $result->carrierShipmentId,
            'tracking_number' => $result->trackingNumber,
            'status' => 'created',
            'metadata' => $metadata,
        ]);

        return to_route('admin.orders.shipments.prepare', $order)->with('status', 'CDEK shipment created successfully.'.($result->trackingNumber ? ' Tracking number: '.$result->trackingNumber.'.' : ''));
    }

    public function track(Order $order, CdekTrackingSynchronizer $synchronizer): RedirectResponse
    {
        $shipment = $this->shipment($order);

        if ($shipment === null || blank($shipment->external_id)) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'Create the CDEK shipment before refreshing tracking.');
        }

        try {
            $eventCount = $synchronizer->sync($shipment);
        } catch (CarrierRequestException) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'CDEK tracking could not be refreshed. Review Failed API Logs.');
        }

        return to_route('admin.orders.shipments.prepare', $order)->with('status', $eventCount.' CDEK tracking event(s) refreshed.');
    }

    public function cancel(CancelCdekShipmentRequest $request, Order $order, CancelShipmentAction $cancelShipment): RedirectResponse
    {
        $shipment = $this->shipment($order);

        if ($shipment === null || blank($shipment->external_id) || $shipment->status === 'cancelled') {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'This CDEK shipment cannot be cancelled.');
        }

        try {
            $cancelShipment->handle('cdek', $shipment->external_id);
        } catch (CarrierRequestException) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'CDEK could not cancel the shipment. Review Failed API Logs.');
        }

        $shipment->update(['status' => 'cancelled']);

        return to_route('admin.orders.shipments.prepare', $order)->with('status', 'CDEK shipment cancellation was submitted. Refresh tracking to confirm its final status.');
    }

    public function requestLabel(Request $request, Order $order, RequestLabelAction $requestLabel): RedirectResponse
    {
        $shipment = $this->shipment($order);

        if ($shipment === null || blank($shipment->external_id) || $shipment->status === 'cancelled') {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'Only an active CDEK shipment can have a label generated.');
        }

        $metadata = $shipment->metadata ?? [];

        if (filled($metadata['cdek_print_request_uuid'] ?? null) && ! $request->boolean('regenerate')) {
            return to_route('admin.orders.shipments.prepare', $order)->with('status', 'The CDEK label is already being prepared. Download it when ready.');
        }

        try {
            unset($metadata['cdek_print_request_uuid']);
            $metadata['cdek_print_request_uuid'] = $requestLabel->handle('cdek', $shipment->external_id);
        } catch (CarrierRequestException) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'CDEK could not start label generation. Review Failed API Logs.');
        }

        $shipment->update(['metadata' => $metadata]);

        return to_route('admin.orders.shipments.prepare', $order)->with('status', 'CDEK label generation requested. Wait a moment, then download the label.');
    }

    public function downloadLabel(Order $order, DownloadLabelAction $downloadLabel): StreamedResponse|RedirectResponse
    {
        $shipment = $this->shipment($order);

        if ($shipment === null) {
            return to_route('admin.orders.index')->with('error', 'Shipment not found.');
        }

        $label = $shipment->labels()->latest('generated_at')->first();

        if ($label !== null && Storage::disk($label->disk)->exists($label->path)) {
            return Storage::disk($label->disk)->download($label->path, 'cdek-label-'.$shipment->tracking_number.'.pdf', ['Content-Type' => 'application/pdf']);
        }

        $printRequestId = $shipment->metadata['cdek_print_request_uuid'] ?? null;

        if (blank($printRequestId)) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', 'Request CDEK label generation before downloading it.');
        }

        try {
            $labelData = $downloadLabel->handle('cdek', $printRequestId);
        } catch (CarrierRequestException $exception) {
            return to_route('admin.orders.shipments.prepare', $order)->with('error', $exception->getMessage());
        }

        $path = 'labels/cdek/'.$shipment->id.'/'.$printRequestId.'.pdf';
        Storage::disk('local')->put($path, $labelData->content);
        Label::query()->updateOrCreate(
            ['shipment_id' => $shipment->id, 'path' => $path],
            [
                'format' => 'pdf',
                'disk' => 'local',
                'checksum' => hash('sha256', $labelData->content),
                'size_bytes' => strlen($labelData->content),
                'generated_at' => now(),
            ],
        );

        return Storage::disk('local')->download($path, $labelData->fileName ?: 'cdek-label-'.$shipment->id.'.pdf', ['Content-Type' => $labelData->mimeType]);
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

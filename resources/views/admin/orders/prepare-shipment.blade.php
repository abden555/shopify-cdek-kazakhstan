<x-admin.layout :title="'Prepare shipment '.$order->order_number">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a class="text-decoration-none small" href="{{ route('admin.orders.index') }}"><i class="bi bi-arrow-left"></i> Back to orders</a>
            <h2 class="h3 mt-2 mb-1">Prepare CDEK shipment {{ $order->order_number }}</h2>
            <p class="text-body-secondary mb-0">Saving this form creates a local draft only. It does not submit an order to CDEK.</p>
        </div>
        <span class="badge text-bg-light border">{{ number_format((float) $order->total_amount, 2) }} {{ $order->currency }}</span>
    </div>

    @if (session('status'))<div class="alert alert-success" role="alert">{{ session('status') }}</div>@endif

    @php($parcel = $draft?->metadata['parcel'] ?? [])
    <form method="POST" action="{{ route('admin.orders.shipments.store', $order) }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body p-4">
            <div class="row g-4">
                <section class="col-lg-6">
                    <h3 class="h5 mb-3">Recipient</h3>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Name</label><input name="recipient_name" class="form-control @error('recipient_name') is-invalid @enderror" value="{{ old('recipient_name', $draft?->recipient['name'] ?? trim(($address['firstName'] ?? '').' '.($address['lastName'] ?? ''))) }}">@error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input name="recipient_phone" class="form-control @error('recipient_phone') is-invalid @enderror" value="{{ old('recipient_phone', $draft?->recipient['phone'] ?? ($address['phone'] ?? '')) }}">@error('recipient_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label">Country code</label><input name="recipient_country_code" class="form-control @error('recipient_country_code') is-invalid @enderror" value="{{ old('recipient_country_code', $draft?->destination_address['country_code'] ?? ($address['countryCodeV2'] ?? 'KZ')) }}">@error('recipient_country_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label">City</label><input name="recipient_city" class="form-control @error('recipient_city') is-invalid @enderror" value="{{ old('recipient_city', $draft?->destination_address['city'] ?? ($address['city'] ?? '')) }}">@error('recipient_city')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label">Address</label><input name="recipient_address" class="form-control @error('recipient_address') is-invalid @enderror" value="{{ old('recipient_address', $draft?->destination_address['address'] ?? trim(($address['address1'] ?? '').' '.($address['address2'] ?? ''))) }}">@error('recipient_address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </div>
                </section>
                <section class="col-lg-6">
                    <h3 class="h5 mb-3">Parcel and tariff</h3>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">CDEK tariff code <span class="text-body-secondary">(optional for draft)</span></label><input type="number" min="1" name="tariff_code" class="form-control" value="{{ old('tariff_code', $draft?->service_code ?? $configuration->defaultTariffCode) }}"></div>
                        @foreach (['weight_grams' => 'Weight (grams)', 'length_cm' => 'Length (cm)', 'width_cm' => 'Width (cm)', 'height_cm' => 'Height (cm)'] as $field => $label)
                            <div class="col-md-6"><label class="form-label">{{ $label }}</label><input type="number" min="1" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $parcel[$field] ?? '') }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        @endforeach
                        <div class="col-12"><label class="form-label">Declared value <span class="text-body-secondary">({{ $order->currency }})</span></label><input type="number" min="0" step="0.01" name="declared_value" class="form-control" value="{{ old('declared_value', $parcel['declared_value'] ?? $order->total_amount) }}"></div>
                    </div>
                </section>
            </div>
        </div>
        <div class="card-footer bg-white border-0 p-4 pt-0"><button class="btn btn-primary" type="submit">Save shipment draft</button></div>
    </form>
</x-admin.layout>

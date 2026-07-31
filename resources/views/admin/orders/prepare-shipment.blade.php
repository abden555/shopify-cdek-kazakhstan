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
    @if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif

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
                        <div class="col-md-6"><label class="form-label">Country code</label><input id="recipient_country_code" name="recipient_country_code" class="form-control @error('recipient_country_code') is-invalid @enderror" value="{{ old('recipient_country_code', $draft?->destination_address['country_code'] ?? ($address['countryCodeV2'] ?? 'KZ')) }}">@error('recipient_country_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label">City</label><input id="recipient_city" name="recipient_city" class="form-control @error('recipient_city') is-invalid @enderror" value="{{ old('recipient_city', $draft?->destination_address['city'] ?? ($address['city'] ?? '')) }}">@error('recipient_city')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label">CDEK recipient location code</label><input id="recipient_location_code" name="recipient_location_code" class="form-control @error('recipient_location_code') is-invalid @enderror" value="{{ old('recipient_location_code', $draft?->destination_address['location_code'] ?? '') }}"><div id="location-feedback" class="form-text" data-url="{{ route('admin.orders.shipments.locations') }}">Filled automatically from the recipient city. You can change it if required.</div>@error('recipient_location_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12">
                            <label class="form-label">CDEK recipient pickup-point code <span class="text-body-secondary">(required for pickup-point tariffs)</span></label>
                            <div class="input-group">
                                <input id="recipient_delivery_point_code" name="recipient_delivery_point_code" class="form-control @error('recipient_delivery_point_code') is-invalid @enderror" value="{{ old('recipient_delivery_point_code', $draft?->destination_address['delivery_point_code'] ?? '') }}">
                                <button id="load-pickup-points" class="btn btn-outline-secondary" type="button" data-url="{{ route('admin.orders.shipments.pickup-points') }}"><i class="bi bi-geo-alt me-1"></i>Load pickup points</button>
                            </div>
                            <select id="pickup-point-results" class="form-select mt-2 d-none" aria-label="CDEK pickup point results"></select>
                            <div id="pickup-point-feedback" class="form-text">An active pickup point is selected automatically after the recipient location is identified. You can choose another one.</div>
                            @error('recipient_delivery_point_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12"><label class="form-label">Address</label><input name="recipient_address" class="form-control @error('recipient_address') is-invalid @enderror" value="{{ old('recipient_address', $draft?->destination_address['address'] ?? trim(($address['address1'] ?? '').' '.($address['address2'] ?? ''))) }}">@error('recipient_address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </div>
                </section>
                <section class="col-lg-6">
                    <h3 class="h5 mb-3">Parcel and tariff</h3>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">CDEK tariff code <span class="text-body-secondary">(optional for draft)</span></label><input id="tariff_code" type="number" min="1" name="tariff_code" class="form-control" value="{{ old('tariff_code', $draft?->service_code ?? $configuration->defaultTariffCode) }}"></div>
                        @foreach (['weight_grams' => 'Weight (grams)', 'length_cm' => 'Length (cm)', 'width_cm' => 'Width (cm)', 'height_cm' => 'Height (cm)'] as $field => $label)
                            <div class="col-md-6"><label class="form-label">{{ $label }}</label><input type="number" min="1" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $parcel[$field] ?? $defaultParcel[$field]) }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        @endforeach
                        <div class="col-12"><label class="form-label">Declared value <span class="text-body-secondary">({{ $order->currency }})</span></label><input type="number" min="0" step="0.01" name="declared_value" class="form-control" value="{{ old('declared_value', $parcel['declared_value'] ?? $order->total_amount) }}"></div>
                    </div>
                </section>
            </div>
        </div>
        <div class="card-footer bg-white border-0 p-4 pt-0"><button class="btn btn-primary" type="submit">Save shipment draft</button></div>
    </form>

    @if (! empty($draft?->metadata['rate_quotes']))
        <section class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4">
                <h3 class="h5 mb-3">Available CDEK rates</h3>
                <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Tariff</th><th>Delivery time</th><th>Cost</th><th></th></tr></thead><tbody>
                    @foreach ($draft->metadata['rate_quotes'] as $quote)
                        <tr><td><strong>{{ $quote['service_code'] }}</strong><br><span class="small text-body-secondary">{{ $quote['service_name'] }}</span></td><td>{{ $quote['delivery_days_min'] }}–{{ $quote['delivery_days_max'] }} days</td><td>{{ number_format($quote['amount_minor'] / 100, 2) }} {{ $quote['currency'] }}</td><td><button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('tariff_code').value='{{ $quote['service_code'] }}'; window.scrollTo({ top: 0, behavior: 'smooth' });">Select</button></td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
        </section>
    @endif

    @if ($draft)
        <form method="POST" action="{{ route('admin.orders.shipments.rates', $order) }}" class="mt-3">
            @csrf
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-calculator me-1"></i>Calculate CDEK rates</button>
            <span class="form-text ms-2">This requests quotes only; it does not create a CDEK shipment.</span>
        </form>

        <section class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4">
                <h3 class="h5">Create CDEK shipment</h3>
                @if (in_array($draft->status, ['created', 'cancelled'], true))
                    <p class="text-success">Created in CDEK. {{ $draft->tracking_number ? 'Tracking number: '.$draft->tracking_number : 'CDEK reference: '.$draft->external_id }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.orders.shipments.track', $order) }}">@csrf<button class="btn btn-outline-primary" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Refresh tracking</button></form>
                        @if (empty($draft->metadata['cdek_print_request_uuid']))
                            <form method="POST" action="{{ route('admin.orders.shipments.label.request', $order) }}">@csrf<button class="btn btn-outline-secondary" type="submit"><i class="bi bi-file-earmark-pdf me-1"></i>Generate label</button></form>
                        @else
                            <a class="btn btn-outline-secondary" href="{{ route('admin.orders.shipments.label.download', $order) }}"><i class="bi bi-download me-1"></i>Download label</a>
                            <form method="POST" action="{{ route('admin.orders.shipments.label.request', $order) }}" onsubmit="return confirm('Request a new CDEK label? Use this only if the existing label request is invalid.');">
                                @csrf
                                <input type="hidden" name="regenerate" value="1">
                                <button class="btn btn-outline-warning" type="submit"><i class="bi bi-arrow-clockwise me-1"></i>Regenerate label</button>
                            </form>
                        @endif
                        @if ($draft->status !== 'cancelled')
                            <form method="POST" action="{{ route('admin.orders.shipments.cancel', $order) }}" onsubmit="return confirm('Request CDEK to cancel this shipment?');">
                                @csrf
                                <input type="hidden" name="confirm_cancel" value="1">
                                <button class="btn btn-outline-danger" type="submit"><i class="bi bi-x-circle me-1"></i>Cancel shipment</button>
                            </form>
                        @endif
                    </div>
                @else
                    <p class="text-body-secondary">This sends the selected tariff and draft details to CDEK. Verify every value before continuing.</p>
                    <form method="POST" action="{{ route('admin.orders.shipments.submit', $order) }}">
                        @csrf
                        <div class="form-check mb-3">
                            <input class="form-check-input @error('confirm_create') is-invalid @enderror" type="checkbox" value="1" id="confirm_create" name="confirm_create">
                            <label class="form-check-label" for="confirm_create">I confirm that the recipient, tariff, parcel, and sender details are correct.</label>
                            @error('confirm_create')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-success" type="submit"><i class="bi bi-send-check me-1"></i>Create CDEK shipment</button>
                    </form>
                @endif
            </div>
        </section>
    @endif
    <script>
        (() => {
            const locationCode = document.getElementById('recipient_location_code');
            const city = document.getElementById('recipient_city');
            const countryCode = document.getElementById('recipient_country_code');
            const locationFeedback = document.getElementById('location-feedback');
            const pointCode = document.getElementById('recipient_delivery_point_code');
            const loadButton = document.getElementById('load-pickup-points');
            const results = document.getElementById('pickup-point-results');
            const feedback = document.getElementById('pickup-point-feedback');

            const loadPickupPoints = async (selectFirst = false) => {
                if (!locationCode?.value.trim()) {
                    feedback.textContent = 'Waiting for the recipient location code.';
                    return false;
                }

                loadButton.disabled = true;
                feedback.textContent = 'Loading CDEK pickup points…';

                try {
                    const response = await fetch(loadButton.dataset.url + '?location_code=' + encodeURIComponent(locationCode.value.trim()), {
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'CDEK pickup points could not be loaded.');
                    }

                    results.replaceChildren();
                    results.add(new Option('Select a CDEK pickup point', ''));

                    payload.data.forEach((point) => {
                        const details = [point.name, point.address, point.work_time].filter(Boolean).join(' — ');
                        results.add(new Option(point.code + ': ' + details, point.code));
                    });

                    results.classList.toggle('d-none', payload.data.length === 0);
                    if (selectFirst && payload.data.length && !pointCode.value.trim()) {
                        results.value = payload.data[0].code;
                        pointCode.value = payload.data[0].code;
                    }
                    feedback.textContent = payload.data.length
                        ? payload.data.length + ' active pickup point(s) found.' + (selectFirst && pointCode.value ? ' The first point was selected; choose another if needed.' : ' Select one to fill the code.')
                        : 'No active pickup points were found for this location.';
                } catch (error) {
                    feedback.textContent = error.message || 'CDEK pickup points could not be loaded.';
                } finally {
                    loadButton.disabled = false;
                }
                return true;
            };

            const loadLocation = async () => {
                if (locationCode.value.trim() || !city.value.trim() || !countryCode.value.trim()) return;

                locationFeedback.textContent = 'Finding the CDEK location for ' + city.value.trim() + '…';
                try {
                    const response = await fetch(locationFeedback.dataset.url + '?city=' + encodeURIComponent(city.value.trim()) + '&country_code=' + encodeURIComponent(countryCode.value.trim()), { headers: { Accept: 'application/json' } });
                    const payload = await response.json();
                    if (!response.ok || !payload.data?.length) throw new Error(payload.message || 'No CDEK location was found for this city.');
                    locationCode.value = payload.data[0].code;
                    locationFeedback.textContent = 'CDEK location ' + payload.data[0].code + ' was selected for ' + payload.data[0].name + '.';
                    await loadPickupPoints(true);
                } catch (error) {
                    locationFeedback.textContent = error.message || 'CDEK location lookup could not be completed.';
                }
            };

            loadButton?.addEventListener('click', () => loadPickupPoints(false));
            city?.addEventListener('change', () => { locationCode.value = ''; pointCode.value = ''; loadLocation(); });
            countryCode?.addEventListener('change', () => { locationCode.value = ''; pointCode.value = ''; loadLocation(); });
            loadLocation();

            results?.addEventListener('change', () => {
                if (results.value) {
                    pointCode.value = results.value;
                }
            });
        })();
    </script>
</x-admin.layout>

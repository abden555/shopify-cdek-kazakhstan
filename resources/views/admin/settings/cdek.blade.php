<x-admin.layout title="CDEK settings">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">CDEK settings</h1>
            <p class="text-body-secondary mb-0">Manage the CDEK integration and sender profile.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.cdek.update') }}" class="card shadow-sm border-0">
        @csrf
        @method('PUT')
        <div class="card-body p-4">
            <h2 class="h5 mb-3">API connection</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="base_url">Environment</label>
                    <select class="form-select @error('base_url') is-invalid @enderror" id="base_url" name="base_url">
                        <option value="https://api.edu.cdek.ru/v2" @selected(old('base_url', $settings['base_url']) === 'https://api.edu.cdek.ru/v2')>Test / sandbox</option>
                        <option value="https://api.cdek.ru/v2" @selected(old('base_url', $settings['base_url']) === 'https://api.cdek.ru/v2')>Live production</option>
                    </select>
                    @error('base_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="client_id">Client ID</label>
                    <input class="form-control @error('client_id') is-invalid @enderror" id="client_id" name="client_id" value="{{ old('client_id', $settings['client_id']) }}" autocomplete="off">
                    @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="client_secret">Client secret</label>
                    <input type="password" class="form-control @error('client_secret') is-invalid @enderror" id="client_secret" name="client_secret" autocomplete="new-password" placeholder="{{ $settings['client_secret_configured'] ? 'Configured — leave empty to keep unchanged' : 'Enter client secret' }}">
                    <div class="form-text">Stored encrypted. It is never displayed after saving.</div>
                    @error('client_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">
            <h2 class="h5 mb-3">Sender profile</h2>
            <div class="row g-3">
                @foreach (['sender_company' => 'Company name', 'sender_phone' => 'Contact phone', 'sender_city' => 'City', 'sender_pickup_point_code' => 'CDEK pickup-point code'] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                        <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $settings[$field]) }}">
                        @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <div class="col-md-8">
                    <label class="form-label" for="sender_address">Address</label>
                    <input class="form-control @error('sender_address') is-invalid @enderror" id="sender_address" name="sender_address" value="{{ old('sender_address', $settings['sender_address']) }}">
                    @error('sender_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="default_tariff_code">Default tariff code <span class="text-body-secondary">(optional)</span></label>
                    <input type="number" min="1" class="form-control @error('default_tariff_code') is-invalid @enderror" id="default_tariff_code" name="default_tariff_code" value="{{ old('default_tariff_code', $settings['default_tariff_code']) }}">
                    @error('default_tariff_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 p-4 pt-0"><button class="btn btn-primary" type="submit">Save CDEK settings</button></div>
    </form>
</x-admin.layout>

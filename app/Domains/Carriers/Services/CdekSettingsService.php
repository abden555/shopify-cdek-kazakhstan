<?php

namespace App\Domains\Carriers\Services;

use App\Domains\Carriers\DTOs\CdekConfigurationData;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;

final class CdekSettingsService
{
    private const PREFIX = 'cdek.';

    private ?bool $settingsStorageAvailable = null;

    /** @param array<string, mixed> $input */
    public function save(array $input): void
    {
        foreach ([
            'base_url', 'client_id', 'sender_company', 'sender_phone', 'sender_city',
            'sender_address', 'sender_pickup_point_code', 'default_tariff_code',
        ] as $key) {
            $this->put($key, $input[$key] ?? null);
        }

        if (filled($input['client_secret'] ?? null)) {
            $this->put('client_secret', (string) $input['client_secret'], encrypted: true);
        }
    }

    public function configuration(): CdekConfigurationData
    {
        return new CdekConfigurationData(
            baseUrl: (string) ($this->get('base_url') ?: config('carriers.cdek.base_url')),
            clientId: $this->nullable('client_id') ?? config('carriers.cdek.client_id'),
            clientSecret: $this->nullable('client_secret') ?? config('carriers.cdek.client_secret'),
            senderCompany: $this->nullable('sender_company'),
            senderPhone: $this->nullable('sender_phone'),
            senderCity: $this->nullable('sender_city'),
            senderAddress: $this->nullable('sender_address'),
            senderPickupPointCode: $this->nullable('sender_pickup_point_code'),
            defaultTariffCode: ($value = $this->get('default_tariff_code')) !== null ? (int) $value : null,
        );
    }

    /** @return array<string, mixed> */
    public function formValues(): array
    {
        $configuration = $this->configuration();

        return [
            'base_url' => $configuration->baseUrl,
            'client_id' => $configuration->clientId,
            'client_secret_configured' => $this->get('client_secret') !== null || filled(config('carriers.cdek.client_secret')),
            'sender_company' => $configuration->senderCompany,
            'sender_phone' => $configuration->senderPhone,
            'sender_city' => $configuration->senderCity,
            'sender_address' => $configuration->senderAddress,
            'sender_pickup_point_code' => $configuration->senderPickupPointCode,
            'default_tariff_code' => $configuration->defaultTariffCode,
        ];
    }

    private function put(string $key, mixed $value, bool $encrypted = false): void
    {
        $payload = $encrypted ? ['value' => Crypt::encryptString((string) $value)] : ['value' => $value];

        Setting::query()->updateOrCreate(
            ['shop_id' => null, 'setting_key' => self::PREFIX.$key],
            ['value' => $payload, 'is_encrypted' => $encrypted],
        );
    }

    private function nullable(string $key): ?string
    {
        $value = $this->get($key);

        return $value === null ? null : (string) $value;
    }

    private function get(string $key): mixed
    {
        if ($this->settingsStorageAvailable === false) {
            return null;
        }

        try {
            $setting = Setting::query()->whereNull('shop_id')->where('setting_key', self::PREFIX.$key)->first();
            $this->settingsStorageAvailable = true;
        } catch (QueryException) {
            // Environment values remain a safe deployment fallback if settings storage is unavailable.
            $this->settingsStorageAvailable = false;

            return null;
        }

        if ($setting === null) {
            return null;
        }

        $value = $setting->value['value'] ?? null;

        return $setting->is_encrypted && is_string($value) ? Crypt::decryptString($value) : $value;
    }
}

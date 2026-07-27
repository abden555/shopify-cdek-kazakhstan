<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class PrepareShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:50'],
            'recipient_country_code' => ['required', 'string', 'size:2'],
            'recipient_city' => ['required', 'string', 'max:255'],
            'recipient_location_code' => ['required', 'string', 'max:50'],
            'recipient_address' => ['required', 'string', 'max:1000'],
            'tariff_code' => ['nullable', 'integer', 'min:1'],
            'weight_grams' => ['required', 'integer', 'min:1', 'max:300000'],
            'length_cm' => ['required', 'integer', 'min:1', 'max:500'],
            'width_cm' => ['required', 'integer', 'min:1', 'max:500'],
            'height_cm' => ['required', 'integer', 'min:1', 'max:500'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

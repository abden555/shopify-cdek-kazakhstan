<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCdekSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'base_url' => ['required', 'url', Rule::in(['https://api.edu.cdek.ru/v2', 'https://api.cdek.ru/v2'])],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
            'sender_company' => ['nullable', 'string', 'max:255'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'sender_city' => ['nullable', 'string', 'max:255'],
            'sender_address' => ['nullable', 'string', 'max:1000'],
            'sender_pickup_point_code' => ['nullable', 'string', 'max:100'],
            'default_tariff_code' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

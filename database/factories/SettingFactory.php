<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Setting> */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return ['setting_key' => fake()->unique()->slug(2, '_'), 'value' => ['enabled' => true], 'is_encrypted' => false];
    }
}

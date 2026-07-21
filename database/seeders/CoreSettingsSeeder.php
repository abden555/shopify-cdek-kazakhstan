<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'application.locale' => ['value' => 'en'],
            'application.timezone' => ['value' => 'Asia/Almaty'],
            'queue.default' => ['value' => 'database'],
        ] as $settingKey => $value) {
            Setting::firstOrCreate(
                ['shop_id' => null, 'setting_key' => $settingKey],
                ['value' => $value],
            );
        }
    }
}

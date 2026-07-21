<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ActivityLog> */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return ['shop_id' => Shop::factory(), 'causer_user_id' => User::factory(), 'event' => 'created', 'description' => fake()->sentence(), 'properties' => [], 'ip_address' => fake()->ipv4(), 'user_agent' => fake()->userAgent()];
    }
}

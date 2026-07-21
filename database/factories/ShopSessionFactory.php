<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\ShopSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShopSession> */
class ShopSessionFactory extends Factory
{
    protected $model = ShopSession::class;

    public function definition(): array
    {
        return ['shop_id' => Shop::factory(), 'session_key' => fake()->unique()->sha256(), 'access_token' => fake()->sha256(), 'scopes' => ['read_orders'], 'expires_at' => now()->addMonth()];
    }
}

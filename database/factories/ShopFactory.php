<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shop> */
class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'name' => fake()->company(), 'domain' => fake()->unique()->domainName(), 'platform' => 'shopify', 'external_id' => fake()->unique()->uuid(), 'currency' => 'KZT', 'timezone' => 'Asia/Almaty', 'is_active' => true, 'metadata' => []];
    }
}

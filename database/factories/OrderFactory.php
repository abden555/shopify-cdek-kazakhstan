<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);

        return ['shop_id' => Shop::factory(), 'external_id' => fake()->uuid(), 'order_number' => (string) fake()->numberBetween(1000, 999999), 'email' => fake()->safeEmail(), 'currency' => 'KZT', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled', 'subtotal_amount' => $subtotal, 'shipping_amount' => 0, 'tax_amount' => 0, 'discount_amount' => 0, 'total_amount' => $subtotal, 'billing_address' => [], 'shipping_address' => [], 'metadata' => [], 'ordered_at' => now()];
    }
}

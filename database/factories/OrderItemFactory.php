<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 4);
        $price = fake()->randomFloat(2, 100, 10000);

        return ['order_id' => Order::factory(), 'external_id' => fake()->uuid(), 'sku' => fake()->bothify('SKU-####'), 'title' => fake()->words(3, true), 'quantity' => $quantity, 'fulfilled_quantity' => 0, 'unit_price' => $price, 'discount_amount' => 0, 'total_amount' => $price * $quantity, 'metadata' => []];
    }
}

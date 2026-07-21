<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShipmentItem> */
class ShipmentItemFactory extends Factory
{
    protected $model = ShipmentItem::class;

    public function definition(): array
    {
        return ['shipment_id' => Shipment::factory(), 'description' => fake()->words(3, true), 'sku' => fake()->bothify('SKU-####'), 'quantity' => fake()->numberBetween(1, 4), 'weight_grams' => fake()->numberBetween(100, 5000), 'declared_value' => fake()->randomFloat(2, 100, 10000)];
    }
}

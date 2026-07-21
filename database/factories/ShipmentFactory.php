<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shipment> */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return ['shop_id' => Shop::factory(), 'order_id' => Order::factory(), 'external_id' => fake()->uuid(), 'provider' => 'cdek', 'tracking_number' => fake()->bothify('CDEK########'), 'status' => 'pending', 'currency' => 'KZT', 'recipient' => [], 'origin_address' => [], 'destination_address' => [], 'metadata' => []];
    }
}

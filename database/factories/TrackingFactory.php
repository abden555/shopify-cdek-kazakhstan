<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\Tracking;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tracking> */
class TrackingFactory extends Factory
{
    protected $model = Tracking::class;

    public function definition(): array
    {
        return ['shipment_id' => Shipment::factory(), 'external_id' => fake()->uuid(), 'status' => 'created', 'description' => fake()->sentence(), 'location' => fake()->city(), 'metadata' => [], 'occurred_at' => now()];
    }
}

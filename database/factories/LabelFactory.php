<?php

namespace Database\Factories;

use App\Models\Label;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Label> */
class LabelFactory extends Factory
{
    protected $model = Label::class;

    public function definition(): array
    {
        return ['shipment_id' => Shipment::factory(), 'format' => 'pdf', 'disk' => 'private', 'path' => 'labels/'.fake()->uuid().'.pdf', 'checksum' => fake()->sha256(), 'size_bytes' => fake()->numberBetween(1000, 500000), 'generated_at' => now()];
    }
}

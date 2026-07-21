<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebhookLog> */
class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        return ['shop_id' => Shop::factory(), 'provider' => 'shopify', 'event_id' => fake()->uuid(), 'topic' => 'orders/create', 'headers' => [], 'payload' => [], 'status' => 'received', 'attempts' => 0];
    }
}

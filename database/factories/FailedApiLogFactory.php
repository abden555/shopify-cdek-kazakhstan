<?php

namespace Database\Factories;

use App\Models\FailedApiLog;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FailedApiLog> */
class FailedApiLogFactory extends Factory
{
    protected $model = FailedApiLog::class;

    public function definition(): array
    {
        return ['shop_id' => Shop::factory(), 'service' => 'shopify', 'operation' => 'orders.sync', 'request_method' => 'GET', 'request_url' => 'https://example.test/api/orders', 'request_headers' => [], 'request_payload' => [], 'error_message' => fake()->sentence(), 'retry_count' => 0];
    }
}

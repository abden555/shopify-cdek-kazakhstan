<?php

namespace App\Domains\Orders\Events;

use App\Models\Shop;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ShopifyOrdersSynced
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Shop $shop, public readonly int $count) {}
}

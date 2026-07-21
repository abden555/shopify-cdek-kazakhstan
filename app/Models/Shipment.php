<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['shop_id', 'order_id', 'external_id', 'provider', 'tracking_number', 'status', 'service_code', 'shipping_cost', 'currency', 'recipient', 'origin_address', 'destination_address', 'metadata', 'shipped_at', 'delivered_at'];

    protected function casts(): array
    {
        return ['shipping_cost' => 'decimal:2', 'recipient' => 'array', 'origin_address' => 'array', 'destination_address' => 'array', 'metadata' => 'array', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(Tracking::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }
}

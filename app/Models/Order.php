<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['shop_id', 'external_id', 'order_number', 'email', 'currency', 'financial_status', 'fulfillment_status', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount', 'billing_address', 'shipping_address', 'metadata', 'ordered_at'];

    protected function casts(): array
    {
        return ['subtotal_amount' => 'decimal:2', 'discount_amount' => 'decimal:2', 'shipping_amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2', 'billing_address' => 'array', 'shipping_address' => 'array', 'metadata' => 'array', 'ordered_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}

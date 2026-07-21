<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['shipment_id', 'order_item_id', 'description', 'sku', 'quantity', 'weight_grams', 'declared_value'];

    protected function casts(): array
    {
        return ['declared_value' => 'decimal:2'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}

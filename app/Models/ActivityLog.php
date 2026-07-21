<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['shop_id', 'causer_user_id', 'event', 'description', 'subject_type', 'subject_id', 'properties', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

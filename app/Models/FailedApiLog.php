<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FailedApiLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['shop_id', 'service', 'operation', 'request_method', 'request_url', 'request_headers', 'request_payload', 'response_code', 'response_body', 'error_message', 'retry_count', 'last_retried_at'];

    protected $hidden = ['request_headers', 'request_payload', 'response_body'];

    protected function casts(): array
    {
        return ['request_headers' => 'encrypted:array', 'request_payload' => 'encrypted:array', 'last_retried_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopSession extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['shop_id', 'user_id', 'shopify_user_id', 'session_key', 'token_type', 'access_token', 'refresh_token', 'scopes', 'associated_user', 'expires_at', 'refresh_token_expires_at', 'last_used_at', 'deleted_at'];

    protected $hidden = ['access_token'];

    protected function casts(): array
    {
        return ['access_token' => 'encrypted', 'refresh_token' => 'encrypted', 'scopes' => 'array', 'associated_user' => 'array', 'expires_at' => 'datetime', 'refresh_token_expires_at' => 'datetime', 'last_used_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

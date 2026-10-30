<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSession extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['game_session_id', 'shop_id', 'expires_at', 'closed_at'];

    public function gameSession(): BelongsTo
    {

        return $this->belongsTo(GameSession::class);
    }

    public function shops(): BelongsTo
    {

        return $this->belongsTo(Shop::class);
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function hasExpired(): bool
    {
        return $this->closed_at !== null || $this->expires_at->isPast();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasUuids;

    public const INITIAL_CREDITS = 10000;

    protected $fillable = ['credits', 'expires_at'];

    protected const INTIAL_CREDITS = 1000;

    public function shopSessions(): HasMany
    {
        return $this->hasMany(ShopSession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function inventory(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'game_session_items')->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    // public function hasItems(): bool
    // {
    //     return $this->inventory()->exists();
    // }
}

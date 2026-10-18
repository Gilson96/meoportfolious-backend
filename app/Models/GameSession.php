<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    protected $fillable = ['equipped_item_id', 'credits', 'expires_at', 'created_at', 'update_at'];

    public function shopSessions(): HasMany
    {
        return $this->hasMany(ShopSession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Orders::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class);
    }

    public function equippedItem(): BelongsTo
    {
        return $this->belongsToOne(GameSessionItem::class);
    }
}

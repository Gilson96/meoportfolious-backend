<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSessionItem extends Model
{
    protected $fillable = ['game_session_id', 'item_id', 'created_at', 'update_at'];

    public function items(): HasMany {
        return $this->hasMany(Item::class);
    }

    public function gameSession(): BelongsToMany {
        return $this->belongsToMany(GameSession::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShopSession extends Model
{
   protected $fillable = ['game_session_id', 'shop_id', 'expires_at', 'closed_at'];

    public function gameSession(): BelongsToMany {
        
        return $this->belongsToMany(GameSession::class);
    }

    public function Shops(): BelongsToMany {
        
        return $this->belongsToMany(Shop::class);
    }


}

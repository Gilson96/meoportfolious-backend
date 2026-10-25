<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Item extends Model
{
    public $timestamps = false;

    protected $fillable = ['shop_id', 'external_variant_id', 'sprite_key', 'textureURL', 'atlasURL', 'active'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function inventory(): BelongsToMany
    {
        return $this->belongsToMany(GameSession::class, 'game_session_items')->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}

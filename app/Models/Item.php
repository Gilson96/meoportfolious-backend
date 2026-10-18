<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $fillable = ['shop_id', 'external_variant_id', 'name', 'type', 'sprite_key', 'active'];

    public function shop(): BelongsTo
    {
        return $this->belongsToOne(Shop::class);
    }

    public function gameSessionItem(): BelongsTo {

        return $this->belongsToOne(GameSessionItem::class);
    }

}

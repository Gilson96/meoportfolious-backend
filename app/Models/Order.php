<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = ['shop_id', 'external_order_id', 'game_session_id', 'shop_session_id', 'total', 'payload'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(ShopSession::class);
    }

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
 
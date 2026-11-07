<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopifyInstall extends Model
{
    protected $fillable = ['shop_domain', 'access_token', 'scopes'];

    protected $hidden = ['access_token'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
        ];
    }
}

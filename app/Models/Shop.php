<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shop extends Model
{
    public $timestamps = false;

    protected $fillable = ['config'];

    public function items(): HasMany
    {

        return $this->hasMany(Item::class);
    }

    public function shopSessions(): HasMany
    {

        return $this->hasMany(ShopSession::class);
    }

    public function shopifyInstall(): HasOne
    {
        return $this->hasOne(ShopifyInstall::class);
    }

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }
}

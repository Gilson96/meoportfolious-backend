<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'provider', 'config'];

    public function items(): HasMany {

        return $this->hasMany(Item::class);
    }

    public function shopSessions(): HasMany {

        return $this->hasMany(ShopSession::class);
    }

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

}

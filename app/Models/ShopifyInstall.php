<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyInstall extends Model
{
    protected $fillable = ['shop_domain', 'access_token', 'scopes'];

    protected $hidden = ['access_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
        ];
    }
}

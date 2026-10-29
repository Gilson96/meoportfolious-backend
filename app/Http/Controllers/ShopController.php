<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function create(Request $request, Shop $shop)
    {
        return $shop->create([
            'slug' => request('slug'),
            'provider' => request('provider'),
            'config' => request('config'),
        ]);
    }

    public function show(Request $request, Shop $shop)
    {
        return $shop;
    }

    public function update(Request $request, Shop $shop)
    {
        return $shop->update([
            'slug' => request('slug'),
            'provider' => request('provider'),
            'config' => request('config'),
        ]);
    }

    public function destroy(Shop $shop)
    {
        return $shop->delete();
    }
}

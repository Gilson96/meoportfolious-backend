<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Collection;

class ShopController extends Controller
{
    public function index(): Collection
    {
        return Shop::all();
    }

    public function show(Shop $shop): Shop
    {
        return $shop;
    }
}

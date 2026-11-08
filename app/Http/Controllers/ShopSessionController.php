<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\Shop;
use App\Models\ShopSession;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class ShopSessionController extends Controller
{
    public function create(Shop $shop, GameSession $game_session): ShopSession
    {
        return ShopSession::create([
            'game_session_id' => $game_session->id,
            'shop_id' => $shop->id,
            'expires_at' => now()->addHour(),
        ]);
    }

    public function show(ShopSession $shop_session): ShopSession
    {
        return $shop_session->load('shop');
    }

    public function destroy(ShopSession $shop_session): Response
    {
        $shop_session->update(['closed_at' => now()]);

        return response()->noContent();
    }

    public function shopSessionOrders(Request $request)
    {
        
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\Shop;
use App\Models\ShopSession;
use Illuminate\Http\Request;

class ShopSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function create(ShopSession $shop_session, Shop $shop): ShopSession
    {
        try {
            $shop_session = ShopSession::create([
                'game_session_id' => app(GameSession::class)->id,
                'shop_id' => $shop->id,
                'expires_at' => now()->addHour(),
            ]);

        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        return $shop_session;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ShopSession $shop_session)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShopSession $shop_session)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShopSession $shop_session)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShopSession $shop_session)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\Item;
use Illuminate\Http\Request;

class GameSessionController extends Controller
{
    public function create(GameSession $game_session): GameSession
    {
        try {
            $game_session = GameSession::create([
                'credits' => GameSession::INITIAL_CREDITS,
                'expires_at' => now()->addHour(),
            ]);

            if ($game_session) {
                $game_session->inventory()->attach(Item::find(1));
            }

        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        return $game_session;
    }

    public function show(GameSession $game_session): GameSession
    {
        return $game_session;
    }

    public function update(Request $request, GameSession $game_session): int
    {
        $validated = $request->validate([
            'credits' => ['required', 'integer', 'min:1', 'max:'.$game_session->credits],
        ]);

        $game_session->decrement('credits', $validated['credits']);

        return $game_session->credits;
    }

    // public function destroy(GameSession $game_session): bool
    // {
    //     if (! $game_session->hasExpired()) {
    //         return $game_session->delete();
    //     }

    //     return true;
    // }
}

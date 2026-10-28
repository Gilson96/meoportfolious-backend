<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
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
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        return $game_session;
    }

    public function update(Request $request, GameSession $game_session)
    {

        if ($request['credits'] && is_int($request['credits']) ?? $request['credits'] > 0) {
            $amount_spended = $request['credits'];

            return $game_session->credits = $game_session->credits - $amount_spended;
        }

        return $game_session->credits;
    }

    public function destroy(GameSession $game_session): bool
    {
        if (! $game_session->hasExpired()) {
            return $game_session->delete;
        }

        return true;
    }
}

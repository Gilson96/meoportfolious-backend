<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GameSessionController extends Controller
{
    public function create(Request $request): GameSession
    {
        $validated = $request->validate([
            'credits' => ['sometimes', 'integer', 'min:0'],
            'expires_at' => ['required', 'date', 'after:now'],
        ]);

        $game_session = GameSession::create($validated);

        return $game_session;
    }

    public function destroy(GameSession $game_session): bool
    {
        if (! $game_session->hasExpired()) {
            return false;
        }

        Session::forget('game_session_'.$game_session->id);

        return true;
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\GameSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResolveGameSession
{
    public const HEADER = 'X-Game-Session';

    public function handle(Request $request, Closure $next): Response
    {
        $gameSessionId = $request->header(self::HEADER);

        if (! is_string($gameSessionId) || ! Str::isUuid($gameSessionId)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Missing or invalid game session.');
        }

        $gameSession = GameSession::find($gameSessionId);

        if ($gameSession === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'Game session not found.');
        }

        if ($gameSession->hasExpired()) {
            abort(Response::HTTP_UNAUTHORIZED, 'Game session has expired.');
        }

        app()->instance(GameSession::class, $gameSession);

        return $next($request);
    }
}

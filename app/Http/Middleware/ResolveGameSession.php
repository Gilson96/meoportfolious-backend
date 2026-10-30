<?php

namespace App\Http\Middleware;

use App\Models\GameSession;

class ResolveGameSession extends ResolveSession
{
    public const HEADER = 'X-Game-Session';

    protected string $header = self::HEADER;

    protected string $modelClass = GameSession::class;

    protected string $label = 'game';
}

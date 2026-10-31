<?php

namespace App\Http\Middleware;

use App\Models\GameSession;
use App\Models\ShopSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveShopSession extends ResolveSession
{
    public const HEADER = 'X-Shop-Session';

    protected string $header = self::HEADER;

    protected string $modelClass = ShopSession::class;

    protected string $label = 'shop';

    public function handle(Request $request, Closure $next): Response
    {
        $gameSession = $this->findActiveSession($request, ResolveGameSession::HEADER, GameSession::class, 'game');
        $shopSession = $this->findActiveSession($request, $this->header, $this->modelClass, $this->label);

        if ($shopSession->game_session_id !== $gameSession->id) {
            abort(Response::HTTP_FORBIDDEN, 'Shop session does not belong to the game session.');
        }

        app()->instance(GameSession::class, $gameSession);
        app()->instance($this->modelClass, $shopSession);

        return $next($request);
    }
}

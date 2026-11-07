<?php

namespace App\Http\Middleware;

use App\Models\GameSession;
use App\Models\ShopifyInstall;
use App\Models\ShopSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveShopSession extends ResolveSession
{
    public const HEADER = 'X-Shop-Session';

    public const SHOPIFY_HEADER = 'X-Shopify-Access-Token';

    protected string $header = self::HEADER;

    protected string $modelClass = ShopSession::class;

    protected string $label = 'shop';

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->hasHeader(self::SHOPIFY_HEADER) ? self::SHOPIFY_HEADER : $this->header;

        $gameSession = $this->findActiveSession($request, ResolveGameSession::HEADER, GameSession::class, 'game');
        $shopSession = $this->findActiveSession($request, $header, $this->modelClass, $this->label);

        if ($shopSession->game_session_id !== $gameSession->id) {
            abort(Response::HTTP_FORBIDDEN, 'Shop session does not belong to the game session.');
        }

        if ($header === self::SHOPIFY_HEADER && ! ShopifyInstall::query()->where('shop_id', $shopSession->shop_id)->exists()) {
            abort(Response::HTTP_FORBIDDEN, 'Shop session is not linked with Shopify.');
        }

        app()->instance(GameSession::class, $gameSession);
        app()->instance($this->modelClass, $shopSession);

        return $next($request);
    }
}

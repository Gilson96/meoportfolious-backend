<?php

use App\Http\Middleware\ResolveGameSession;
use App\Http\Middleware\ResolveShopSession;
use App\Models\GameSession;
use App\Models\Shop;
use App\Models\ShopSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(ResolveShopSession::class)->get('/test-shop-session', fn () => 'ok');

    $this->gameSession = GameSession::create([
        'credits' => GameSession::INITIAL_CREDITS,
        'expires_at' => now()->addHour(),
    ]);

    $shop = Shop::create([
        'slug' => 'test-shop',
        'provider' => 'test',
        'config' => [],
    ]);

    $this->shopSession = ShopSession::create([
        'game_session_id' => $this->gameSession->id,
        'shop_id' => $shop->id,
        'expires_at' => now()->addHour(),
    ]);
});

test('shop session routes accept a shop session belonging to the game session', function () {
    $this->withHeaders([
        ResolveGameSession::HEADER => $this->gameSession->id,
        ResolveShopSession::HEADER => $this->shopSession->id,
    ])->getJson('/test-shop-session')->assertOk();
});

test('shop session routes reject a shop session that belongs to another game session', function () {
    $otherGameSession = GameSession::create([
        'credits' => GameSession::INITIAL_CREDITS,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders([
        ResolveGameSession::HEADER => $otherGameSession->id,
        ResolveShopSession::HEADER => $this->shopSession->id,
    ])->getJson('/test-shop-session')->assertForbidden();
});

test('shop session routes reject a request without a valid game session header', function (?string $gameSessionId) {
    $headers = [ResolveShopSession::HEADER => $this->shopSession->id];

    if ($gameSessionId !== null) {
        $headers[ResolveGameSession::HEADER] = $gameSessionId;
    }

    $this->withHeaders($headers)->getJson('/test-shop-session')->assertUnauthorized();
})->with([
    'missing header' => [null],
    'malformed id' => ['not-a-uuid'],
    'unknown id' => [fn () => (string) Str::uuid()],
]);

test('shop session routes reject an expired game session', function () {
    $this->gameSession->update(['expires_at' => now()->subMinute()]);

    $this->withHeaders([
        ResolveGameSession::HEADER => $this->gameSession->id,
        ResolveShopSession::HEADER => $this->shopSession->id,
    ])->getJson('/test-shop-session')->assertUnauthorized();
});

test('shop session routes reject an expired or closed shop session', function (string $state) {
    $this->shopSession->update(match ($state) {
        'expired' => ['expires_at' => now()->subMinute()],
        'closed' => ['closed_at' => now()->subMinute()],
    });

    $this->withHeaders([
        ResolveGameSession::HEADER => $this->gameSession->id,
        ResolveShopSession::HEADER => $this->shopSession->id,
    ])->getJson('/test-shop-session')->assertUnauthorized();
})->with(['expired', 'closed']);

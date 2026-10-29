<?php

use App\Http\Middleware\ResolveGameSession;
use App\Models\GameSession;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('show returns the game session with its inventory', function () {
    $gameSession = GameSession::create([
        'credits' => GameSession::INITIAL_CREDITS,
        'expires_at' => now()->addHour(),
    ]);

    $ownedItem = Item::create([
        'sprite_key' => 'hat',
        'textureURL' => 'https://example.com/hat.png',
        'atlasURL' => 'https://example.com/hat.json',
    ]);

    Item::create([
        'sprite_key' => 'boots',
        'textureURL' => 'https://example.com/boots.png',
        'atlasURL' => 'https://example.com/boots.json',
    ]);

    $gameSession->inventory()->attach($ownedItem);

    $this->withHeader(ResolveGameSession::HEADER, $gameSession->id)
        ->getJson('/api/game-session')
        ->assertOk()
        ->assertJsonPath('id', $gameSession->id)
        ->assertJsonCount(1, 'inventory')
        ->assertJsonPath('inventory.0.id', $ownedItem->id)
        ->assertJsonPath('inventory.0.sprite_key', 'hat');
});

test('update spends credits from the game session in the header', function () {
    $gameSession = GameSession::create([
        'credits' => GameSession::INITIAL_CREDITS,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeader(ResolveGameSession::HEADER, $gameSession->id)
        ->patchJson('/api/game-session', ['credits' => 500])
        ->assertOk();

    expect($gameSession->fresh()->credits)->toBe(GameSession::INITIAL_CREDITS - 500);
});

test('game session routes reject requests without a valid session header', function (?string $headerValue) {
    $request = $headerValue === null ? $this : $this->withHeader(ResolveGameSession::HEADER, $headerValue);

    $request->getJson('/api/game-session')->assertUnauthorized();
})->with([
    'missing header' => [null],
    'malformed id' => ['not-a-uuid'],
    'unknown id' => [fn () => (string) Str::uuid()],
]);

test('game session routes reject an expired session', function () {
    $gameSession = GameSession::create([
        'credits' => GameSession::INITIAL_CREDITS,
        'expires_at' => now()->subMinute(),
    ]);

    $this->withHeader(ResolveGameSession::HEADER, $gameSession->id)
        ->getJson('/api/game-session')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Game session has expired.');
});

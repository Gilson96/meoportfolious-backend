<?php

use App\Models\GameSession;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $this->getJson("/api/game-session/{$gameSession->id}")
        ->assertOk()
        ->assertJsonPath('id', $gameSession->id)
        ->assertJsonCount(1, 'inventory')
        ->assertJsonPath('inventory.0.id', $ownedItem->id)
        ->assertJsonPath('inventory.0.sprite_key', 'hat');
});

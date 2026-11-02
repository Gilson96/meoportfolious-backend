<?php

use App\Http\Middleware\ResolveGameSession;
use App\Http\Middleware\ResolveShopSession;
use App\Models\GameSession;
use App\Models\Shop;
use App\Models\ShopSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gameSession = GameSession::create([
        'credits' => GameSession::INITIAL_CREDITS,
        'expires_at' => now()->addHour(),
    ]);

    $this->shop = Shop::create([
        'config' => [
            'sprite_key' => 'test-shop',
            'textureURL' => 'https://example.com/test-shop.png',
            'atlasURL' => 'https://example.com/test-shop.json',
            'provider' => 'test',
            'currency' => 'credits',
        ],
    ]);
});

test('shops can be listed and shown', function () {
    $this->getJson('/api/shop')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.config.sprite_key', 'test-shop');

    $this->getJson("/api/shop/{$this->shop->id}")
        ->assertOk()
        ->assertJsonPath('config.currency', 'credits');
});

test('shop links and passwords are kept in the shop config', function () {
    $this->shop->update([
        'config' => [
            ...$this->shop->config,
            'shop_link' => 'https://example.myshopify.com/',
            'password' => 'secret-password',
        ],
    ]);

    expect($this->shop->refresh()->config)
        ->toMatchArray(['shop_link' => 'https://example.myshopify.com/', 'password' => 'secret-password']);

    $this->getJson("/api/shop/{$this->shop->id}")
        ->assertOk()
        ->assertJsonPath('config.shop_link', 'https://example.myshopify.com/');
});

test('shops cannot be created through the api', function () {
    $this->postJson('/api/shop', ['config' => ['sprite_key' => 'new-shop']])->assertMethodNotAllowed();

    expect(Shop::count())->toBe(1);
});

test('creating a shop session opens it for the shop and the game session in the header', function () {
    $response = $this->withHeader(ResolveGameSession::HEADER, $this->gameSession->id)
        ->postJson("/api/shop/{$this->shop->id}/session")
        ->assertCreated()
        ->assertJsonPath('game_session_id', $this->gameSession->id)
        ->assertJsonPath('shop_id', $this->shop->id);

    $shopSession = ShopSession::findOrFail($response->json('id'));

    expect($shopSession->hasExpired())->toBeFalse();
});

test('creating a shop session requires an active game session', function () {
    $this->postJson("/api/shop/{$this->shop->id}/session")->assertUnauthorized();

    expect(ShopSession::count())->toBe(0);
});

test('creating a shop session for an unknown shop returns not found', function () {
    $this->withHeader(ResolveGameSession::HEADER, $this->gameSession->id)
        ->postJson('/api/shop/999/session')
        ->assertNotFound();
});

test('the current shop session is shown with its shop', function () {
    $shopSession = ShopSession::create([
        'game_session_id' => $this->gameSession->id,
        'shop_id' => $this->shop->id,
        'expires_at' => now()->addHour(),
    ]);

    $this->withHeaders([
        ResolveGameSession::HEADER => $this->gameSession->id,
        ResolveShopSession::HEADER => $shopSession->id,
    ])->getJson('/api/shop-session')
        ->assertOk()
        ->assertJsonPath('id', $shopSession->id)
        ->assertJsonPath('shop.config.sprite_key', 'test-shop');
});

test('closing the current shop session marks it closed so it can no longer be used', function () {
    $shopSession = ShopSession::create([
        'game_session_id' => $this->gameSession->id,
        'shop_id' => $this->shop->id,
        'expires_at' => now()->addHour(),
    ]);

    $headers = [
        ResolveGameSession::HEADER => $this->gameSession->id,
        ResolveShopSession::HEADER => $shopSession->id,
    ];

    $this->withHeaders($headers)->deleteJson('/api/shop-session')->assertNoContent();

    expect($shopSession->refresh()->closed_at)->not->toBeNull()
        ->and($shopSession->hasExpired())->toBeTrue();

    $this->withHeaders($headers)->getJson('/api/shop-session')->assertUnauthorized();
});

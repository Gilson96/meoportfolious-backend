<?php

use App\Http\Controllers\ShopifyController;
use App\Models\ShopifyInstall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.shopify.client_id' => 'test-client-id',
        'services.shopify.secret' => 'test-secret',
    ]);
});

/**
 * @param  array<string, string>  $params
 * @return array<string, string>
 */
function signShopifyParams(array $params): array
{
    $message = collect($params)
        ->sortKeys()
        ->map(fn ($value, $key) => $key.'='.str_replace(['%', '&'], ['%25', '%26'], $value))
        ->implode('&');

    return [...$params, 'hmac' => hash_hmac('sha256', $message, 'test-secret')];
}

test('install redirects to the shop authorize page with a state nonce', function () {
    $response = $this->get('/shopify/auth?'.http_build_query(signShopifyParams([
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ])));

    $response->assertRedirectContains('https://example-store.myshopify.com/admin/oauth/authorize?');

    $query = [];
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query)
        ->toHaveKeys(['client_id', 'scope', 'redirect_uri', 'state'])
        ->and($query['client_id'])->toBe('test-client-id')
        ->and($query['state'])->toBe(session(ShopifyController::STATE_SESSION_KEY));
});

test('install rejects a request with a bad signature', function () {
    $params = signShopifyParams(['shop' => 'example-store.myshopify.com', 'timestamp' => '1700000000']);
    $params['hmac'] = 'tampered';

    $this->get('/shopify/auth?'.http_build_query($params))->assertForbidden();
});

test('install rejects a shop that is not a myshopify domain', function () {
    $this->get('/shopify/auth?'.http_build_query(signShopifyParams([
        'shop' => 'evil.example.com',
        'timestamp' => '1700000000',
    ])))->assertForbidden();
});

test('callback exchanges the code and stores the install with an encrypted token', function () {
    Http::fake([
        'example-store.myshopify.com/admin/oauth/access_token' => Http::response([
            'access_token' => 'shpat_plaintext_token',
            'scope' => 'read_products,write_products',
        ]),
    ]);

    $this->withSession([ShopifyController::STATE_SESSION_KEY => 'expected-state']);

    $this->get('/shopify/auth-redirect?'.http_build_query(signShopifyParams([
        'shop' => 'example-store.myshopify.com',
        'code' => 'auth-code',
        'state' => 'expected-state',
        'timestamp' => '1700000000',
    ])))->assertRedirect('https://example-store.myshopify.com/admin/apps/test-client-id');

    Http::assertSent(fn ($request) => $request->url() === 'https://example-store.myshopify.com/admin/oauth/access_token'
        && $request['code'] === 'auth-code'
        && $request['client_secret'] === 'test-secret');

    $install = ShopifyInstall::where('shop_domain', 'example-store.myshopify.com')->sole();

    expect($install->access_token)->toBe('shpat_plaintext_token')
        ->and($install->scopes)->toBe('read_products,write_products')
        ->and(DB::table('shopify_installs')->value('access_token'))->not->toBe('shpat_plaintext_token');
});

test('callback rejects a mismatched state and stores nothing', function () {
    Http::fake();

    $this->withSession([ShopifyController::STATE_SESSION_KEY => 'expected-state']);

    $this->get('/shopify/auth-redirect?'.http_build_query(signShopifyParams([
        'shop' => 'example-store.myshopify.com',
        'code' => 'auth-code',
        'state' => 'forged-state',
        'timestamp' => '1700000000',
    ])))->assertForbidden();

    Http::assertNothingSent();
    expect(ShopifyInstall::count())->toBe(0);
});

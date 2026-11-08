<?php

use App\Http\Controllers\ShopifyController;
use App\Models\Shop;
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

test('install shows the top product on the welcome page when the shop is already installed', function () {
    $shop = Shop::create(['config' => ['sprite_key' => 'shoes_shop']]);
    ShopifyInstall::factory()->for($shop)->create(['shop_domain' => 'shoes.myshopify.com']);

    Http::fake([
        'shoes.myshopify.com/*' => Http::response(['data' => ['products' => ['edges' => [
            ['node' => ['id' => 'gid://shopify/Product/123', 'title' => 'Red Sneakers']],
        ]]]]),
    ]);

    $this->get('/shopify/auth?'.http_build_query(signShopifyParams([
        'shop' => 'shoes.myshopify.com',
        'timestamp' => '1700000000',
    ])))
        ->assertOk()
        ->assertViewIs('welcome')
        ->assertViewHas('product', ['id' => 'gid://shopify/Product/123', 'title' => 'Red Sneakers'])
        ->assertSee('Red Sneakers');
});

test('install shows the welcome page without a product when the store has none', function () {
    $shop = Shop::create(['config' => ['sprite_key' => 'shoes_shop']]);
    ShopifyInstall::factory()->for($shop)->create(['shop_domain' => 'shoes.myshopify.com']);

    Http::fake([
        'shoes.myshopify.com/*' => Http::response(['data' => ['products' => ['edges' => []]]]),
    ]);

    $this->get('/shopify/auth?'.http_build_query(signShopifyParams([
        'shop' => 'shoes.myshopify.com',
        'timestamp' => '1700000000',
    ])))
        ->assertOk()
        ->assertViewHas('product', null)
        ->assertSee('No products found in shoes.myshopify.com.');
});

test('install shows the welcome page without calling shopify when the install has no shop', function () {
    ShopifyInstall::factory()->create(['shop_domain' => 'shoes.myshopify.com']);

    Http::fake();

    $this->get('/shopify/auth?'.http_build_query(signShopifyParams([
        'shop' => 'shoes.myshopify.com',
        'timestamp' => '1700000000',
    ])))
        ->assertOk()
        ->assertViewHas('product', null);

    Http::assertNothingSent();
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

test('callback associates the install with the shop whose sprite key matches the domain', function () {
    Http::fake([
        'clothes-7kwlamyy.myshopify.com/admin/oauth/access_token' => Http::response([
            'access_token' => 'shpat_plaintext_token',
            'scope' => 'read_products',
        ]),
    ]);

    $shoesShop = Shop::create(['config' => ['sprite_key' => 'shoes_shop']]);
    $clothesShop = Shop::create(['config' => [
        'sprite_key' => 'clothes_shop',
        'provider' => 'shopify',
        'shop_link' => 'https://old-store.myshopify.com/',
    ]]);

    $this->withSession([ShopifyController::STATE_SESSION_KEY => 'expected-state']);

    $this->get('/shopify/auth-redirect?'.http_build_query(signShopifyParams([
        'shop' => 'clothes-7kwlamyy.myshopify.com',
        'code' => 'auth-code',
        'state' => 'expected-state',
        'timestamp' => '1700000000',
    ])))->assertRedirect();

    expect(ShopifyInstall::where('shop_domain', 'clothes-7kwlamyy.myshopify.com')->sole()->shop_id)
        ->toBe($clothesShop->id)
        ->and($clothesShop->fresh()->config)->toBe([
            'sprite_key' => 'clothes_shop',
            'provider' => 'shopify',
            'shop_link' => 'https://clothes-7kwlamyy.myshopify.com/',
        ])
        ->and($shoesShop->fresh()->config)->toBe(['sprite_key' => 'shoes_shop']);
});

test('callback sets the shop link when the matched shop has none', function () {
    Http::fake([
        'clothes-7kwlamyy.myshopify.com/admin/oauth/access_token' => Http::response([
            'access_token' => 'shpat_plaintext_token',
            'scope' => 'read_products',
        ]),
    ]);

    $clothesShop = Shop::create(['config' => ['sprite_key' => 'clothes_shop']]);

    $this->withSession([ShopifyController::STATE_SESSION_KEY => 'expected-state']);

    $this->get('/shopify/auth-redirect?'.http_build_query(signShopifyParams([
        'shop' => 'clothes-7kwlamyy.myshopify.com',
        'code' => 'auth-code',
        'state' => 'expected-state',
        'timestamp' => '1700000000',
    ])))->assertRedirect();

    expect($clothesShop->fresh()->config['shop_link'])->toBe('https://clothes-7kwlamyy.myshopify.com/');
});

test('callback leaves the install unassociated when no shop matches the domain', function () {
    Http::fake([
        'example-store.myshopify.com/admin/oauth/access_token' => Http::response([
            'access_token' => 'shpat_plaintext_token',
            'scope' => 'read_products',
        ]),
    ]);

    Shop::create(['config' => ['sprite_key' => 'clothes_shop']]);

    $this->withSession([ShopifyController::STATE_SESSION_KEY => 'expected-state']);

    $this->get('/shopify/auth-redirect?'.http_build_query(signShopifyParams([
        'shop' => 'example-store.myshopify.com',
        'code' => 'auth-code',
        'state' => 'expected-state',
        'timestamp' => '1700000000',
    ])))->assertRedirect();

    expect(ShopifyInstall::where('shop_domain', 'example-store.myshopify.com')->sole()->shop_id)->toBeNull();
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

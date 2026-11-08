<?php

use App\Http\Controllers\ShopifyActionsController;
use App\Models\Shop;
use App\Models\ShopifyInstall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('graphQl posts the query to the shop admin api and returns the data', function () {
    $shop = Shop::create(['config' => ['sprite_key' => 'shoes_shop']]);
    ShopifyInstall::factory()->for($shop)->create([
        'shop_domain' => 'shoes.myshopify.com',
        'access_token' => 'shpat_secret',
    ]);

    Http::fake([
        'shoes.myshopify.com/*' => Http::response(['data' => ['shop' => ['name' => 'Shoes']]]),
    ]);

    $data = (new ShopifyActionsController)->graphQl('query { shop { name } }', $shop);

    expect($data)->toBe(['shop' => ['name' => 'Shoes']]);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://shoes.myshopify.com/admin/api/'.ShopifyActionsController::API_VERSION.'/graphql.json'
        && $request->method() === 'POST'
        && $request->hasHeader('X-Shopify-Access-Token', 'shpat_secret')
        && $request['query'] === 'query { shop { name } }');
});

test('graphQl returns null when shopify responds with graphql errors', function () {
    $shop = Shop::create(['config' => ['sprite_key' => 'shoes_shop']]);
    ShopifyInstall::factory()->for($shop)->create();

    Http::fake([
        '*' => Http::response(['errors' => [['message' => 'Field does not exist']]]),
    ]);

    expect((new ShopifyActionsController)->graphQl('query { nope }', $shop))->toBeNull();
});

test('graphQl returns null without calling shopify when the shop has no install', function () {
    $shop = Shop::create(['config' => ['sprite_key' => 'shoes_shop']]);

    Http::fake();

    expect((new ShopifyActionsController)->graphQl('query { shop { name } }', $shop))->toBeNull();

    Http::assertNothingSent();
});

test('getProduct returns the id and title of the product with the most inventory', function () {
    $shop = Shop::create(['config' => ['sprite_key' => 'shoes_shop']]);
    ShopifyInstall::factory()->for($shop)->create();

    Http::fake([
        '*' => Http::response(['data' => ['products' => ['edges' => [
            ['node' => ['id' => 'gid://shopify/Product/123', 'title' => 'Red Sneakers']],
        ]]]]),
    ]);

    expect((new ShopifyActionsController)->getProduct($shop))->toBe([
        'id' => 'gid://shopify/Product/123',
        'title' => 'Red Sneakers',
    ]);

    Http::assertSent(fn (Request $request) => str_contains($request['query'], 'sortKey: INVENTORY_TOTAL, reverse: true'));
});

test('getProduct throws when the store has no products', function () {
    $shop = Shop::create(['config' => ['sprite_key' => 'shoes_shop']]);
    ShopifyInstall::factory()->for($shop)->create();

    Http::fake([
        '*' => Http::response(['data' => ['products' => ['edges' => []]]]),
    ]);

    (new ShopifyActionsController)->getProduct($shop);
})->throws(RuntimeException::class, 'No products found in store');

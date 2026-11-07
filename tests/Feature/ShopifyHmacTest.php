<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.shopify.client_id' => 'test-client-id',
        'services.shopify.secret' => 'test-secret',
    ]);
});

/**
 * Signs params the way Shopify does: keys sorted, values escaped for & and % only, then HMAC-SHA256.
 *
 * @param  array<string, string>  $params
 * @return array<string, string>
 */
function signShopifyHmacParams(array $params, string $secret = 'test-secret'): array
{
    $message = collect($params)
        ->sortKeys()
        ->map(fn (string $value, string $key) => $key.'='.str_replace(['%', '&'], ['%25', '%26'], $value))
        ->implode('&');

    return [...$params, 'hmac' => hash_hmac('sha256', $message, $secret)];
}

test('a correctly signed request is validated', function () {
    $params = signShopifyHmacParams([
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ]);

    $this->get('/shopify/auth?'.http_build_query($params))->assertRedirectContains('https://example-store.myshopify.com/admin/oauth/authorize?');
});

test('a signed request whose host param has base64 padding is validated', function () {
    $params = signShopifyHmacParams([
        'host' => 'YWRtaW4=',
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ]);

    $this->get('/shopify/auth?'.http_build_query($params))->assertRedirectContains('https://example-store.myshopify.com/admin/oauth/authorize?');
});

test('a signed request whose host param has +, / and = characters is validated', function () {
    $params = signShopifyHmacParams([
        'host' => 'YWRtaW4+/x=',
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ]);

    $this->get('/shopify/auth?'.http_build_query($params))->assertRedirectContains('https://example-store.myshopify.com/admin/oauth/authorize?');
});

test('a request with a tampered hmac is not validated', function () {
    $params = signShopifyHmacParams([
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ]);
    $params['hmac'] = 'tampered';

    $this->get('/shopify/auth?'.http_build_query($params))->assertForbidden();
});

test('a request tampered after signing is not validated', function () {
    $params = signShopifyHmacParams([
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ]);
    $params['shop'] = 'attacker-store.myshopify.com';

    $this->get('/shopify/auth?'.http_build_query($params))->assertForbidden();
});

test('a request signed with a different secret is not validated', function () {
    $params = signShopifyHmacParams([
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ], 'wrong-secret');

    $this->get('/shopify/auth?'.http_build_query($params))->assertForbidden();
});

test('a request without an hmac is not validated', function () {
    $this->get('/shopify/auth?'.http_build_query([
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ]))->assertForbidden();
});

test('an unset secret never validates a request signed with an empty key', function () {
    config(['services.shopify.secret' => null]);

    $params = signShopifyHmacParams([
        'shop' => 'example-store.myshopify.com',
        'timestamp' => '1700000000',
    ], '');

    $this->get('/shopify/auth?'.http_build_query($params))->assertForbidden();
});

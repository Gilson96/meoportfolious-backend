<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyActionsController extends Controller
{
    public const API_VERSION = '2026-10';

    /**
     * Run a query or mutation against the shop's Shopify Admin GraphQL API.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>|null The response `data`, or null when the request failed.
     */
    public function graphQl(string $query, Shop $shop, array $variables = []): ?array
    {
        $install = $shop->shopifyInstall;

        if ($install === null) {
            Log::error('Shopify GraphQL request attempted for a shop without an install', ['shop_id' => $shop->id]);

            return null;
        }

        $url = 'https://'.$install->shop_domain.'/admin/api/'.self::API_VERSION.'/graphql.json';

        try {
            $response = Http::withHeaders(['X-Shopify-Access-Token' => $install->access_token])
                ->acceptJson()
                ->timeout(30)
                ->post($url, array_filter([
                    'query' => $query,
                    'variables' => $variables,
                ]));
        } catch (ConnectionException $exception) {
            Log::error('Shopify GraphQL request errored', [
                'shop_domain' => $install->shop_domain,
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }


        return $response->json('data');
    }
}

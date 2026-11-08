<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopifyInstall;
use App\Models\ShopSession;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ShopifyActionsController extends Controller
{
    public const WEBHOOK_TOPICS = ['ORDERS_CREATE'];

    /**
     * Run a query or mutation against the shop's Shopify Admin GraphQL API.
     *
     * @param  array<string, mixed>  $variables
     */
    public function graphQLRequestSetup(string $query): ?array
    {
        $shop_domain = request()->header('X-Shopify-Shop-Domain');
        $install = ShopifyInstall::query()->with('shop')->where('shop_domain', $shop_domain)->first();

        if ($install === null) {
            Log::error('Shopify GraphQL request attempted for a shop without an install', ['shop' => $shop_domain]);

            return null;
        }

        $url = 'https://'.$install->shop_domain.'/admin/api/2026-10'.'/graphql.json';

        try {
            $response = Http::withHeaders(['X-Shopify-Access-Token' => $install->access_token])
                ->acceptJson()
                ->post($url, array_filter([ 'query' => $query ]));
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

    public function setupWebhooks(bool $deleteFirst = false): void
    {
        if ($deleteFirst){  
            $this->deleteWebhooksByTopic(self::WEBHOOK_TOPICS);
        }

        $this->addWebhookByGraphQL('ORDERS_CREATE', route('shopify.order_create'));
    } 


    public function addWebhookByGraphQL(string $topic, string $callbackUrl): ?array
    {
        return $this->graphQLRequestSetup(<<<GRAPHQL
            mutation {
                webhookSubscriptionCreate(
                    topic: $topic
                    webhookSubscription: {
                        format: JSON,
                        callbackUrl: "$callbackUrl"
                    }
                ) {
                    userErrors {
                        field
                        message
                    }
                    webhookSubscription {
                        id
                    }
                }
            }
        GRAPHQL);
    }


    public function deleteWebhooksByTopic(array $topics): void
    {
        $existing = $this->graphQLRequestSetup('{ webhookSubscriptions(first: 20) { edges { node { id topic } } } }');

        foreach (data_get($existing, 'webhookSubscriptions.edges', []) as $edge) {
            if (in_array($edge['node']['topic'], $topics, true)) {
                $this->graphQLRequestSetup(<<<'GRAPHQL'
                    mutation webhookSubscriptionDelete($id: ID!) {
                    webhookSubscriptionDelete(id: $id) { deletedWebhookSubscriptionId userErrors { field message } }}
                    GRAPHQL);
            }
        }
    }
}
 
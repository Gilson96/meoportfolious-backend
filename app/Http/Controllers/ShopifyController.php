<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopifyInstall;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShopifyController extends Controller
{
    public const STATE_SESSION_KEY = 'shopify_oauth_state';

    public function getAuth(Request $request): RedirectResponse|View
    {
        $shopDomain = $request->query('shop');

        if (! $this->isValidShopDomain($shopDomain) || ! $this->checkHMAC($request)) {
            abort(403);
        }

        if (ShopifyInstall::query()->where('shop_domain', $shopDomain)->exists()) {
            return view('welcome', ['shopDomain' => $shopDomain]);
        }

        $state = Str::random(40);
        $request->session()->put(self::STATE_SESSION_KEY, $state);

        return redirect('https://'.$shopDomain.'/admin/oauth/authorize?'.http_build_query([
            'client_id' => config('services.shopify.client_id'),
            'scope' => config('services.shopify.scopes'),
            'redirect_uri' => route('shopify.auth-redirect'),
            'state' => $state,
        ]));
    }

    public function authRedirect(Request $request): RedirectResponse
    {
        $shopDomain = $request->query('shop');
        $expectedState = $request->session()->pull(self::STATE_SESSION_KEY);

        if (! $this->isValidShopDomain($shopDomain)
            || ! $this->checkHMAC($request)
            || ! is_string($expectedState)
            || ! hash_equals($expectedState, (string) $request->query('state', ''))) {
            abort(403);
        }

        $token = $this->accessToken($shopDomain, (string) $request->query('code'));

        if ($token === null || ! isset($token->access_token)) {
            abort(403);
        }

        /** @var ShopifyInstall $install */
        $install = ShopifyInstall::updateOrCreate(
            ['shop_domain' => $shopDomain],
            ['access_token' => $token->access_token, 'scopes' => $token->scope ?? ''],
        );

        $shop = $this->findShopForDomain($shopDomain);

        if ($shop !== null) {
            $install->shop()->associate($shop)->save();
            $shop->config = [...$shop->config, 'shop_link' => 'https://'.$shopDomain.'/'];
            $shop->save();
        }

        return redirect($this->shopAppUrl($shopDomain));
    }

    protected function findShopForDomain(string $shopDomain): ?Shop
    {
        $shopName = Str::before(Str::before($shopDomain, '.'), '-');

        return Shop::query()
            ->where('config->sprite_key', 'like', '%'.$shopName.'%')
            ->orderBy('id')
            ->first();
    }

    protected function shopAppUrl(string $shopDomain): string
    {
        return 'https://'.$shopDomain.'/admin/apps/'.config('services.shopify.client_id');
    }

    protected function accessToken(string $shopDomain, string $code): ?\stdClass
    {
        $response = Http::asForm()->post("https://{$shopDomain}/admin/oauth/access_token", [
            'client_id' => config('services.shopify.client_id'),
            'client_secret' => config('services.shopify.secret'),
            'code' => $code,
        ]);

        return $response->successful() ? $response->object() : null;
    }

    protected function isValidShopDomain(mixed $shop): bool
    {
        return is_string($shop) && preg_match('/^[a-z0-9][a-z0-9\-]*\.myshopify\.com$/i', $shop) === 1;
    }

    protected function checkHMAC(Request $request): bool
    {
        if (config('services.shopify.secret') === null) {
            return false;
        }

        $query = $request->query();
        $hmac = (string) $request->query('hmac', '');

        unset($query['hmac'], $query['signature']);
        ksort($query);

        $message = collect($query)
            ->map(fn ($value, $key) => $key.'='.str_replace(['%', '&'], ['%25', '%26'], (string) $value))
            ->implode('&');

        $expectedHmac = hash_hmac('sha256', $message, (string) config('services.shopify.secret'));

        return hash_equals($expectedHmac, $hmac);
    }
}

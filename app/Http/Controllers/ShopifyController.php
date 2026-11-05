<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShopifyController extends Controller
{

    public function getAuth(Request $request)
    {
        $shop = $request->query('shop');
        $hmac = $request->query('hmac');
        $timestamp = $request->query('timestamp');
        
        // if(!$this->isValidShopDomain($shop)) {
        //     return 403;
        // }

        echo $shop;
    }

    protected function isValidShopDomain($shop): bool 
    {
        return is_string($shop) && preg_match('/^[a-z0-9][a-z0-9\-]*\.myshopify\.com$/i', $shop) === 1;
    }


}

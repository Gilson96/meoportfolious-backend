<?php

use App\Http\Controllers\ShopifyController;
use Illuminate\Support\Facades\Route;

Route::get('shopify/auth', [ShopifyController::class, 'getAuth'])->name('shopify.auth');
Route::get('shopify/auth-redirect', [ShopifyController::class, 'authRedirect'])->name('shopify.auth-redirect');

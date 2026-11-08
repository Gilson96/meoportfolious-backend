<?php

use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopifyActionsController;
use App\Http\Controllers\ShopSessionController;
use App\Http\Middleware\ResolveGameSession;
use App\Http\Middleware\ResolveShopSession;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyController;

Route::prefix('shop')->group(function () {
    Route::get('/', [ShopController::class, 'index']);
    Route::get('/{shop}', [ShopController::class, 'show']);

    Route::post('/{shop}/session', [ShopSessionController::class, 'create'])->middleware(ResolveGameSession::class);
});

Route::prefix('shop-session')->controller(ShopSessionController::class)->group(function () {
    Route::middleware(ResolveShopSession::class)->group(function () {
        Route::get('/', 'show');
        Route::delete('/', 'destroy');
    });
});

Route::post('shopify/order_create', [OrderController::class, 'shopifyOrder'])
    ->name('shopify.order_create');

Route::prefix('game-session')->controller(GameSessionController::class)->group(function () {
    Route::post('/', 'create');

    Route::middleware(ResolveGameSession::class)->group(function () {
        Route::get('/', 'show');
        Route::patch('/', 'update');
    });
});

Route::get('/item/{item}', [ItemController::class, 'show']);

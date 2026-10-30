<?php

use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ShopSessionController;
use App\Http\Middleware\ResolveGameSession;
use App\Http\Middleware\ResolveShopSession;
use Illuminate\Support\Facades\Route;

Route::prefix('shop-session')->controller(ShopSessionController::class)->group(function () {
    Route::post('/shop', [ShopSessionController::class, 'create']);

    Route::middleware(ResolveShopSession::class)->group(function () {
        Route::get('/shop', [ShopSessionController::class, 'index']);
        Route::get('/shop/{shop}', [ShopSessionController::class, 'show']);
        Route::patch('/shop/{shop}', [ShopSessionController::class, 'update']);
    });
});

Route::prefix('game-session')->controller(GameSessionController::class)->group(function () {
    Route::post('/', 'create');

    Route::middleware(ResolveGameSession::class)->group(function () {
        Route::get('/', 'show');
        Route::patch('/', 'update');
    });
});

Route::get('/item/{item}', [ItemController::class, 'show']);

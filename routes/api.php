<?php

use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/shop', [ShopController::class, 'index']);
Route::post('/shop', [ShopController::class, 'create']);
Route::get('/shop/{shop}', [ShopController::class, 'show']);
Route::patch('/shop/{shop}', [ShopController::class, 'update']);

Route::prefix('game-session')->controller(GameSessionController::class)->group(function () {
    Route::post('/', 'create');
    Route::get('/{game_session}', 'show');
    Route::patch('/{game_session}', 'update');
});

Route::get('/item/{item}', [ItemController::class, 'show']);

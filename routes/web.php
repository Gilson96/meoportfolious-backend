<?php

use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/shop', [ShopController::class, 'index']);
Route::post('/shop', [ShopController::class, 'create']);
Route::get('/shop/{shop}', [ShopController::class, 'show']);
Route::patch('/shop/{shop}', [ShopController::class, 'update']);

Route::post('/game-session', [GameSessionController::class, 'create']);

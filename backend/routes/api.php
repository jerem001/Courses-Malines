<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\FavoriteStoreController;
use App\Http\Controllers\Api\V1\PreferenceController;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('brands', [CatalogController::class, 'brands']);
        Route::get('stores', [CatalogController::class, 'stores']);

        Route::get('preferences', [PreferenceController::class, 'show']);
        Route::put('preferences', [PreferenceController::class, 'update']);

        Route::get('favorite-stores', [FavoriteStoreController::class, 'index']);
        Route::post('favorite-stores', [FavoriteStoreController::class, 'store']);
        Route::delete('favorite-stores/{store}', [FavoriteStoreController::class, 'destroy']);
    });
});

<?php

use App\Http\Controllers\Api\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\OfferTrackingController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->scopeBindings()->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::get('/lookups', LookupController::class);

    Route::prefix('profile')->group(function (): void {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
    });

    Route::prefix('offers')->group(function (): void {
        Route::get('/', [OfferController::class, 'index']);
        Route::post('/', [OfferController::class, 'store']);
        Route::get('/{oferta}', [OfferController::class, 'show']);
        Route::put('/{oferta}', [OfferController::class, 'update']);
        Route::post('/{oferta}/decision', [OfferController::class, 'respond']);
        Route::get('/{oferta}/tracking', [OfferTrackingController::class, 'show']);
        Route::post('/{oferta}/tracking', [OfferTrackingController::class, 'update']);
    });

    Route::prefix('admin')->group(function (): void {
        Route::apiResource('users', AdminUserController::class)->except(['create', 'edit']);
        Route::apiResource('clients', AdminClientController::class)->except(['create', 'edit']);
    });
});

<?php

use App\Http\Controllers\Api\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Api\Admin\AdminLookupController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\OfferController;
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
        Route::get('/dni', [ProfileController::class, 'dni']);
        Route::post('/dni', [ProfileController::class, 'uploadDni']);
    });

    Route::prefix('offers')->group(function (): void {
        Route::get('/', [OfferController::class, 'index']);
        Route::post('/', [OfferController::class, 'store']);
        Route::get('/{oferta}', [OfferController::class, 'show']);
        Route::put('/{oferta}', [OfferController::class, 'update']);
        Route::get('/{oferta}/tracking', [OfferController::class, 'trackingOptions']);
        Route::get('/{oferta}/tracking/current', [OfferController::class, 'trackingStep']);
        Route::patch('/{oferta}/tracking', [OfferController::class, 'updateTrackingStep']);
        Route::post('/{oferta}/decision', [OfferController::class, 'respond']);
        Route::get('/{oferta}/documents', [OfferController::class, 'documents']);
        Route::post('/{oferta}/documents', [OfferController::class, 'uploadDocuments']);
    });

    Route::prefix('admin')->group(function (): void {
        Route::get('rols', [AdminLookupController::class, 'roles']);
        Route::get('clients/options', [AdminLookupController::class, 'clients']);
        Route::apiResource('users', AdminUserController::class)
            ->parameters(['users' => 'usuari']);
        Route::apiResource('clients', AdminClientController::class)->except(['create', 'edit']);
    });
});

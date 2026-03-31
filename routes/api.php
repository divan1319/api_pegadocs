<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:login')->group(function (): void {
        Route::post('/auth/login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware('throttle:register')->group(function (): void {
        Route::post('/auth/register', [RegisteredUserController::class, 'store']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/user', function (Request $request) {
            return new UserResource($request->user());
        });
        Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);
    });
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiDispatcherController;

Route::post('/v1/login', [AuthController::class, 'login']);


Route::middleware('auth:api')->group(function () {

    Route::get('/v1/user', [AuthController::class, 'getUser']);

    Route::post('/v1/logout', [AuthController::class, 'logout']);

    Route::post('/v1/refresh', [AuthController::class, 'refresh']);

    Route::prefix('v1')->group(function () {
        Route::post('/{module}/{action}', [ApiDispatcherController::class, 'handle']);
    });
});




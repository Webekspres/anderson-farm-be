<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ProfileController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


// Laravel otomatis menambahkan prefix '/api' di depan route ini
Route::get('/check', [CheckController::class, 'index']);

Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rute Terlindungi (Wajib Bawa Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);


        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{server_id}', [UserController::class, 'update']);
        Route::delete('/users/{server_id}', [UserController::class, 'destroy']);

        // Profile
        Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
        // Area CRUD
        Route::get('/areas', [\App\Http\Controllers\Api\V1\AreaController::class, 'index']);
        Route::post('/areas', [\App\Http\Controllers\Api\V1\AreaController::class, 'store']);
        Route::patch('/areas/{id}', [\App\Http\Controllers\Api\V1\AreaController::class, 'update']);
        Route::delete('/areas/{id}', [\App\Http\Controllers\Api\V1\AreaController::class, 'destroy']);
    });
});

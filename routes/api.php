<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    
    // Public Routes (Tidak butuh token)
    // Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected Routes (Wajib bawa Bearer Token Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::get('/users', [UserController::class, 'index']);
        
        // Nanti kamu bisa tambah route lain di sini:
        // Route::post('/users', [UserController::class, 'store']);
        // Route::post('/auth/logout', [AuthController::class, 'logout']);
    });
});
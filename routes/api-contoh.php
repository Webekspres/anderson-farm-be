<?php

use App\Http\Controllers\UserController;

// Middleware 'auth:sanctum' memastikan hanya user yang sudah login (misal: Admin) yang bisa mengakses ini
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
});
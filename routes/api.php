<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FormConfigController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\TransactionCategoryController;
use App\Http\Controllers\Api\V1\OvkItemController;

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

        // Coop endpoints
        Route::apiResource('coops', App\Http\Controllers\Api\V1\CoopController::class);
        // EquipmentType endpoints
        Route::apiResource('equipment-types', App\Http\Controllers\Api\V1\EquipmentTypeController::class);
        // Area CRUD
        Route::get('/areas', [\App\Http\Controllers\Api\V1\AreaController::class, 'index']);
        Route::post('/areas', [\App\Http\Controllers\Api\V1\AreaController::class, 'store']);
        Route::patch('/areas/{id}', [\App\Http\Controllers\Api\V1\AreaController::class, 'update']);
        Route::delete('/areas/{id}', [\App\Http\Controllers\Api\V1\AreaController::class, 'destroy']);
        // Farm CRUD
        Route::get('/farms', [\App\Http\Controllers\Api\V1\FarmController::class, 'index']);
        Route::post('/farms', [\App\Http\Controllers\Api\V1\FarmController::class, 'store']);
        Route::patch('/farms/{id}', [\App\Http\Controllers\Api\V1\FarmController::class, 'update']);
        Route::delete('/farms/{id}', [\App\Http\Controllers\Api\V1\FarmController::class, 'destroy']);
        // TransactionCategory CRUD
        Route::apiResource('transaction-categories', TransactionCategoryController::class);
        // OvkItem CRUD
        Route::apiResource('ovk-items', OvkItemController::class);
        // EducationArticle CRUD
        Route::apiResource('education-articles', App\Http\Controllers\Api\V1\EducationArticleController::class)->only(['store', 'update', 'destroy']);
        // PriceReference CRUD
        Route::apiResource('price-references', App\Http\Controllers\Api\V1\PriceReferenceController::class)->only(['store', 'update', 'destroy']);
        // FormConfig CRUD
        Route::apiResource('form-configs', FormConfigController::class);
    });
});

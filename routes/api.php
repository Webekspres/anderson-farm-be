<?php

use App\Http\Controllers\Api\V1\SyncEquipmentTypeFormConfigController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FormConfigController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\TransactionCategoryController;
use App\Http\Controllers\Api\V1\OvkItemController;
use App\Http\Controllers\Api\V1\CoopEquipmentController;


// Laravel otomatis menambahkan prefix '/api' di depan route ini
Route::get('/check', [CheckController::class, 'index']);

Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rute Terlindungi (Wajib Bawa Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);


        Route::apiResource('users', UserController::class)->except(['show']);

        // Profile
        Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

        // Coop endpoints
        Route::apiResource('coops', App\Http\Controllers\Api\V1\CoopController::class);

        // EquipmentType endpoints
        Route::apiResource('equipment-types', App\Http\Controllers\Api\V1\EquipmentTypeController::class);
        Route::post('/equipment-types/{equipment_type}/form-configs', [SyncEquipmentTypeFormConfigController::class, '__invoke']);

        Route::apiResource('areas', App\Http\Controllers\Api\V1\AreaController::class)->except(['show']);

        // ReportTemplate CRUD
        Route::apiResource('report-templates', App\Http\Controllers\Api\V1\ReportTemplateController::class);
        // Farm CRUD
        Route::apiResource('farms', App\Http\Controllers\Api\V1\FarmController::class);

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

        // Upload endpoints
        Route::post('/uploads', [\App\Http\Controllers\Api\V1\UploadController::class, 'store']);
        Route::delete('/uploads', [\App\Http\Controllers\Api\V1\UploadController::class, 'destroy']);

        // CoopDocument nested endpoints
        Route::get('/coops/{coop}/documents', [\App\Http\Controllers\Api\V1\CoopDocumentController::class, 'index']);
        Route::post('/coops/{coop}/documents', [\App\Http\Controllers\Api\V1\CoopDocumentController::class, 'store']);
        Route::delete('/coops/{coop}/documents/{document}', [\App\Http\Controllers\Api\V1\CoopDocumentController::class, 'destroy']);

        // CoopEquipment nested endpoints
        Route::get('/coops/{coop}/equipments', [CoopEquipmentController::class, 'index']);
        Route::post('/coops/{coop}/equipments', [CoopEquipmentController::class, 'store']);
        Route::delete('/coops/{coop}/equipments/{equipment}', [CoopEquipmentController::class, 'destroy']);

        // Bulk assignment pekerja ke kandang
        Route::post('/coops/{coop}/user-assignments', [\App\Http\Controllers\Api\V1\CoopUserAssignmentController::class, 'sync']);

        // Bulk sync form assignments ke alat di kandang
        Route::post('/coops/{coop}/form-assignments', [\App\Http\Controllers\Api\V1\SyncCoopFormAssignmentController::class, '__invoke']);
    });
});

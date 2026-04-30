<?php

use App\Http\Controllers\Api\CheckController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChecklistTaskController;
use App\Http\Controllers\Api\V1\ContractAbkController;
use App\Http\Controllers\Api\V1\CoopDocumentController;
use App\Http\Controllers\Api\V1\CoopEquipmentController;
use App\Http\Controllers\Api\V1\CoopUserAssignmentController;
use App\Http\Controllers\Api\V1\FormConfigController;
use App\Http\Controllers\Api\V1\OvkItemController;
use App\Http\Controllers\Api\V1\PeriodController;
use App\Http\Controllers\Api\V1\PeriodActionController;
use App\Http\Controllers\Api\V1\RhppDocumentController;
use App\Http\Controllers\Api\V1\RhppActionController;
use App\Http\Controllers\Api\V1\RhppSyncController;
use App\Http\Controllers\Api\V1\PeriodDocumentController;
use App\Http\Controllers\Api\V1\PeriodFormAssignmentController;
use App\Http\Controllers\Api\V1\PeriodInvestorController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SyncCoopFormAssignmentController;
use App\Http\Controllers\Api\V1\SyncEquipmentTypeFormConfigController;
use App\Http\Controllers\Api\V1\TransactionCategoryController;
use App\Http\Controllers\Api\V1\DailyActivitySyncController;
use App\Http\Controllers\Api\V1\FinanceSyncController;
use App\Http\Controllers\Api\V1\MaintenanceSyncController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

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
        Route::post('/uploads', [UploadController::class, 'store']);
        Route::delete('/uploads', [UploadController::class, 'destroy']);

        // CoopDocument nested endpoints
        Route::get('/coops/{coop}/documents', [CoopDocumentController::class, 'index']);
        Route::post('/coops/{coop}/documents', [CoopDocumentController::class, 'store']);
        Route::delete('/coops/{coop}/documents/{document}', [CoopDocumentController::class, 'destroy']);

        // CoopEquipment nested endpoints
        Route::get('/coops/{coop}/equipments', [CoopEquipmentController::class, 'index']);
        Route::post('/coops/{coop}/equipments', [CoopEquipmentController::class, 'store']);
        Route::delete('/coops/{coop}/equipments/{equipment}', [CoopEquipmentController::class, 'destroy']);

        // Bulk assignment pekerja ke kandang
        Route::post('/coops/{coop}/user-assignments', [CoopUserAssignmentController::class, 'sync']);
        Route::post('/coops/{coop}/form-assignments', SyncCoopFormAssignmentController::class);




        // Production Period endpoints
        Route::post('/periods', [PeriodController::class, 'store']);
        Route::prefix('periods/{period_id}')->group(function () {
            Route::patch('/', [PeriodController::class, 'update']);
            Route::post('/investors', [PeriodInvestorController::class, 'sync']);
            // Bulk sync & get form assignments ke periode
            Route::get('/form-assignments', [PeriodFormAssignmentController::class, 'index']);
            Route::post('/form-assignments', [PeriodFormAssignmentController::class, 'sync']);

            Route::get('/checklist-tasks', [ChecklistTaskController::class, 'index']);
            Route::post('/checklist-tasks', [ChecklistTaskController::class, 'sync']);

            Route::get('/contracts', [ContractAbkController::class, 'index']);
            Route::post('/contracts', [ContractAbkController::class, 'store']);

            Route::get('/documents', [PeriodDocumentController::class, 'index']);
            Route::post('/documents', [PeriodDocumentController::class, 'store']);
            // Period action endpoints
            Route::post('/close', [PeriodActionController::class, 'close']);
            Route::post('/rhpp-documents', [RhppDocumentController::class, 'store']);
        });

        Route::post('/rhpps/{period_id}/publish', [RhppActionController::class, 'publish']);

        // Sync endpoints (Offline-First)
        Route::prefix('sync')->group(function () {
            Route::get('/master-data', [\App\Http\Controllers\Api\V1\MasterDataSyncController::class, 'index']);
            Route::get('/periods', [\App\Http\Controllers\Api\V1\PeriodSyncController::class, 'index']);
            Route::get('/daily-activities', [DailyActivitySyncController::class, 'index']);
            Route::post('/daily-activities', [DailyActivitySyncController::class, 'store']);
            Route::get('/education', [\App\Http\Controllers\Api\V1\EducationSyncController::class, 'index']);
            Route::get('/finances', [FinanceSyncController::class, 'index']);
            Route::post('/finances', [FinanceSyncController::class, 'store']);
            Route::get('/maintenances', [MaintenanceSyncController::class, 'index']);
            Route::post('/maintenances', [MaintenanceSyncController::class, 'store']);
            Route::get('/rhpps', [RhppSyncController::class, 'index']);
        });
    });

    Route::get('/contracts/{contract}', [ContractAbkController::class, 'show']);
    Route::post('/contracts/{contract}', [ContractAbkController::class, 'accept']); // Method POST untuk menyetujui
    Route::delete('/contracts/{contract}', [ContractAbkController::class, 'destroy']);
});

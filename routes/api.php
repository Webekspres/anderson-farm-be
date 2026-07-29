<?php

use App\Http\Controllers\Api\CheckController;
use App\Http\Controllers\Api\V1\ActivityLogSyncController;
use App\Http\Controllers\Api\V1\AreaController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BopExportController;
use App\Http\Controllers\Api\V1\ChecklistTaskController;
use App\Http\Controllers\Api\V1\ContractAbkController;
use App\Http\Controllers\Api\V1\CoopController;
use App\Http\Controllers\Api\V1\CoopDocumentController;
use App\Http\Controllers\Api\V1\CoopEquipmentController;
use App\Http\Controllers\Api\V1\CoopFloorController;
use App\Http\Controllers\Api\V1\CoopUserAssignmentController;
use App\Http\Controllers\Api\V1\DailyActivityApprovalController;
use App\Http\Controllers\Api\V1\DailyActivitySyncController;
use App\Http\Controllers\Api\V1\EducationArticleController;
use App\Http\Controllers\Api\V1\EducationSyncController;
use App\Http\Controllers\Api\V1\EquipmentTypeController;
use App\Http\Controllers\Api\V1\EquipmentTypeFormConfigController;
use App\Http\Controllers\Api\V1\EvaluationExportController;
use App\Http\Controllers\Api\V1\FarmController;
use App\Http\Controllers\Api\V1\FcmTokenController;
use App\Http\Controllers\Api\V1\FinanceSyncController;
use App\Http\Controllers\Api\V1\FormConfigController;
use App\Http\Controllers\Api\V1\HarvestExportController;
use App\Http\Controllers\Api\V1\InvestorDashboardController;
use App\Http\Controllers\Api\V1\MaintenanceSyncController;
use App\Http\Controllers\Api\V1\MasterDataSyncController;
use App\Http\Controllers\Api\V1\MonitoringController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OvkExportController;
use App\Http\Controllers\Api\V1\OvkItemController;
use App\Http\Controllers\Api\V1\PeriodActionController;
use App\Http\Controllers\Api\V1\PeriodChecklistTaskController;
use App\Http\Controllers\Api\V1\PeriodController;
use App\Http\Controllers\Api\V1\PeriodDocumentController;
use App\Http\Controllers\Api\V1\PeriodFormAssignmentController;
use App\Http\Controllers\Api\V1\PeriodInvestorController;
use App\Http\Controllers\Api\V1\PeriodSyncController;
use App\Http\Controllers\Api\V1\PriceReferenceController;
use App\Http\Controllers\Api\V1\ReportTemplateController;
use App\Http\Controllers\Api\V1\RhppActionController;
use App\Http\Controllers\Api\V1\RhppDocumentController;
use App\Http\Controllers\Api\V1\RhppExportController;
use App\Http\Controllers\Api\V1\RhppSyncController;
use App\Http\Controllers\Api\V1\SalaryExportController;
use App\Http\Controllers\Api\V1\SalaryImportController;
use App\Http\Controllers\Api\V1\SyncCoopFormAssignmentController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\TransactionCategoryController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// Laravel otomatis menambahkan prefix '/api' di depan route ini
Route::get('/check', [CheckController::class, 'index']);

Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:10,1');

    Route::get('/system/check-version', [SystemController::class, 'checkVersion']);

    // Rute Terlindungi (Wajib Bawa Token)
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/fcm-token', FcmTokenController::class);

        Route::apiResource('users', UserController::class)->except(['update']);
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');

        // Coop endpoints
        Route::apiResource('coops', CoopController::class)->except(['update']);
        Route::patch('coops/{coop}', [CoopController::class, 'update'])->name('coops.update');

        Route::apiResource('coop-floors', CoopFloorController::class)->except(['update']);
        Route::patch('coop-floors/{coop_floor}', [CoopFloorController::class, 'update'])->name('coop-floors.update');

        // EquipmentType endpoints
        Route::apiResource('equipment-types', EquipmentTypeController::class)->except(['update']);
        Route::patch('equipment-types/{equipment_type}', [EquipmentTypeController::class, 'update'])->name('equipment-types.update');
        Route::get('/equipment-types/{equipment_type}/form-configs', [EquipmentTypeFormConfigController::class, 'getFormConfigs']);
        Route::post('/equipment-types/{equipment_type}/form-configs', [EquipmentTypeFormConfigController::class, 'syncFormConfigs']);

        Route::apiResource('areas', AreaController::class)->except(['update']);
        Route::patch('areas/{area}', [AreaController::class, 'update'])->name('areas.update');

        // ReportTemplate CRUD
        Route::apiResource('report-templates', ReportTemplateController::class)->except(['update']);
        Route::patch('report-templates/{report_template}', [ReportTemplateController::class, 'update'])->name('report-templates.update');
        // Farm CRUD
        Route::apiResource('farms', FarmController::class)->except(['update']);
        Route::patch('farms/{farm}', [FarmController::class, 'update'])->name('farms.update');

        // TransactionCategory CRUD
        Route::apiResource('transaction-categories', TransactionCategoryController::class)->except(['update']);
        Route::patch('transaction-categories/{transaction_category}', [TransactionCategoryController::class, 'update'])->name('transaction-categories.update');
        // OvkItem CRUD
        Route::apiResource('ovk-items', OvkItemController::class)->except(['update']);
        Route::patch('ovk-items/{ovk_item}', [OvkItemController::class, 'update'])->name('ovk-items.update');
        // EducationArticle CRUD
        Route::apiResource('education-articles', EducationArticleController::class)->only(['store', 'destroy']);
        Route::patch('education-articles/{education_article}', [EducationArticleController::class, 'update'])->name('education-articles.update');
        // PriceReference CRUD
        Route::apiResource('price-references', PriceReferenceController::class)->only(['store', 'destroy']);
        Route::patch('price-references/{price_reference}', [PriceReferenceController::class, 'update'])->name('price-references.update');
        // FormConfig CRUD
        Route::apiResource('form-configs', FormConfigController::class)->except(['update']);
        Route::patch('form-configs/{form_config}', [FormConfigController::class, 'update'])->name('form-configs.update');

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
        Route::get('/coops/{coop}/user-assignments', [CoopUserAssignmentController::class, 'index']);
        Route::post('/coops/{coop}/user-assignments', [CoopUserAssignmentController::class, 'sync']);
        Route::post('/coops/{coop}/form-assignments', SyncCoopFormAssignmentController::class);

        // Production Period endpoints
        Route::post('/periods', [PeriodController::class, 'store']);
        Route::prefix('periods/{period_id}')->group(function () {
            Route::patch('/', [PeriodController::class, 'update']);
            Route::get('/investors', [PeriodInvestorController::class, 'index']);
            Route::post('/investors', [PeriodInvestorController::class, 'sync']);
            // Bulk sync & get form assignments ke periode
            Route::get('/form-assignments', [PeriodFormAssignmentController::class, 'index']);
            Route::post('/form-assignments', [PeriodFormAssignmentController::class, 'sync']);

            Route::get('/checklist-tasks', [PeriodChecklistTaskController::class, 'index']);
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

        // Export endpoints
        Route::get('/export/ovk-usages', [OvkExportController::class, 'show']);
        Route::get('/export/bop-details', [BopExportController::class, 'show']);
        Route::get('/export/harvests', [HarvestExportController::class, 'show']);
        Route::get('/export/evaluations', [EvaluationExportController::class, 'show']);
        Route::get('/export/template-salary', [SalaryExportController::class, 'show']);
        Route::get('/export/rhpp', [RhppExportController::class, 'show']);
        Route::post('/import/salary', [SalaryImportController::class, 'store']);

        Route::get('/investor/dashboard', InvestorDashboardController::class);

        // Monitoring & KPI
        Route::get('/monitoring/kpi', [MonitoringController::class, 'kpi']);
        Route::get('/monitoring/deviations', [MonitoringController::class, 'deviations']);

        // Sync endpoints (Offline-First)
        Route::prefix('sync')->group(function () {
            Route::get('/master-data', [MasterDataSyncController::class, 'index']);
            Route::get('/periods', [PeriodSyncController::class, 'index']);
            Route::post('/periods', [PeriodSyncController::class, 'store']);
            Route::get('/daily-activities', [DailyActivitySyncController::class, 'index']);
            Route::post('/daily-activities', [DailyActivitySyncController::class, 'store']);
            Route::get('/education', [EducationSyncController::class, 'index']);
            Route::get('/finances', [FinanceSyncController::class, 'index']);
            Route::post('/finances', [FinanceSyncController::class, 'store']);
            Route::get('/maintenances', [MaintenanceSyncController::class, 'index']);
            Route::post('/maintenances', [MaintenanceSyncController::class, 'store']);
            Route::get('/rhpps', [RhppSyncController::class, 'index']);
            Route::post('/activity-logs', [ActivityLogSyncController::class, 'store']);
        });

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

        Route::prefix('approvals')->group(function () {
            Route::get('/daily-activities', [DailyActivityApprovalController::class, 'index']);
            Route::get('/daily-activities/{daily_activity}', [DailyActivityApprovalController::class, 'show']);
            Route::post('/daily-activities/{daily_activity}', [DailyActivityApprovalController::class, 'store']);
        });

        Route::get('/contracts/{contract}', [ContractAbkController::class, 'show']);
        Route::post('/contracts/{contract}', [ContractAbkController::class, 'accept']); // Method POST untuk menyetujui
        Route::delete('/contracts/{contract}', [ContractAbkController::class, 'destroy']);
    });
});

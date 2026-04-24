<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncGetMasterDataRequest;
use App\Http\Resources\Api\V1\AreaResource;
use App\Http\Resources\Api\V1\CoopResource;
use App\Http\Resources\Api\V1\CoopUserAssignmentResource;
use App\Http\Resources\Api\V1\EducationArticleResource;
use App\Http\Resources\Api\V1\FarmResource;
use App\Http\Resources\Api\V1\FormConfigResource;
use App\Http\Resources\Api\V1\OvkItemResource;
use App\Http\Resources\Api\V1\PriceReferenceResource;
use App\Http\Resources\Api\V1\ProductionPeriodResource;
use App\Http\Resources\Api\V1\ReportTemplateResource;
use App\Services\Api\V1\Sync\MasterDataSyncService;

class MasterDataSyncController extends Controller
{
    protected MasterDataSyncService $syncService;

    public function __construct(MasterDataSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function index(SyncGetMasterDataRequest $request)
    {
        $lastSyncTimestamp = $request->validated('last_sync_timestamp');
        $user = $request->user();

        $data = $this->syncService->compileMasterData($lastSyncTimestamp, $user);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil master data sync.',
            'current_server_timestamp' => now()->toIso8601String(),
            'data' => [
                'coop_user_assignments' => CoopUserAssignmentResource::collection($data['coop_user_assignments']),
                'coops'                 => CoopResource::collection($data['coops']),
                'farms'                 => FarmResource::collection($data['farms']),
                'areas'                 => AreaResource::collection($data['areas']),
                'production_periods'    => ProductionPeriodResource::collection($data['production_periods']),
                'form_configs'          => FormConfigResource::collection($data['form_configs']),
                'ovk_items'             => OvkItemResource::collection($data['ovk_items']),
                'education_articles'    => EducationArticleResource::collection($data['education_articles']),
                'price_references'      => PriceReferenceResource::collection($data['price_references']),
                'report_templates'      => ReportTemplateResource::collection($data['report_templates']),
            ],
        ]);
    }
}

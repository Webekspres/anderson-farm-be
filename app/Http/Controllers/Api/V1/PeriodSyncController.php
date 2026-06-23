<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncGetPeriodRequest;
use App\Http\Requests\Api\V1\Sync\SyncPostPeriodRequest;
use App\Http\Resources\Api\V1\Sync\PeriodContractAcceptancePushResource;
use App\Http\Resources\Api\V1\Sync\PeriodSyncResource;
use App\Services\Api\V1\Sync\PeriodSyncService;
use Illuminate\Http\JsonResponse;

class PeriodSyncController extends Controller
{
    public function __construct(private PeriodSyncService $periodSyncService) {}

    /**
     * GET /api/v1/sync/periods
     * 
     * Retrieve period details for offline sync.
     */
    public function index(SyncGetPeriodRequest $request): JsonResponse
    {
        $periodId = $request->validated('period_id');
        $lastSyncTimestamp = $request->validated('last_sync_timestamp');
        $user = $request->user();

        $period = $this->periodSyncService->getPeriodDetail($periodId, $lastSyncTimestamp, $user);
        $data = $period ? PeriodSyncResource::collection([$period]) : [];

        return response()->json([
            'success' => true,
            'message' => 'Detail periode berhasil ditarik.',
            'data' => $data,
        ], 200);
    }

    /**
     * POST /api/v1/sync/periods
     * 
     * Push contract acceptances (digital signatures) from ABK/PIC to server.
     * This is a "push-only" endpoint for offline-first sync.
     */
    public function store(SyncPostPeriodRequest $request): JsonResponse
    {
        $user = $request->user();

        $syncTimestamp = $request->validated('sync_timestamp');
        $acceptances = $request->validated('contract_acceptances');

        $syncResults = $this->periodSyncService->storeContractAcceptances(
            $user,
            $acceptances,
            $syncTimestamp
        );

        return response()->json([
            'success' => true,
            'message' => 'Persetujuan kontrak berhasil disinkronkan.',
            'data' => (new PeriodContractAcceptancePushResource($syncResults))->resolve(),
        ], 200);
    }
}

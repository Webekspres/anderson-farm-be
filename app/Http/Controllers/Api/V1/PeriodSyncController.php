<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncGetPeriodRequest;
use App\Http\Resources\Api\V1\Sync\PeriodSyncResource;
use App\Services\Api\V1\Sync\PeriodSyncService;
use Illuminate\Http\JsonResponse;

class PeriodSyncController extends Controller
{
    public function __construct(private PeriodSyncService $periodSyncService) {}

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
}

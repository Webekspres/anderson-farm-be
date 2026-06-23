<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncGetRhppRequest;
use App\Http\Resources\Api\V1\RhppResource;
use App\Services\Api\RhppSyncService;
use Illuminate\Http\JsonResponse;

class RhppSyncController extends Controller
{
    public function __construct(
        private readonly RhppSyncService $rhppSyncService,
    ) {}

    /**
     * GET /api/v1/sync/rhpps
     */
    public function index(SyncGetRhppRequest $request): JsonResponse
    {
        $user = $request->user();
        $lastSyncTimestamp = $request->validated('last_sync_timestamp');

        $rhpps = $this->rhppSyncService->getPullPayload($user, $lastSyncTimestamp);

        return response()->json([
            'success' => true,
            'current_server_timestamp' => now()->toIso8601String(),
            'data' => [
                'rhpps' => RhppResource::collection($rhpps),
            ],
        ]);
    }
}

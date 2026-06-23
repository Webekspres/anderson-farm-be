<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\BulkSyncActivityLogRequest;
use App\Services\Api\V1\Sync\ActivityLogSyncService;
use Illuminate\Http\JsonResponse;

class ActivityLogSyncController extends Controller
{
    public function __construct(
        private readonly ActivityLogSyncService $activityLogSyncService,
    ) {}

    public function store(BulkSyncActivityLogRequest $request): JsonResponse
    {
        $syncedIds = $this->activityLogSyncService->syncBulk(
            $request->user(),
            $request->validated('logs'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi log aktivitas berhasil.',
            'synced_ids' => $syncedIds,
        ], 200);
    }
}

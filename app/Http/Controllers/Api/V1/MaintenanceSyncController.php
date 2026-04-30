<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncGetMaintenanceRequest;
use App\Http\Requests\Api\V1\Sync\SyncPostMaintenanceRequest;
use App\Http\Resources\Api\V1\MaintenanceLogResource;
use App\Models\User;
use App\Services\Api\MaintenanceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MaintenanceSyncController extends Controller
{
    public function __construct(
        private readonly MaintenanceSyncService $syncService,
    ) {}

    /**
     * GET /api/v1/sync/maintenances
     *
     * Pull delta maintenance logs berdasarkan scope coop user.
     * Admin mendapat semua log; user lain hanya mendapat log dari floor di coop mereka.
     */
    public function index(SyncGetMaintenanceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user              = Auth::user();
        $lastSyncTimestamp = $request->validated('last_sync_timestamp');

        $logs = $this->syncService->getPullPayload($user, $lastSyncTimestamp);

        return response()->json([
            'success'                  => true,
            'current_server_timestamp' => now()->toIso8601String(),
            'data'                     => [
                'maintenance_logs' => MaintenanceLogResource::collection($logs),
            ],
        ]);
    }

    /**
     * POST /api/v1/sync/maintenances
     *
     * Push bulk maintenance logs dari SQLite ke server.
     * Mendukung pembuatan laporan baru (ABK/PIC/Manager) dan
     * update status (PIC/Manager/Admin saja).
     */
    public function store(SyncPostMaintenanceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $payloadLogs     = $request->validated('maintenances');
        $serverTimestamp = now();

        $syncResults = $this->syncService->processPushSync($payloadLogs, $user, $serverTimestamp);

        $successCount  = collect($syncResults)->where('status', 'SUCCESS')->count();
        $failedCount   = collect($syncResults)->where('status', 'FAILED')->count();
        $forbiddenCount = collect($syncResults)->where('status', 'FORBIDDEN')->count();

        $messageParts = [];
        if ($successCount > 0)   $messageParts[] = "{$successCount} berhasil disinkronkan";
        if ($failedCount > 0)    $messageParts[] = "{$failedCount} gagal";
        if ($forbiddenCount > 0) $messageParts[] = "{$forbiddenCount} ditolak (akses tidak diizinkan)";

        return response()->json([
            'success'          => true,
            'message'          => 'Sinkronisasi selesai. ' . implode(', ', $messageParts) . '.',
            'server_timestamp' => $serverTimestamp->toIso8601String(),
            'data'             => [
                'sync_results' => $syncResults,
            ],
        ]);
    }
}

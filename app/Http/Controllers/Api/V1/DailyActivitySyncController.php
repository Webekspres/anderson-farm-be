<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncGetDailyActivityRequest;
use App\Http\Requests\Api\V1\Sync\SyncPostDailyActivityRequest;
use App\Http\Resources\Api\V1\DailyActivityHeaderResource;
use App\Models\DailyActivityHeader;
use App\Services\Api\DailyActivitySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DailyActivitySyncController extends Controller
{
    public function __construct(
        private readonly DailyActivitySyncService $dailyActivitySyncService,
    ) {}

    /**
     * GET /api/v1/sync/daily-activities
     *
     * Pull delta updates untuk sinkronisasi offline-first.
     */
    public function index(SyncGetDailyActivityRequest $request): JsonResponse
    {
        $periodId = $request->validated('period_id');
        $lastSyncTimestamp = $request->validated('last_sync_timestamp');

        $headers = DailyActivityHeader::withTrashed()
            ->where('period_id', $periodId)
            ->when($lastSyncTimestamp, fn ($query) => $query->where('updated_at_server', '>', $lastSyncTimestamp))
            ->with([
                'dynamicLogs',
                'harvests',
                'ovkUsages',
                'photos',
                'dailyChecklistLogs',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi berhasil.',
            'current_server_timestamp' => now()->toIso8601String(),
            'data' => DailyActivityHeaderResource::collection($headers),
        ]);
    }

    /**
     * POST /api/v1/sync/daily-activities
     *
     * Push bulk sync dari mobile. ABK hanya boleh mengirim status DRAFT, SUBMITTED, NEEDS_REVIEW.
     */
    public function store(SyncPostDailyActivityRequest $request): JsonResponse
    {
        $payloadHeaders = $request->validated('headers');
        $serverTimestamp = now();
        $syncResults = [];
        $authUser = $request->user();

        DB::transaction(function () use ($payloadHeaders, $serverTimestamp, &$syncResults, $authUser) {
            foreach ($payloadHeaders as $headerPayload) {
                $syncResults[] = $this->dailyActivitySyncService->processHeader(
                    $headerPayload,
                    $serverTimestamp,
                    $authUser,
                );
            }
        });

        $syncedCount = collect($syncResults)->where('status', 'SYNCED')->count();
        $conflictCount = collect($syncResults)->where('status', 'CONFLICT')->count();
        $closedCount = collect($syncResults)->where('status', 'PERIOD_CLOSED')->count();
        $duplicateDateCount = collect($syncResults)->where('status', 'DUPLICATE_DATE')->count();
        $forbiddenCount = collect($syncResults)->where('status', 'FORBIDDEN')->count();
        $lockedCount = collect($syncResults)->where('status', 'LOCKED')->count();
        $invalidStatusCount = collect($syncResults)->where('status', 'INVALID_STATUS')->count();

        $messageParts = [];
        if ($syncedCount > 0) {
            $messageParts[] = "{$syncedCount} berhasil";
        }
        if ($conflictCount > 0) {
            $messageParts[] = "{$conflictCount} konflik";
        }
        if ($closedCount > 0) {
            $messageParts[] = "{$closedCount} periode ditutup";
        }
        if ($duplicateDateCount > 0) {
            $messageParts[] = "{$duplicateDateCount} tanggal duplikat";
        }
        if ($forbiddenCount > 0) {
            $messageParts[] = "{$forbiddenCount} akses ditolak";
        }
        if ($lockedCount > 0) {
            $messageParts[] = "{$lockedCount} terkunci";
        }
        if ($invalidStatusCount > 0) {
            $messageParts[] = "{$invalidStatusCount} status tidak valid";
        }

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi selesai. '.implode(', ', $messageParts).'.',
            'data' => [
                'server_timestamp' => $serverTimestamp->toIso8601String(),
                'sync_results' => $syncResults,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncGetDailyActivityRequest;
use App\Http\Requests\Api\V1\Sync\SyncPostDailyActivityRequest;
use App\Http\Resources\Api\V1\DailyActivityHeaderResource;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\DailyChecklistLog;
use App\Models\DailyDynamicLog;
use App\Models\HarvestEntry;
use App\Models\OvkUsage;
use App\Models\PhotoEvidence;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyActivitySyncController extends Controller
{
    /**
     * GET /api/v1/sync/daily-activities
     *
     * Pull delta updates untuk sinkronisasi offline-first.
     * Jika last_sync_timestamp diberikan, hanya data yang lebih baru yang dikembalikan.
     * Jika tidak, seluruh data pada periode tersebut dikirim (fresh sync).
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
     * Push bulk sync dari mobile. Menerima array header beserta 5 relasi anaknya.
     * Menggunakan strategi Wipe & Replace untuk anak, dan conflict detection untuk header.
     */
    public function store(SyncPostDailyActivityRequest $request): JsonResponse
    {
        $payloadHeaders = $request->validated('headers');
        $serverTimestamp = now();
        $syncResults = [];
        $authUser = $request->user();

        DB::transaction(function () use ($payloadHeaders, $serverTimestamp, &$syncResults, $authUser) {
            foreach ($payloadHeaders as $headerPayload) {
                $syncResults[] = $this->processHeader($headerPayload, $serverTimestamp, $authUser);
            }
        });

        $syncedCount = collect($syncResults)->where('status', 'SYNCED')->count();
        $conflictCount = collect($syncResults)->where('status', 'CONFLICT')->count();
        $closedCount = collect($syncResults)->where('status', 'PERIOD_CLOSED')->count();

        $duplicateDateCount = collect($syncResults)->where('status', 'DUPLICATE_DATE')->count();
        $forbiddenCount = collect($syncResults)->where('status', 'FORBIDDEN')->count();
        $lockedCount = collect($syncResults)->where('status', 'LOCKED')->count();

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

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi selesai. '.implode(', ', $messageParts).'.',
            'data' => [
                'server_timestamp' => $serverTimestamp->toIso8601String(),
                'sync_results' => $syncResults,
            ],
        ]);
    }

    /**
     * Proses satu header: gatekeeping → akses kandang → konflik → duplikat tanggal → upsert → wipe & replace anak.
     */
    private function processHeader(array $headerPayload, Carbon $serverTimestamp, User $authUser): array
    {
        $headerId = $headerPayload['id'];
        $periodId = $headerPayload['period_id'];

        // ── Step 1: Gatekeeping — Cek apakah periode masih aktif ──
        $period = ProductionPeriod::query()->with('floor')->find($periodId);
        if (! $period || $period->status !== 'active') {
            return [
                'id' => $headerId,
                'status' => 'PERIOD_CLOSED',
                'server_id' => null,
            ];
        }

        $coopId = $period->floor?->coop_id;
        if (! $coopId) {
            return [
                'id' => $headerId,
                'status' => 'FORBIDDEN',
                'server_id' => null,
            ];
        }

        $hasCoopAccess = CoopUserAssignment::query()
            ->where('user_id', $authUser->id)
            ->where('coop_id', $coopId)
            ->exists();

        if (! $hasCoopAccess) {
            return [
                'id' => $headerId,
                'status' => 'FORBIDDEN',
                'server_id' => null,
            ];
        }

        // ── Step 2: Conflict Detection ──
        $existingHeader = DailyActivityHeader::withTrashed()->find($headerId);

        if ($existingHeader && $existingHeader->business_status === 'APPROVED') {
            return [
                'id' => $headerId,
                'status' => 'LOCKED',
                'server_id' => $existingHeader->server_id,
            ];
        }

        if ($existingHeader && $existingHeader->updated_at_server) {
            $clientUpdatedAt = Carbon::parse($headerPayload['updated_at_client']);

            // Jika data server lebih baru dari data yang dikirim klien, tandai sebagai CONFLICT
            if ($existingHeader->updated_at_server->greaterThan($clientUpdatedAt)) {
                return [
                    'id' => $headerId,
                    'status' => 'CONFLICT',
                    'server_id' => $existingHeader->server_id,
                ];
            }
        }

        // ── Step 3: Unik periode + tanggal (UUID berbeda dilarang) ──
        $incomingDate = Carbon::parse($headerPayload['date'])->startOfDay();
        $duplicateOtherUuid = DailyActivityHeader::withTrashed()
            ->where('period_id', $periodId)
            ->whereDate('date', $incomingDate)
            ->where('id', '!=', $headerId)
            ->exists();

        if ($duplicateOtherUuid) {
            return [
                'id' => $headerId,
                'status' => 'DUPLICATE_DATE',
                'server_id' => null,
            ];
        }

        // ── Step 4: UPSERT Header ──
        $header = DailyActivityHeader::withTrashed()->updateOrCreate(
            ['id' => $headerId],
            [
                'period_id' => $periodId,
                'user_id' => $headerPayload['user_id'],
                'date' => $headerPayload['date'],
                'age_days' => $headerPayload['age_days'],
                'mortality_count' => $headerPayload['mortality_count'] ?? 0,
                'cull_count' => $headerPayload['cull_count'] ?? 0,
                'average_weight' => $headerPayload['average_weight'] ?? null,
                'business_status' => $headerPayload['business_status'],
                'sync_status' => 'SYNCED',
                'created_at_client' => $this->toMysqlDateTime($headerPayload['created_at_client']),
                'created_at_server' => $this->toMysqlDateTime($existingHeader?->created_at_server ?? $serverTimestamp),
                'updated_at_client' => $this->toMysqlDateTime($headerPayload['updated_at_client']),
                'updated_at_server' => $this->toMysqlDateTime($serverTimestamp),
                'deleted_at' => null, // Restore jika sebelumnya soft-deleted
            ],
        );

        // ── Step 5: Wipe & Replace untuk 5 tabel anak ──
        $this->wipeAndReplaceChildren($header, $headerPayload, $serverTimestamp);

        return [
            'id' => $header->id,
            'status' => 'SYNCED',
            'server_id' => $header->server_id,
        ];
    }

    /**
     * Hapus semua data anak lama (force delete) lalu bulk insert data baru.
     */
    private function wipeAndReplaceChildren(
        DailyActivityHeader $header,
        array $headerPayload,
        Carbon $serverTimestamp,
    ): void {
        $headerId = $header->id;

        // Force-delete existing children (bypass SoftDeletes)
        DailyDynamicLog::withTrashed()->where('header_id', $headerId)->forceDelete();
        HarvestEntry::withTrashed()->where('header_id', $headerId)->forceDelete();
        OvkUsage::withTrashed()->where('header_id', $headerId)->forceDelete();
        PhotoEvidence::withTrashed()->where('header_id', $headerId)->forceDelete();
        DailyChecklistLog::withTrashed()->where('header_id', $headerId)->forceDelete();

        // ── Bulk Insert: Dynamic Logs ──
        if (! empty($headerPayload['dynamic_logs'])) {
            $dynamicLogs = array_map(fn (array $log) => [
                'id' => $log['id'],
                'header_id' => $headerId,
                'form_config_id' => $log['form_config_id'],
                'value' => $log['value'],
                'sync_status' => 'SYNCED',
                'created_at_client' => $this->toMysqlDateTime($log['created_at_client']),
                'updated_at_client' => $this->toMysqlDateTime($log['updated_at_client']),
            ], $headerPayload['dynamic_logs']);
            DailyDynamicLog::insert($dynamicLogs);
        }

        // ── Bulk Insert: Harvest Entries ──
        if (! empty($headerPayload['harvests'])) {
            $harvests = array_map(fn (array $harvest) => [
                'id' => $harvest['id'],
                'header_id' => $headerId,
                'rit_number' => $harvest['rit_number'],
                'buyer_name' => $harvest['buyer_name'] ?? null,
                'total_birds' => $harvest['total_birds'],
                'total_weight' => $harvest['total_weight'],
                'price_per_kg' => $harvest['price_per_kg'],
                'total_revenue' => $harvest['total_revenue'],
                'delivery_order_no' => $harvest['delivery_order_no'] ?? null,
                'sync_status' => 'SYNCED',
                'created_at_client' => $this->toMysqlDateTime($harvest['created_at_client']),
                'created_at_server' => $this->toMysqlDateTime($serverTimestamp),
                'updated_at_client' => $this->toMysqlDateTime($harvest['updated_at_client']),
                'updated_at_server' => $this->toMysqlDateTime($serverTimestamp),
            ], $headerPayload['harvests']);
            HarvestEntry::insert($harvests);
        }

        // ── Bulk Insert: OVK Usages ──
        if (! empty($headerPayload['ovk_usages'])) {
            $ovkUsages = array_map(fn (array $ovk) => [
                'id' => $ovk['id'],
                'header_id' => $headerId,
                'ovk_item_id' => $ovk['ovk_item_id'],
                'quantity' => $ovk['quantity'],
                'notes' => $ovk['notes'] ?? null,
                'sync_status' => 'SYNCED',
                'created_at_client' => $this->toMysqlDateTime($ovk['created_at_client']),
                'created_at_server' => $this->toMysqlDateTime($serverTimestamp),
                'updated_at_client' => $this->toMysqlDateTime($ovk['updated_at_client']),
                'updated_at_server' => $this->toMysqlDateTime($serverTimestamp),
            ], $headerPayload['ovk_usages']);
            OvkUsage::insert($ovkUsages);
        }

        // ── Bulk Insert: Photo Evidences ──
        if (! empty($headerPayload['photos'])) {
            $photos = array_map(fn (array $photo) => [
                'id' => $photo['id'],
                'header_id' => $headerId,
                'photo_path_local' => $photo['photo_path_local'],
                'photo_url' => $photo['photo_url'],
                'photo_type' => $photo['photo_type'],
                'description' => $photo['description'] ?? null,
                'sync_status' => 'SYNCED',
                'created_at_client' => $this->toMysqlDateTime($photo['created_at_client']),
                'created_at_server' => $this->toMysqlDateTime($serverTimestamp),
                'updated_at_client' => $this->toMysqlDateTime($photo['updated_at_client']),
                'updated_at_server' => $this->toMysqlDateTime($serverTimestamp),
            ], $headerPayload['photos']);
            PhotoEvidence::insert($photos);
        }

        // ── Bulk Insert: Checklist Logs ──
        if (! empty($headerPayload['checklist_logs'])) {
            $checklistLogs = array_map(fn (array $log) => [
                'id' => $log['id'],
                'header_id' => $headerId,
                'task_id' => $log['task_id'],
                'boolean_value' => $log['boolean_value'] ?? null,
                'text_value' => $log['text_value'] ?? null,
                'notes' => $log['notes'] ?? null,
                'sync_status' => 'SYNCED',
                'created_at_client' => $this->toMysqlDateTime($log['created_at_client']),
                'updated_at_client' => $this->toMysqlDateTime($log['updated_at_client']),
            ], $headerPayload['checklist_logs']);
            DailyChecklistLog::insert($checklistLogs);
        }
    }

    /**
     * Bulk insert melewati Eloquent cast; MySQL menolak ISO-8601 bertipe `...T...Z`.
     */
    private function toMysqlDateTime(\DateTimeInterface|string $value): string
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}

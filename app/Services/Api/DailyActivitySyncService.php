<?php

namespace App\Services\Api;

use App\Enums\BusinessStatus;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\DailyChecklistLog;
use App\Models\DailyDynamicLog;
use App\Models\HarvestEntry;
use App\Models\OvkUsage;
use App\Models\PhotoEvidence;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Support\Carbon;

class DailyActivitySyncService
{
    /**
     * Proses satu header: gatekeeping → akses kandang → status → konflik → duplikat tanggal → upsert → wipe & replace anak.
     *
     * @return array{id: string, status: string, server_id: int|null}
     */
    public function processHeader(array $headerPayload, Carbon $serverTimestamp, User $authUser): array
    {
        $headerId = $headerPayload['id'];
        $periodId = $headerPayload['period_id'];

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

        $existingHeader = DailyActivityHeader::withTrashed()->find($headerId);
        $incomingStatus = $headerPayload['business_status'];

        if (in_array($incomingStatus, [BusinessStatus::Approved->value, BusinessStatus::Rejected->value], true)) {
            return [
                'id' => $headerId,
                'status' => 'INVALID_STATUS',
                'server_id' => $existingHeader?->server_id,
            ];
        }

        if ($existingHeader && $existingHeader->business_status === BusinessStatus::Approved->value) {
            return [
                'id' => $headerId,
                'status' => 'LOCKED',
                'server_id' => $existingHeader->server_id,
            ];
        }

        $resolvedStatus = $this->resolveSyncBusinessStatus(
            $existingHeader?->business_status,
            $incomingStatus,
        );

        if ($resolvedStatus === null) {
            return [
                'id' => $headerId,
                'status' => 'INVALID_STATUS',
                'server_id' => $existingHeader?->server_id,
            ];
        }

        if ($existingHeader && $existingHeader->updated_at_server) {
            $clientUpdatedAt = Carbon::parse($headerPayload['updated_at_client']);

            if ($existingHeader->updated_at_server->greaterThan($clientUpdatedAt)) {
                return [
                    'id' => $headerId,
                    'status' => 'CONFLICT',
                    'server_id' => $existingHeader->server_id,
                ];
            }
        }

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

        $clearApprovalFields = $existingHeader?->business_status === BusinessStatus::Rejected->value
            && $resolvedStatus === BusinessStatus::Submitted->value;

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
                'business_status' => $resolvedStatus,
                'approved_by' => $clearApprovalFields ? null : $existingHeader?->approved_by,
                'rejection_reason' => $clearApprovalFields ? null : $existingHeader?->rejection_reason,
                'sync_status' => 'SYNCED',
                'created_at_client' => $this->toMysqlDateTime($headerPayload['created_at_client']),
                'created_at_server' => $this->toMysqlDateTime($existingHeader?->created_at_server ?? $serverTimestamp),
                'updated_at_client' => $this->toMysqlDateTime($headerPayload['updated_at_client']),
                'updated_at_server' => $this->toMysqlDateTime($serverTimestamp),
                'deleted_at' => null,
            ],
        );

        $this->wipeAndReplaceChildren($header, $headerPayload, $serverTimestamp);

        return [
            'id' => $header->id,
            'status' => 'SYNCED',
            'server_id' => $header->server_id,
        ];
    }

    /**
     * Tentukan status bisnis yang boleh disimpan dari payload sync ABK.
     */
    private function resolveSyncBusinessStatus(?string $existingStatus, string $incomingStatus): ?string
    {
        if (! in_array($incomingStatus, BusinessStatus::syncableValues(), true)) {
            return null;
        }

        if ($existingStatus === BusinessStatus::Submitted->value && $incomingStatus === BusinessStatus::Draft->value) {
            return null;
        }

        if ($existingStatus === BusinessStatus::Rejected->value && $incomingStatus === BusinessStatus::Submitted->value) {
            return BusinessStatus::Submitted->value;
        }

        return $incomingStatus;
    }

    private function wipeAndReplaceChildren(
        DailyActivityHeader $header,
        array $headerPayload,
        Carbon $serverTimestamp,
    ): void {
        $headerId = $header->id;
        $periodId = $header->period_id;

        DailyDynamicLog::withTrashed()->where('header_id', $headerId)->forceDelete();
        HarvestEntry::withTrashed()->where('header_id', $headerId)->forceDelete();
        OvkUsage::withTrashed()->where('header_id', $headerId)->forceDelete();
        PhotoEvidence::withTrashed()->where('header_id', $headerId)->forceDelete();

        DailyChecklistLog::withTrashed()
            ->where(function ($query) use ($headerId, $periodId, $headerPayload) {
                $query->where('header_id', $headerId);
                if (! empty($headerPayload['checklist_logs'])) {
                    $preChickInTaskIds = collect($headerPayload['checklist_logs'])
                        ->filter(fn ($log) => empty($log['header_id']))
                        ->pluck('task_id')
                        ->all();
                    if (! empty($preChickInTaskIds)) {
                        $query->orWhere(function ($q) use ($periodId, $preChickInTaskIds) {
                            $q->where('period_id', $periodId)
                                ->whereNull('header_id')
                                ->whereIn('task_id', $preChickInTaskIds);
                        });
                    }
                }
            })
            ->forceDelete();

        if (! empty($headerPayload['dynamic_logs'])) {
            $dynamicLogs = array_map(fn (array $log) => [
                'id' => $log['id'],
                'header_id' => $headerId,
                'form_config_id' => $log['form_config_id'],
                'value' => $log['value'],
                'value_numeric' => isset($log['value_numeric']) ? (float) $log['value_numeric'] : null,
                'value_boolean' => isset($log['value_boolean']) ? (bool) $log['value_boolean'] : null,
                'sync_status' => 'SYNCED',
                'created_at_client' => $this->toMysqlDateTime($log['created_at_client']),
                'updated_at_client' => $this->toMysqlDateTime($log['updated_at_client']),
            ], $headerPayload['dynamic_logs']);
            DailyDynamicLog::insert($dynamicLogs);
        }

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

        if (! empty($headerPayload['photos'])) {
            $photos = array_map(fn (array $photo) => [
                'id' => $photo['id'],
                'header_id' => $headerId,
                'photo_path_local' => $photo['photo_path_local'],
                'photo_url' => $photo['photo_url'] ?? null,
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

        if (! empty($headerPayload['checklist_logs'])) {
            $checklistLogs = array_map(fn (array $log) => [
                'id' => $log['id'],
                'header_id' => ! empty($log['header_id']) ? $log['header_id'] : null,
                'period_id' => $log['period_id'] ?? $periodId,
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

    private function toMysqlDateTime(\DateTimeInterface|string $value): string
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}

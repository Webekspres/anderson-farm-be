<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Sync;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityLogSyncService
{
    /**
     * @param  array<int, array<string, mixed>>  $logs
     * @return array<int, string>
     */
    public function syncBulk(User $user, array $logs): array
    {
        if ($logs === []) {
            return [];
        }

        $dedupedLogs = $this->deduplicateLogsById($logs);
        $syncedIds = [];
        $serverNow = now();

        DB::transaction(function () use ($user, $dedupedLogs, $serverNow, &$syncedIds): void {
            foreach ($dedupedLogs as $logPayload) {
                $syncedIds[] = $this->upsertLog($user, $logPayload, $serverNow);
            }
        });

        return $syncedIds;
    }

    /**
     * @param  array<int, array<string, mixed>>  $logs
     * @return array<string, array<string, mixed>>
     */
    private function deduplicateLogsById(array $logs): array
    {
        $deduped = [];

        foreach ($logs as $logPayload) {
            $deduped[(string) $logPayload['id']] = $logPayload;
        }

        return $deduped;
    }

    /**
     * @param  array<string, mixed>  $logPayload
     */
    private function upsertLog(User $user, array $logPayload, Carbon $serverNow): string
    {
        $logId = (string) $logPayload['id'];
        $existing = ActivityLog::query()->find($logId);

        $attributes = [
            'user_id' => $user->id,
            'action' => (string) $logPayload['action'],
            'entity_type' => (string) $logPayload['entity_type'],
            'entity_id' => (string) $logPayload['entity_id'],
            'device_id' => $logPayload['device_id'] ?? null,
            'status' => (string) $logPayload['status'],
            'metadata' => $logPayload['metadata'] ?? null,
            'sync_status' => 'SYNCED',
            'created_at_client' => Carbon::parse((string) $logPayload['created_at_client']),
            'updated_at_client' => Carbon::parse((string) $logPayload['updated_at_client']),
            'updated_at_server' => $serverNow,
        ];

        if ($existing === null) {
            $attributes['created_at_server'] = $serverNow;
        }

        ActivityLog::query()->updateOrCreate(
            ['id' => $logId],
            $attributes,
        );

        return $logId;
    }
}

<?php

namespace App\Services\Api;

use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class MaintenanceSyncService
{
    /**
     * Susun payload pull untuk GET /sync/maintenances.
     *
     * Scope hierarki: coop_user_assignments → coop_floors → maintenance_logs
     * Admin bypass: langsung fetch semua log tanpa cek assignment.
     */
    public function getPullPayload(User $user, ?string $lastSyncTimestamp): Collection
    {
        if ($user->role === 'admin') {
            // Admin: akses global ke seluruh log
            return MaintenanceLog::withTrashed()
                ->when(
                    $lastSyncTimestamp,
                    fn($q) => $q->where('updated_at_server', '>', $lastSyncTimestamp)
                )
                ->get();
        }

        // Non-admin: scope ke floor yang berada di coop yang diassign ke user
        $floorIds = $this->resolveFloorIds($user);

        return MaintenanceLog::withTrashed()
            ->whereIn('floor_id', $floorIds)
            ->when(
                $lastSyncTimestamp,
                fn($q) => $q->where('updated_at_server', '>', $lastSyncTimestamp)
            )
            ->get();
    }

    /**
     * Resolusi floor_id berdasarkan assignment coop user.
     * Langkah: coop_user_assignments → coop_floors.
     */
    private function resolveFloorIds(User $user): SupportCollection
    {
        $coopIds = CoopUserAssignment::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('coop_id');

        return CoopFloor::whereIn('coop_id', $coopIds)
            ->whereNull('deleted_at')
            ->pluck('id');
    }

    /**
     * Proses seluruh array payload maintenance dari mobile.
     * Iterasi per-item agar satu kegagalan tidak memblokir item lainnya.
     */
    public function processPushSync(array $payloadLogs, User $user, Carbon $serverTimestamp): array
    {
        $syncResults = [];

        foreach ($payloadLogs as $logPayload) {
            $syncResults[] = $this->processLog($logPayload, $user, $serverTimestamp);
        }

        return $syncResults;
    }

    /**
     * Proses satu item maintenance log:
     * 1. Validasi hierarki: user harus punya assignment ke coop milik floor ini
     * 2. Branching Scenario A (baru) vs Scenario B (update existing)
     */
    private function processLog(array $payload, User $user, Carbon $serverTimestamp): array
    {
        $logId = $payload['id'];

        // ── Step 1: Hierarchical Validation — Cek assignment coop ──
        // Ambil coop_id lewat relasi floor → coop
        $floor = CoopFloor::find($payload['floor_id']);

        if (!$floor) {
            return $this->result($logId, 'FAILED', null, 'Lantai tidak ditemukan.');
        }

        // Admin memiliki akses global, role lain harus punya assignment
        if ($user->role !== 'admin') {
            $isAssigned = CoopUserAssignment::where('user_id', $user->id)
                ->where('coop_id', $floor->coop_id)
                ->whereNull('deleted_at')
                ->exists();

            if (!$isAssigned) {
                return $this->result($logId, 'FORBIDDEN', null, 'Anda tidak memiliki akses ke kandang ini.');
            }
        }

        // ── Step 2: Cek apakah record sudah ada di server ──
        $existingLog = MaintenanceLog::withTrashed()->find($logId);

        if (!$existingLog) {
            // ── SCENARIO A: Log baru — semua role boleh membuat ──
            return $this->createNewLog($logId, $payload, $user, $serverTimestamp);
        }

        // ── SCENARIO B: Log sudah ada — hanya PIC/Manager/Admin boleh update ──
        return $this->updateExistingLog($existingLog, $payload, $user, $serverTimestamp);
    }

    /**
     * Scenario A: Buat log baru.
     * Status dikunci ke REPORTED, reported_by diisi dari user yang login.
     */
    private function createNewLog(
        string $logId,
        array  $payload,
        User   $user,
        Carbon $serverTimestamp,
    ): array {
        $log = MaintenanceLog::create([
            'id'               => $logId,
            'floor_id'         => $payload['floor_id'],
            'reported_by'      => $user->id,         // Selalu dari user yang login
            'date'             => $serverTimestamp,   // Tanggal ditetapkan server
            'description'      => $payload['description'],
            'image_path_local' => $payload['image_path_local'] ?? null,
            'status'           => 'REPORTED',         // Default wajib REPORTED untuk log baru
            'sync_status'      => 'SYNCED',
            'created_at_client' => $payload['created_at_client'],
            'created_at_server' => $serverTimestamp,
            'updated_at_client' => $payload['updated_at_client'],
            'updated_at_server' => $serverTimestamp,
        ]);

        return $this->result($log->id, 'SUCCESS', $log->server_id);
    }

    /**
     * Scenario B: Update log yang sudah ada.
     * ABK dilarang keras. RESOLVED hanya bisa dibuka ulang oleh Admin.
     */
    private function updateExistingLog(
        MaintenanceLog $existingLog,
        array          $payload,
        User           $user,
        Carbon         $serverTimestamp,
    ): array {
        // ABK tidak boleh mengubah log yang sudah ada
        if ($user->role === 'abk') {
            return $this->result(
                $existingLog->id,
                'FORBIDDEN',
                $existingLog->server_id,
                'ABK tidak diizinkan mengubah laporan yang sudah ada.'
            );
        }

        // Jika sudah RESOLVED, hanya Admin yang boleh membuka kembali / mengubah
        if ($existingLog->status === 'RESOLVED' && $user->role !== 'admin') {
            return $this->result(
                $existingLog->id,
                'FAILED',
                $existingLog->server_id,
                'Log yang sudah RESOLVED hanya dapat diubah oleh Admin.'
            );
        }

        // PIC, Manager, Admin boleh update status dan deskripsi
        $existingLog->update([
            'description'      => $payload['description'],
            'status'           => $payload['status'],
            'image_path_local' => $payload['image_path_local'] ?? $existingLog->image_path_local,
            'sync_status'      => 'SYNCED',
            'updated_at_client' => $payload['updated_at_client'],
            'updated_at_server' => $serverTimestamp,
        ]);

        return $this->result($existingLog->id, 'SUCCESS', $existingLog->server_id);
    }

    /**
     * Helper: Buat array hasil per-item yang konsisten.
     */
    private function result(string $id, string $status, ?int $serverId, ?string $errorMessage = null): array
    {
        return [
            'id'            => $id,
            'status'        => $status,
            'server_id'     => $serverId,
            'error_message' => $errorMessage,
        ];
    }
}

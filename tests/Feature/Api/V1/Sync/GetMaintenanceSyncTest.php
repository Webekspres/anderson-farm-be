<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * Buat Coop → Floor dan beri assignment user ke Coop.
 */
function createMaintenanceFloorForUser(User $user): CoopFloor
{
    $coop  = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    CoopUserAssignment::factory()->create([
        'user_id' => $user->id,
        'coop_id' => $coop->id,
    ]);

    return $floor;
}

describe('GET /api/v1/sync/maintenances', function () {

    // ── Test 1: Scope — hanya log dari coop yang diassign ──

    it('Test 1 — User hanya melihat maintenance log dari Coop yang diassign kepadanya', function () {
        $pic = User::factory()->create(['role' => 'pic']);

        // Coop A — assigned ke $pic
        $floorA = createMaintenanceFloorForUser($pic);
        $logA   = MaintenanceLog::factory()->create([
            'floor_id'    => $floorA->id,
            'reported_by' => $pic->id,
            'status'      => 'REPORTED',
        ]);

        // Coop B — TIDAK assigned ke $pic
        $coopB  = Coop::factory()->create();
        $floorB = CoopFloor::factory()->create(['coop_id' => $coopB->id]);
        MaintenanceLog::factory()->create([
            'floor_id'    => $floorB->id,
            'reported_by' => $pic->id,
            'status'      => 'REPORTED',
        ]);

        $response = $this->actingAs($pic)->getJson('/api/v1/sync/maintenances');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'current_server_timestamp',
            'data' => ['maintenance_logs'],
        ]);

        // Hanya log Coop A yang dikembalikan
        $response->assertJsonCount(1, 'data.maintenance_logs');
        $response->assertJsonPath('data.maintenance_logs.0.id', $logA->id);
    });

    // ── Test 2: Delta Sync — filter by last_sync_timestamp ──

    it('Test 2 — Delta sync hanya mengembalikan log yang di-update setelah last_sync_timestamp', function () {
        $pic   = User::factory()->create(['role' => 'pic']);
        $floor = createMaintenanceFloorForUser($pic);

        $cutoff = now()->subHour();

        // Log lama — updated sebelum cutoff (tidak boleh muncul)
        MaintenanceLog::factory()->create([
            'floor_id'          => $floor->id,
            'reported_by'       => $pic->id,
            'status'            => 'REPORTED',
            'updated_at_server' => $cutoff->copy()->subMinutes(30)->toDateTimeString(),
        ]);

        // Log baru — updated setelah cutoff (harus muncul)
        $newLog = MaintenanceLog::factory()->create([
            'floor_id'          => $floor->id,
            'reported_by'       => $pic->id,
            'status'            => 'IN_PROGRESS',
            'updated_at_server' => now()->toDateTimeString(),
        ]);

        $response = $this->actingAs($pic)->getJson(
            '/api/v1/sync/maintenances?last_sync_timestamp=' . urlencode($cutoff->toDateTimeString())
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data.maintenance_logs');
        $response->assertJsonPath('data.maintenance_logs.0.id', $newLog->id);
    });

    // ── Test 3: Soft Delete — deleted record tetap dikembalikan ──

    it('Test 3 — Soft-deleted log tetap dikembalikan dengan deleted_at terisi', function () {
        $pic   = User::factory()->create(['role' => 'pic']);
        $floor = createMaintenanceFloorForUser($pic);

        // Buat log, lalu soft-delete
        $log = MaintenanceLog::factory()->create([
            'floor_id'    => $floor->id,
            'reported_by' => $pic->id,
            'status'      => 'RESOLVED',
        ]);
        $log->delete(); // soft delete

        $response = $this->actingAs($pic)->getJson('/api/v1/sync/maintenances');

        $response->assertOk();

        // Record soft-deleted harus tetap ada di payload
        $response->assertJsonCount(1, 'data.maintenance_logs');

        // deleted_at harus terisi (non-null)
        $deletedAt = $response->json('data.maintenance_logs.0.deleted_at');
        expect($deletedAt)->not->toBeNull();
    });

    // ── Bonus: Admin mendapat semua log tanpa scope ──

    it('Admin mendapat semua log dari seluruh coop tanpa perlu assignment', function () {
        $admin = User::factory()->create(['role' => 'admin']);

        // Buat 3 log di 3 coop berbeda yang tidak diassign ke admin
        foreach (range(1, 3) as $i) {
            $coop  = Coop::factory()->create();
            $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
            MaintenanceLog::factory()->create([
                'floor_id'    => $floor->id,
                'reported_by' => $admin->id,
                'status'      => 'REPORTED',
            ]);
        }

        $response = $this->actingAs($admin)->getJson('/api/v1/sync/maintenances');

        $response->assertOk();
        $response->assertJsonCount(3, 'data.maintenance_logs');
    });

    it('returns 401 when not authenticated', function () {
        $this->getJson('/api/v1/sync/maintenances')->assertUnauthorized();
    });
});

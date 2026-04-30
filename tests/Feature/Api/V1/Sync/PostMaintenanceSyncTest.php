<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * Buat Coop → Floor dan beri user assignment ke Coop tersebut.
 * Mengembalikan floor yang sudah terassign.
 */
function createAssignedFloor(User $user): CoopFloor
{
    $coop  = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    CoopUserAssignment::factory()->create([
        'user_id' => $user->id,
        'coop_id' => $coop->id,
    ]);

    return $floor;
}

/**
 * Buat payload standar satu item maintenance log.
 */
function buildMaintenancePayload(string $floorId, array $overrides = []): array
{
    return array_merge([
        'id'                => Str::uuid()->toString(),
        'floor_id'          => $floorId,
        'description'       => 'Atap kandang bocor di sudut barat.',
        'status'            => 'REPORTED',
        'image_path_local'  => null,
        'created_at_client' => '2026-04-27T08:00:00Z',
        'updated_at_client' => '2026-04-27T08:00:00Z',
    ], $overrides);
}

// ──────────────────────────────────────────────────────────────
// Test Suite
// ──────────────────────────────────────────────────────────────

describe('POST /api/v1/sync/maintenances', function () {

    // ── Test 1: ABK creates a new log (Happy Path — Scenario A) ──

    it('ABK berhasil membuat laporan kerusakan baru (Scenario A)', function () {
        $abk   = User::factory()->create(['role' => 'abk']);
        $floor = createAssignedFloor($abk);
        $logId = Str::uuid()->toString();

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'maintenances'   => [
                buildMaintenancePayload($floor->id, ['id' => $logId]),
            ],
        ];

        $response = $this->actingAs($abk)->postJson('/api/v1/sync/maintenances', $payload);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'server_timestamp',
            'data' => [
                'sync_results' => [
                    '*' => ['id', 'status', 'server_id', 'error_message'],
                ],
            ],
        ]);
        $response->assertJsonPath('data.sync_results.0.id', $logId);
        $response->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

        // Pastikan status dikunci ke REPORTED meskipun client kirim status lain
        $this->assertDatabaseHas('maintenance_logs', [
            'id'          => $logId,
            'status'      => 'REPORTED',
            'reported_by' => $abk->id,
            'sync_status' => 'SYNCED',
        ]);
    });

    // ── Test 2: ABK tries to update existing log (Forbidden — Scenario B) ──

    it('ABK dilarang mengupdate log yang sudah ada (Scenario B — FORBIDDEN)', function () {
        $abk   = User::factory()->create(['role' => 'abk']);
        $floor = createAssignedFloor($abk);

        // Buat log yang sudah ada di server
        $existingLog = MaintenanceLog::factory()->create([
            'floor_id'    => $floor->id,
            'reported_by' => $abk->id,
            'status'      => 'REPORTED',
        ]);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'maintenances'   => [
                buildMaintenancePayload($floor->id, [
                    'id'     => $existingLog->id, // ID yang sudah ada
                    'status' => 'RESOLVED',        // ABK mencoba self-resolve
                ]),
            ],
        ];

        $response = $this->actingAs($abk)->postJson('/api/v1/sync/maintenances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'FORBIDDEN');

        // Pastikan status di DB TIDAK berubah
        $this->assertDatabaseHas('maintenance_logs', [
            'id'     => $existingLog->id,
            'status' => 'REPORTED', // Tetap REPORTED
        ]);
    });

    // ── Test 3: PIC updates existing log to RESOLVED (Happy Path — Scenario B) ──

    it('PIC berhasil mengupdate status log ke RESOLVED (Scenario B — SUCCESS)', function () {
        $pic   = User::factory()->create(['role' => 'pic']);
        $abk   = User::factory()->create(['role' => 'abk']);
        $floor = createAssignedFloor($pic);

        // ABK sudah melaporkan kerusakan sebelumnya
        $existingLog = MaintenanceLog::factory()->create([
            'floor_id'    => $floor->id,
            'reported_by' => $abk->id,
            'status'      => 'REPORTED',
        ]);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'maintenances'   => [
                buildMaintenancePayload($floor->id, [
                    'id'          => $existingLog->id,
                    'status'      => 'RESOLVED',
                    'description' => 'Atap sudah diperbaiki oleh tukang.',
                ]),
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/maintenances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

        $this->assertDatabaseHas('maintenance_logs', [
            'id'          => $existingLog->id,
            'status'      => 'RESOLVED',
            'description' => 'Atap sudah diperbaiki oleh tukang.',
            'sync_status' => 'SYNCED',
        ]);
    });

    // ── Test 4: User tanpa assignment ke coop (FORBIDDEN) ──

    it('User tanpa assignment ke coop mendapat FORBIDDEN', function () {
        $pic  = User::factory()->create(['role' => 'pic']);
        $coop = Coop::factory()->create();

        // Floor milik coop yang TIDAK diassign ke $pic
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'maintenances'   => [
                buildMaintenancePayload($floor->id),
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/maintenances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'FORBIDDEN');

        $this->assertDatabaseCount('maintenance_logs', 0);
    });

    // ── Bonus Test 5: RESOLVED lock — PIC tidak bisa reopen ──

    it('PIC tidak dapat mengubah log yang sudah RESOLVED', function () {
        $pic   = User::factory()->create(['role' => 'pic']);
        $floor = createAssignedFloor($pic);

        $resolvedLog = MaintenanceLog::factory()->create([
            'floor_id'    => $floor->id,
            'reported_by' => $pic->id,
            'status'      => 'RESOLVED',
        ]);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'maintenances'   => [
                buildMaintenancePayload($floor->id, [
                    'id'     => $resolvedLog->id,
                    'status' => 'IN_PROGRESS', // Coba buka kembali
                ]),
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/maintenances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'FAILED');

        // Status harus tetap RESOLVED
        $this->assertDatabaseHas('maintenance_logs', [
            'id'     => $resolvedLog->id,
            'status' => 'RESOLVED',
        ]);
    });

    // ── Bonus Test 6: Admin bisa reopen RESOLVED ──

    it('Admin dapat mengubah log yang sudah RESOLVED', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $coop  = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

        $resolvedLog = MaintenanceLog::factory()->create([
            'floor_id'    => $floor->id,
            'reported_by' => $admin->id,
            'status'      => 'RESOLVED',
        ]);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'maintenances'   => [
                buildMaintenancePayload($floor->id, [
                    'id'     => $resolvedLog->id,
                    'status' => 'IN_PROGRESS',
                ]),
            ],
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/sync/maintenances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

        $this->assertDatabaseHas('maintenance_logs', [
            'id'     => $resolvedLog->id,
            'status' => 'IN_PROGRESS',
        ]);
    });

    // ── Auth & Validation ──

    it('returns 401 when not authenticated', function () {
        $response = $this->postJson('/api/v1/sync/maintenances', [
            'sync_timestamp' => now()->toIso8601String(),
            'maintenances'   => [],
        ]);

        $response->assertUnauthorized();
    });

    it('returns 422 when maintenances array is missing', function () {
        $user = User::factory()->create(['role' => 'pic']);

        $response = $this->actingAs($user)->postJson('/api/v1/sync/maintenances', [
            'sync_timestamp' => now()->toIso8601String(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('maintenances');
    });

    it('returns 422 when status is invalid', function () {
        $user  = User::factory()->create(['role' => 'pic']);
        $coop  = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'maintenances'   => [
                buildMaintenancePayload($floor->id, ['status' => 'diperbaiki']), // status lama/salah
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/maintenances', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('maintenances.0.status');
    });
});

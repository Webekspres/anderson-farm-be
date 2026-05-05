<?php

declare(strict_types=1);

use App\Models\Coop;
use App\Models\CoopEquipment;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\Farm;
use App\Models\ProductionPeriod;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────────────────────
// Shared Setup
// ──────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    // ── Farm Infrastructure ──
    $this->farm = Farm::factory()->create();

    // Two separate Coops representing two physical chicken houses
    $this->coopA = Coop::factory()->create(['farm_id' => $this->farm->id]);
    $this->coopB = Coop::factory()->create(['farm_id' => $this->farm->id]);

    // One Floor per Coop
    $this->floorA = CoopFloor::factory()->create(['coop_id' => $this->coopA->id]);
    $this->floorB = CoopFloor::factory()->create(['coop_id' => $this->coopB->id]);

    // Active production period for Coop A only
    $this->periodA = ProductionPeriod::factory()->create([
        'floor_id' => $this->floorA->id,
        'status' => 'active',
    ]);

    // A physical piece of equipment installed in Coop A's floor (for reference context)
    $this->equipmentA = CoopEquipment::factory()->create([
        'floor_id' => $this->floorA->id,
    ]);

    // Expense category required for finance payload validation
    $this->expenseCategory = TransactionCategory::factory()->create(['type' => 'EXPENSE']);

    // ── Users ──
    //
    // NOTE on RBAC:
    // POST /api/v1/sync/finances blocks the `abk` role entirely (403 Forbidden).
    // In the real operational flow, petty cash (Kas Kecil) is submitted by the PIC
    // or Manager, not directly by an ABK. Therefore:
    //   - $picA  (role: pic)  → finance push actor, assigned to Coop A
    //   - $abkA  (role: abk)  → maintenance push actor, assigned to Coop A
    //   - $abkB  (role: abk)  → isolation actor, assigned to Coop B ONLY
    $this->picA = User::factory()->create(['role' => 'pic']);
    $this->abkA = User::factory()->create(['role' => 'abk']);
    $this->abkB = User::factory()->create(['role' => 'abk']);

    // Assignments — strictly scoped per Coop
    CoopUserAssignment::factory()->create([
        'user_id' => $this->picA->id,
        'coop_id' => $this->coopA->id,
    ]);
    CoopUserAssignment::factory()->create([
        'user_id' => $this->abkA->id,
        'coop_id' => $this->coopA->id,
    ]);
    CoopUserAssignment::factory()->create([
        'user_id' => $this->abkB->id,
        'coop_id' => $this->coopB->id,
    ]);
});

// ──────────────────────────────────────────────────────────────────────────────
// Journey Test
// ──────────────────────────────────────────────────────────────────────────────

it('successfully syncs finances and maintenances and strictly isolates data between coops', function () {

    $financeId = Str::uuid()->toString();
    $maintenanceId = Str::uuid()->toString();

    // ══════════════════════════════════════════════════════════════
    // PHASE 1 — Push Sync Data
    // ══════════════════════════════════════════════════════════════

    // ── Step 1a: PIC A pushes Kas Kecil (Petty Cash Expense) ──────
    // Role `abk` is RBAC-blocked from POST /sync/finances; the PIC
    // supervising the house is responsible for petty cash submission.
    Sanctum::actingAs($this->picA, ['*']);

    $financeResponse = $this->postJson('/api/v1/sync/finances', [
        'sync_timestamp' => now()->toIso8601String(),
        'transactions' => [
            [
                'id' => $financeId,
                'period_id' => $this->periodA->id,
                'category_id' => $this->expenseCategory->id,
                'transaction_date' => now()->toDateTimeString(),
                'type' => 'EXPENSE',
                'amount' => 50000,
                'description' => 'Beli Sapu Lidi',
                'receipt_image_path_local' => null,
                'created_at_client' => now()->toIso8601String(),
                'updated_at_client' => now()->toIso8601String(),
            ],
        ],
    ]);

    $financeResponse->assertOk();
    $financeResponse->assertJsonPath('data.sync_results.0.id', $financeId);
    $financeResponse->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    // ── Step 1b: ABK A pushes Maintenance Report (Kerusakan) ──────
    // ABK can create new maintenance logs (Scenario A in service logic).
    Sanctum::actingAs($this->abkA, ['*']);

    $maintenanceResponse = $this->postJson('/api/v1/sync/maintenances', [
        'sync_timestamp' => now()->toIso8601String(),
        'maintenances' => [
            [
                'id' => $maintenanceId,
                'floor_id' => $this->floorA->id,
                'description' => 'Dinamo Kipas Terbakar',
                'status' => 'REPORTED',
                'image_path_local' => null,
                'created_at_client' => now()->toIso8601String(),
                'updated_at_client' => now()->toIso8601String(),
            ],
        ],
    ]);

    $maintenanceResponse->assertOk();
    $maintenanceResponse->assertJsonPath('data.sync_results.0.id', $maintenanceId);
    $maintenanceResponse->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    // ══════════════════════════════════════════════════════════════
    // PHASE 2 — Data Isolation Test (Actor: ABK Kandang B)
    // ABK B is assigned ONLY to Coop B, which has no data pushed.
    // ══════════════════════════════════════════════════════════════

    Sanctum::actingAs($this->abkB, ['*']);

    // ── Step 2a: ABK B pulls Finances — must see empty transactions ──
    // ABK role always receives an empty `transactions` array (role-level
    // data isolation in FinanceSyncService). Additionally, $abkB has no
    // coop assignment to Coop A, so even if the role allowed it, no
    // Coop A data would be in scope.
    $pullFinanceBResponse = $this->getJson('/api/v1/sync/finances');

    $pullFinanceBResponse->assertOk();
    $pullFinanceBResponse->assertJsonCount(0, 'data.transactions');

    // ── Step 2b: ABK B pulls Maintenances — must see empty list ──
    // Maintenance logs are scoped to floors belonging to the user's
    // assigned coops. Coop B has no logs, so the list must be empty.
    $pullMaintenanceBResponse = $this->getJson('/api/v1/sync/maintenances');

    $pullMaintenanceBResponse->assertOk();
    $pullMaintenanceBResponse->assertJsonCount(0, 'data.maintenance_logs');

    // ══════════════════════════════════════════════════════════════
    // PHASE 3 — Verify Pull Sync (Actor: PIC Kandang A)
    // Switch back to the originating actor to confirm data is present.
    // ══════════════════════════════════════════════════════════════

    Sanctum::actingAs($this->picA, ['*']);

    // ── Step 3a: PIC A pulls Finances — must see the pushed expense ──
    $pullFinanceAResponse = $this->getJson('/api/v1/sync/finances');

    $pullFinanceAResponse->assertOk();
    $pullFinanceAResponse->assertJsonCount(1, 'data.transactions');
    $pullFinanceAResponse->assertJsonPath('data.transactions.0.id', $financeId);
    $pullFinanceAResponse->assertJsonPath('data.transactions.0.description', 'Beli Sapu Lidi');

    // ══════════════════════════════════════════════════════════════
    // PHASE 4 — Database Physical Verification
    // ══════════════════════════════════════════════════════════════

    // Finance record must exist with correct amount and period linkage
    $this->assertDatabaseHas('transactions', [
        'id' => $financeId,
        'period_id' => $this->periodA->id,
        'amount' => 50000,
        'description' => 'Beli Sapu Lidi',
        'sync_status' => 'SYNCED',
    ]);

    // Maintenance log must exist and be linked to Coop A's floor
    $this->assertDatabaseHas('maintenance_logs', [
        'id' => $maintenanceId,
        'floor_id' => $this->floorA->id,
        'description' => 'Dinamo Kipas Terbakar',
        'status' => 'REPORTED',
        'reported_by' => $this->abkA->id,
        'sync_status' => 'SYNCED',
    ]);
});

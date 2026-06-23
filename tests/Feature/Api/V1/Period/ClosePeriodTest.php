<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * Buat state lengkap: Coop → Floor → Period (active) + assignment user ke Coop.
 */
function createPeriodWithAssignment(User $user): ProductionPeriod
{
    $coop  = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    CoopUserAssignment::factory()->create([
        'user_id' => $user->id,
        'coop_id' => $coop->id,
    ]);

    return ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'pic_id'   => $user->id,
        'status'   => 'active',
    ]);
}

/**
 * Payload standar untuk menutup periode.
 */
function closePayload(array $overrides = []): array
{
    return array_merge([
        'closing_reason' => 'Panen selesai, kandang sudah kosong dan dibersihkan.',
        'sync_timestamp' => now()->toIso8601String(),
    ], $overrides);
}

// ──────────────────────────────────────────────────────────────
// Test Suite
// ──────────────────────────────────────────────────────────────

describe('POST /api/v1/periods/{id}/close', function () {

    // ── Test 1: Role ABK attempt (403 Forbidden) ──

    it('Test 1 — Role abk mendapat 403 Forbidden', function () {
        $abk    = User::factory()->create(['role' => 'abk']);
        $period = createPeriodWithAssignment($abk);

        $response = $this->actingAs($abk)
            ->postJson("/api/v1/periods/{$period->id}/close", closePayload());

        $response->assertForbidden();
        $response->assertJsonPath('success', false);

        // Period TIDAK boleh berubah status
        $this->assertDatabaseHas('production_periods', [
            'id'     => $period->id,
            'status' => 'active',
        ]);
    });

    it('Test 1b — Role investor mendapat 403 Forbidden', function () {
        $investor = User::factory()->create(['role' => 'investor']);
        $coop     = Coop::factory()->create();
        $floor    = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $period   = ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'status'   => 'active',
        ]);

        $response = $this->actingAs($investor)
            ->postJson("/api/v1/periods/{$period->id}/close", closePayload());

        $response->assertForbidden();
    });

    // ── Test 2: PIC closes with pending DailyActivityHeader (422) ──

    it('Test 2 — PIC tidak bisa tutup periode yang punya aktivitas harian berstatus SUBMITTED', function () {
        $pic    = User::factory()->create(['role' => 'pic']);
        $period = createPeriodWithAssignment($pic);

        // Buat DailyActivityHeader dengan status SUBMITTED — menggantung!
        DailyActivityHeader::factory()->create([
            'period_id'       => $period->id,
            'user_id'         => $pic->id,
            'business_status' => 'SUBMITTED',
        ]);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/close", closePayload());

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        // Period TIDAK boleh berubah
        $this->assertDatabaseHas('production_periods', [
            'id'     => $period->id,
            'status' => 'active',
        ]);
    });

    it('Test 2b — PIC tidak bisa tutup periode yang punya transaksi berstatus DRAFT', function () {
        $pic      = User::factory()->create(['role' => 'pic']);
        $period   = createPeriodWithAssignment($pic);
        $category = TransactionCategory::factory()->create();

        // Buat Transaksi dengan status DRAFT — menggantung!
        Transaction::factory()->create([
            'period_id'       => $period->id,
            'user_id'         => $pic->id,
            'category_id'     => $category->id,
            'business_status' => 'DRAFT',
        ]);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/close", closePayload());

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        $this->assertDatabaseHas('production_periods', [
            'id'     => $period->id,
            'status' => 'active',
        ]);
    });

    // ── Test 3: PIC closes valid period with all APPROVED data (200 OK) ──

    it('Test 3 — PIC berhasil menutup periode dengan semua data sudah APPROVED', function () {
        $pic      = User::factory()->create(['role' => 'pic']);
        $period   = createPeriodWithAssignment($pic);
        $category = TransactionCategory::factory()->create();

        // Semua aktivitas sudah APPROVED — clean!
        DailyActivityHeader::factory()->create([
            'period_id'       => $period->id,
            'user_id'         => $pic->id,
            'business_status' => 'APPROVED',
        ]);

        // Semua transaksi sudah APPROVED — clean!
        Transaction::factory()->create([
            'period_id'       => $period->id,
            'user_id'         => $pic->id,
            'category_id'     => $category->id,
            'business_status' => 'APPROVED',
        ]);

        $closingReason = 'Panen selesai, kandang sudah kosong dan dibersihkan.';

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/close", [
                'closing_reason' => $closingReason,
                'sync_timestamp' => now()->toIso8601String(),
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'completed');
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'status', 'closed_at', 'closing_reason'],
        ]);

        // Verifikasi DB changes
        $this->assertDatabaseHas('production_periods', [
            'id'             => $period->id,
            'status'         => 'completed',
            'closing_reason' => $closingReason,
        ]);

        // Pastikan closed_at terisi
        $updatedPeriod = ProductionPeriod::find($period->id);
        expect($updatedPeriod->closed_at)->not->toBeNull();
    });

    // ── Additional edge cases ──

    it('Mengembalikan 400 jika periode sudah completed (double-close)', function () {
        $pic    = User::factory()->create(['role' => 'pic']);
        $period = createPeriodWithAssignment($pic);

        // Set periode langsung ke completed
        $period->update(['status' => 'completed', 'closed_at' => now()]);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/close", closePayload());

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
    });

    it('PIC tanpa assignment ke coop mendapat 403', function () {
        $pic   = User::factory()->create(['role' => 'pic']);
        $coop  = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

        // Period milik coop yang TIDAK diassign ke $pic
        $period = ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'status'   => 'active',
        ]);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/close", closePayload());

        $response->assertForbidden();
    });

    it('Manager berhasil menutup periode tanpa perlu assignment (global access)', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $coop    = Coop::factory()->create();
        $floor   = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $period  = ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'status'   => 'active',
        ]);

        // Manager tidak perlu assignment

        $response = $this->actingAs($manager)
            ->postJson("/api/v1/periods/{$period->id}/close", closePayload());

        $response->assertOk();
        $response->assertJsonPath('data.status', 'completed');
    });

    it('Mengembalikan 404 jika period ID tidak ditemukan', function () {
        $pic = User::factory()->create(['role' => 'pic']);

        $response = $this->actingAs($pic)
            ->postJson('/api/v1/periods/' . Str::uuid() . '/close', closePayload());

        $response->assertNotFound();
    });

    it('Mengembalikan 422 jika closing_reason kurang dari 5 karakter', function () {
        $pic = User::factory()->create(['role' => 'pic']);

        $coop  = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $period = ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'status'   => 'active',
        ]);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/close", [
                'closing_reason' => 'ok',
                'sync_timestamp' => now()->toIso8601String(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('closing_reason');
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->postJson('/api/v1/periods/' . Str::uuid() . '/close', closePayload());
        $response->assertUnauthorized();
    });
});

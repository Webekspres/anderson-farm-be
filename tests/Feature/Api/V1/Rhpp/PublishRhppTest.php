<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\RhppDocument;
use App\Models\PeriodInvestor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function createCompletedPeriod(?User $pic = null): ProductionPeriod
{
    $coop  = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
    $pic   ??= User::factory()->create();

    return ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'pic_id'   => $pic->id,
        'status'   => 'completed',
    ]);
}

describe('POST /api/v1/rhpps/{period_id}/publish', function () {

    it('Test 1 — Role pic mendapat 403 Forbidden', function () {
        $pic    = User::factory()->create(['role' => 'pic']);
        $period = createCompletedPeriod($pic);

        $response = $this->actingAs($pic)->postJson(
            "/api/v1/rhpps/{$period->id}/publish",
            ['sync_timestamp' => now()->toIso8601String()]
        );

        $response->assertForbidden();
        $response->assertJsonPath('success', false);
    });

    it('Test 2 — Manager mendapat 400 jika tidak ada dokumen RHPP', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period  = createCompletedPeriod();

        // Buat Rhpp tapi tanpa document
        Rhpp::factory()->create([
            'period_id' => $period->id,
            'publish_status' => 'DRAFT',
        ]);

        $response = $this->actingAs($manager)->postJson(
            "/api/v1/rhpps/{$period->id}/publish",
            ['sync_timestamp' => now()->toIso8601String()]
        );

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Tidak dapat publish: Dokumen PDF RHPP harus diunggah terlebih dahulu.');
    });

    it('Test 3 — Success & Calculation', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period  = createCompletedPeriod();

        $rhpp = Rhpp::factory()->create([
            'period_id' => $period->id,
            'net_profit' => 10000000,
            'publish_status' => 'DRAFT',
        ]);

        RhppDocument::factory()->create([
            'Rhpp_id' => $rhpp->id,
        ]);

        $investorUserA = User::factory()->create(['role' => 'investor']);
        $investorA = PeriodInvestor::factory()->create([
            'period_id' => $period->id,
            'user_id' => $investorUserA->id,
            'profit_share_percentage' => 20,
            'final_dividend_amount' => null,
            'is_paid' => false,
        ]);

        $investorUserB = User::factory()->create(['role' => 'investor']);
        $investorB = PeriodInvestor::factory()->create([
            'period_id' => $period->id,
            'user_id' => $investorUserB->id,
            'profit_share_percentage' => 30,
            'final_dividend_amount' => null,
            'is_paid' => false,
        ]);

        $response = $this->actingAs($manager)->postJson(
            "/api/v1/rhpps/{$period->id}/publish",
            ['sync_timestamp' => now()->toIso8601String()]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.publish_status', 'PUBLISHED');
        $response->assertJsonPath('data.net_profit', 10000000);

        // Assert Rhpp status
        $this->assertDatabaseHas('rhpps', [
            'id' => $rhpp->id,
            'publish_status' => 'PUBLISHED',
        ]);

        // Assert PeriodInvestor dividend calculated
        $this->assertDatabaseHas('period_investors', [
            'id' => $investorA->id,
            'final_dividend_amount' => 10000000 * 20 / 100, // 2000000
            'is_paid' => false,
        ]);

        $this->assertDatabaseHas('period_investors', [
            'id' => $investorB->id,
            'final_dividend_amount' => 10000000 * 30 / 100, // 3000000
            'is_paid' => false,
        ]);
    });

    it('returns 404 if rhpp not found', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period  = createCompletedPeriod();

        $response = $this->actingAs($manager)->postJson(
            "/api/v1/rhpps/{$period->id}/publish",
            ['sync_timestamp' => now()->toIso8601String()]
        );

        $response->assertNotFound();
    });

    it('returns 400 if already published', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period  = createCompletedPeriod();

        $rhpp = Rhpp::factory()->create([
            'period_id' => $period->id,
            'publish_status' => 'PUBLISHED',
        ]);

        $response = $this->actingAs($manager)->postJson(
            "/api/v1/rhpps/{$period->id}/publish",
            ['sync_timestamp' => now()->toIso8601String()]
        );

        $response->assertStatus(400);
    });

});

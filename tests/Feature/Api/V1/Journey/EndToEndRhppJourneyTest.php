<?php

declare(strict_types=1);

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\Farm;
use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────────────────────
// Shared Setup
// ──────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Storage::fake('public');

    // ── Infrastructure ──
    $this->farm = Farm::factory()->create();
    $this->coop = Coop::factory()->create(['farm_id' => $this->farm->id]);
    $this->floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);

    // Active production period tied to this coop's floor
    $this->period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floor->id,
        'status' => 'active',
    ]);

    // ── Users ──
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->investor = User::factory()->create(['role' => 'investor']);

    // ── Investor assignment: 25% profit share ──
    $this->periodInvestor = PeriodInvestor::factory()->create([
        'period_id' => $this->period->id,
        'user_id' => $this->investor->id,
        'profit_share_percentage' => 25.00,
        'final_dividend_amount' => null,
        'is_paid' => false,
    ]);

    // ── Dummy APPROVED transaction ──
    // ClosePeriod blocks any Transaction with business_status in
    // [DRAFT, SUBMITTED, NEEDS_REVIEW]. Using APPROVED bypasses the gate,
    // while still giving the period realistic financial data to close on.
    $category = TransactionCategory::factory()->create(['type' => 'EXPENSE']);
    Transaction::factory()->create([
        'period_id' => $this->period->id,
        'category_id' => $category->id,
        'business_status' => 'APPROVED',
        'amount' => 5_000_000,
    ]);
});

// ──────────────────────────────────────────────────────────────────────────────
// Journey Test
// ──────────────────────────────────────────────────────────────────────────────

it('successfully completes the end-of-cycle journey from closing the period to publishing the RHPP and distributing dividends', function () {

    $periodId = $this->period->id;

    // ══════════════════════════════════════════════════════════════
    // PHASE 1 — Close the Period (Tutup Buku)
    // Actor: Manager
    // ══════════════════════════════════════════════════════════════

    Sanctum::actingAs($this->manager, ['*']);

    $closeResponse = $this->postJson("/api/v1/periods/{$periodId}/close", [
        'closing_reason' => 'Panen selesai, kandang sudah kosong dan bersih.',
        'sync_timestamp' => now()->toIso8601String(),
    ]);

    $closeResponse->assertOk();
    $closeResponse->assertJsonPath('success', true);

    // Period must now be locked as 'completed'
    $this->assertDatabaseHas('production_periods', [
        'id' => $periodId,
        'status' => 'completed',
    ]);

    // No Rhpp record exists yet — it is created in the rhpp-documents step
    $this->assertDatabaseCount('rhpps', 0);

    // ══════════════════════════════════════════════════════════════
    // PHASE 2 — Upload & Attach the Final RHPP Document
    //
    // NOTE: The `POST /api/v1/periods/{id}/rhpp-documents` endpoint
    // combines both file upload and Rhpp shell creation in a single call.
    // It accepts a `document` (PDF file) together with the three financial
    // figures. There is no intermediate step via `POST /api/v1/uploads`.
    // ══════════════════════════════════════════════════════════════

    $totalIncome = 20_000_000;
    $totalExpense = 12_000_000;
    $netProfit = 8_000_000;

    $rhppDocResponse = $this->postJson("/api/v1/periods/{$periodId}/rhpp-documents", [
        'document' => UploadedFile::fake()->create('Laporan_RHPP_Final.pdf', 1500, 'application/pdf'),
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'net_profit' => $netProfit,
    ]);

    $rhppDocResponse->assertOk();
    $rhppDocResponse->assertJsonPath('success', true);
    $rhppDocResponse->assertJsonStructure([
        'success', 'message',
        'data' => ['rhpp_id', 'document_id', 'file_url', 'publish_status'],
    ]);

    // Rhpp shell must now exist with status DRAFT and the correct financials
    $this->assertDatabaseHas('rhpps', [
        'period_id' => $periodId,
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'net_profit' => $netProfit,
        'publish_status' => 'DRAFT',
    ]);

    // The supporting PDF document must be recorded
    $rhppId = $rhppDocResponse->json('data.rhpp_id');
    $fileUrl = $rhppDocResponse->json('data.file_url');

    $this->assertDatabaseHas('rhpp_documents', [
        'Rhpp_id' => $rhppId,
        'file_url' => $fileUrl,
    ]);

    // Capture the net_profit from the newly created Rhpp for dividend math below
    $rhpp = Rhpp::where('period_id', $periodId)->firstOrFail();
    $netProfit = $rhpp->net_profit;

    // ══════════════════════════════════════════════════════════════
    // PHASE 3 — Publish & Execute Dividends ("Ketok Palu")
    // ══════════════════════════════════════════════════════════════

    $publishResponse = $this->postJson("/api/v1/rhpps/{$periodId}/publish", [
        'sync_timestamp' => '2026-05-04T10:00:00Z',
    ]);

    $publishResponse->assertOk();
    $publishResponse->assertJsonPath('success', true);
    $publishResponse->assertJsonPath('data.publish_status', 'PUBLISHED');
    // JSON may encode a whole-number float as an integer (8000000 vs 8000000.0).
    // Use loose equality to avoid a strict-type mismatch between PHP double and JSON int.
    expect($publishResponse->json('data.net_profit'))->toEqual($netProfit);

    // ══════════════════════════════════════════════════════════════
    // PHASE 4 — Critical Financial Database Assertions
    // ══════════════════════════════════════════════════════════════

    // ── 4a: RHPP status must be PUBLISHED ──
    $this->assertDatabaseHas('rhpps', [
        'id' => $rhppId,
        'period_id' => $periodId,
        'publish_status' => 'PUBLISHED',
    ]);

    // ── 4b: Dividend math — investor holds exactly 25% of net profit ──
    $expectedDividend = ($netProfit * 25) / 100;

    $this->assertDatabaseHas('period_investors', [
        'id' => $this->periodInvestor->id,
        'user_id' => $this->investor->id,
        'final_dividend_amount' => $expectedDividend,
        // Money is calculated, NOT yet physically transferred
        'is_paid' => false,
    ]);
});

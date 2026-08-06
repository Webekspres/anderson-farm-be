<?php

declare(strict_types=1);

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
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
// Shared setup
// ──────────────────────────────────────────────────────────────────────────────

beforeEach(function (): void {
    Storage::fake('public');
    config(['filesystems.uploads' => 'public']);

    $this->farm = Farm::factory()->create();
    $this->coop = Coop::factory()->create(['farm_id' => $this->farm->id]);
    $this->floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);

    $this->period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floor->id,
        'status' => 'active',
    ]);

    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->investor = User::factory()->create(['role' => 'investor']);

    CoopUserAssignment::factory()->create([
        'user_id' => $this->manager->id,
        'coop_id' => $this->coop->id,
    ]);

    $this->periodInvestor = PeriodInvestor::factory()->create([
        'period_id' => $this->period->id,
        'user_id' => $this->investor->id,
        'profit_share_percentage' => 30.00,
        'final_dividend_amount' => null,
        'is_paid' => false,
    ]);

    // Close-period pre-flight: no pending transactions (must not be DRAFT/SUBMITTED/NEEDS_REVIEW).
    $category = TransactionCategory::factory()->create(['type' => 'INCOME']);
    Transaction::factory()->create([
        'period_id' => $this->period->id,
        'category_id' => $category->id,
        'business_status' => 'APPROVED',
        'amount' => 15_000_000,
        'description' => 'Dummy pendapatan periode (setup)',
    ]);
});

// ──────────────────────────────────────────────────────────────────────────────
// E2E journey
// ──────────────────────────────────────────────────────────────────────────────

it('successfully completes the end-of-cycle journey, publishes RHPP, and calculates investor dividends accurately', function (): void {

    $periodId = $this->period->id;

    // Fixed scenario: laba bersih RHPP final = Rp 10.000.000 (di-set saat unggah dokumen RHPP).
    $totalIncome = 18_000_000;
    $totalExpense = 8_000_000;
    $netProfitScenario = 10_000_000;

    Sanctum::actingAs($this->manager, ['*']);

    // ═══════════════════════════════════════════════════════════════════════
    // PHASE 1 — Tutup periode produksi (Actor: Manager)
    // Catatan implementasi: kolom status memakai nilai `completed`, bukan literal `closed`.
    // Baris RHPP di DB baru dibuat pada langkah `rhpp-documents`, bukan otomatis di sini.
    // ═══════════════════════════════════════════════════════════════════════

    $closeResponse = $this->postJson("/api/v1/periods/{$periodId}/close", [
        'closing_reason' => 'Panen selesai, period siap ditutup untuk RHPP final.',
        'sync_timestamp' => now()->toIso8601String(),
    ]);

    $closeResponse->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('production_periods', [
        'id' => $periodId,
        'status' => 'completed',
    ]);

    $this->assertDatabaseCount('rhpps', 0);

    // ═══════════════════════════════════════════════════════════════════════
    // PHASE 2 — Unggah file via endpoint storage umum (cache URL untuk konteks bisnis)
    // Respons API memakai `data.image_url`, bukan `file_url`.
    // ═══════════════════════════════════════════════════════════════════════

    $uploadResponse = $this->post('/api/v1/uploads', [
        'file' => UploadedFile::fake()->create('RHPP_Final_Signed.pdf', 2000, 'application/pdf'),
        'folder' => 'rhpp',
    ]);

    $uploadResponse->assertCreated()
        ->assertJsonPath('success', true);

    $capturedFileUrl = $uploadResponse->json('data.image_url');
    expect($capturedFileUrl)->not->toBeEmpty();

    // ═══════════════════════════════════════════════════════════════════════
    // PHASE 3 — Lampirkan dokumen RHPP resmi
    // Backend memerlukan multipart `document` (PDF) + total_income/total_expense/net_profit.
    // Tidak ada endpoint yang menerima JSON { name, file_url } saja untuk RHPP.
    // Variabel $capturedFileUrl membuktikan Phase 2; penyimpanan RHPP tetap lewat API ini.
    // ═══════════════════════════════════════════════════════════════════════

    expect($capturedFileUrl)->toBeString();

    $rhppDocResponse = $this->post("/api/v1/periods/{$periodId}/rhpp-documents", [
        'document' => UploadedFile::fake()->create('RHPP_Final_Signed.pdf', 2000, 'application/pdf'),
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'net_profit' => $netProfitScenario,
    ]);

    $rhppDocResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['rhpp_id', 'document_id', 'file_url', 'publish_status'],
        ]);

    $rhppId = $rhppDocResponse->json('data.rhpp_id');
    $rhppDocumentUrl = $rhppDocResponse->json('data.file_url');

    $this->assertDatabaseHas('rhpps', [
        'id' => $rhppId,
        'period_id' => $periodId,
        'net_profit' => $netProfitScenario,
        'publish_status' => 'DRAFT',
    ]);

    $this->assertDatabaseHas('rhpp_documents', [
        'Rhpp_id' => $rhppId,
        'file_url' => $rhppDocumentUrl,
    ]);

    $rhpp = Rhpp::query()->where('period_id', $periodId)->firstOrFail();
    $netProfit = $rhpp->net_profit;
    expect((float) $netProfit)->toBe((float) $netProfitScenario);

    // ═══════════════════════════════════════════════════════════════════════
    // PHASE 4 — Publish RHPP (“ketok palu”) & eksekusi dividen
    // ═══════════════════════════════════════════════════════════════════════

    $publishResponse = $this->postJson("/api/v1/rhpps/{$periodId}/publish", [
        'sync_timestamp' => now()->toIso8601String(),
    ]);

    $publishResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.publish_status', 'PUBLISHED');

    expect($publishResponse->json('data.net_profit'))->toEqual($netProfit);

    // ═══════════════════════════════════════════════════════════════════════
    // PHASE 5 — Verifikasi keuangan akhir (matematika dividen ketat)
    // ═══════════════════════════════════════════════════════════════════════

    $this->assertDatabaseHas('rhpps', [
        'id' => $rhppId,
        'period_id' => $periodId,
        'publish_status' => 'PUBLISHED',
    ]);

    $expectedDividend = ($netProfit * 30) / 100;

    $this->assertDatabaseHas('period_investors', [
        'id' => $this->periodInvestor->id,
        'period_id' => $periodId,
        'user_id' => $this->investor->id,
        'final_dividend_amount' => $expectedDividend,
        'is_paid' => false,
    ]);
});

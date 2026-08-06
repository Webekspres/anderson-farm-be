<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\RhppDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * Buat periode dengan status tertentu beserta hierarki Coop → Floor.
 */
function createPeriodWithStatus(string $status, ?User $pic = null): ProductionPeriod
{
    $coop = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
    $pic ??= User::factory()->create();

    return ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'pic_id' => $pic->id,
        'status' => $status,
    ]);
}

/**
 * Buat UploadedFile PDF palsu untuk testing.
 */
function fakePdf(string $name = 'rhpp-final.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, 500, 'application/pdf');
}

/**
 * Payload standar untuk upload RHPP.
 */
function rhppPayload(array $overrides = []): array
{
    return array_merge([
        'total_income' => 15000000,
        'total_expense' => 10000000,
        'net_profit' => 5000000,
    ], $overrides);
}

// ──────────────────────────────────────────────────────────────
// Test Suite
// ──────────────────────────────────────────────────────────────

describe('POST /api/v1/periods/{id}/rhpp-documents', function () {

    beforeEach(function () {
        config(['filesystems.uploads' => 'public']);
        Storage::fake('public');
    });

    // ── Test 1: Auth Fail — role pic mendapat 403 ──

    it('Test 1 — Role pic mendapat 403 Forbidden', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = createPeriodWithStatus('completed', $pic);

        $response = $this->actingAs($pic)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            rhppPayload()
        );

        $response->assertForbidden();
        $response->assertJsonPath('success', false);
    });

    it('Test 1b — Role abk mendapat 403 Forbidden', function () {
        $abk = User::factory()->create(['role' => 'abk']);
        $period = createPeriodWithStatus('completed');

        $response = $this->actingAs($abk)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            rhppPayload()
        );

        $response->assertForbidden();
    });

    it('Test 1c — Role investor mendapat 403 Forbidden', function () {
        $investor = User::factory()->create(['role' => 'investor']);
        $period = createPeriodWithStatus('completed');

        $response = $this->actingAs($investor)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            rhppPayload()
        );

        $response->assertForbidden();
    });

    // ── Test 2: State Fail — periode masih active mendapat 400 ──

    it('Test 2 — Manager mendapat 400 jika periode masih active', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period = createPeriodWithStatus('active');

        $response = $this->actingAs($manager)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            array_merge(rhppPayload(), ['document' => fakePdf()])
        );

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);

        // Tidak boleh ada Rhpp yang terbuat
        $this->assertDatabaseCount('rhpps', 0);
        $this->assertDatabaseCount('rhpp_documents', 0);
    });

    // ── Test 3: Happy Path — upload berhasil (Shell Creation) ──

    it('Test 3 — Manager berhasil upload dokumen RHPP ke periode completed', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period = createPeriodWithStatus('completed');
        $pdf = fakePdf('rhpp-final.pdf');

        $response = $this->actingAs($manager)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            array_merge(rhppPayload([
                'total_income' => 20000000,
                'total_expense' => 12000000,
                'net_profit' => 8000000,
            ]), ['document' => $pdf])
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['rhpp_id', 'document_id', 'file_url', 'publish_status'],
        ]);

        // Assert Rhpp terbuat di DB dengan angka yang benar
        $this->assertDatabaseHas('rhpps', [
            'period_id' => $period->id,
            'total_income' => 20000000,
            'total_expense' => 12000000,
            'net_profit' => 8000000,
            'publish_status' => 'DRAFT',
        ]);

        // Assert RhppDocument terbuat
        $rhppId = $response->json('data.rhpp_id');
        $this->assertDatabaseHas('rhpp_documents', [
            'Rhpp_id' => $rhppId,
            'file_type' => 'pdf',
        ]);

        // Assert nama dokumen mengandung period_code
        $document = RhppDocument::where('Rhpp_id', $rhppId)->first();
        expect($document->name)->toContain($period->period_code);

        // Assert file benar-benar tersimpan di fake storage
        expect($document->file_path_local)->toStartWith("rhpp/{$period->id}/");
        Storage::disk('public')->assertExists($document->file_path_local);
    });

    // ── Test 4: Upsert — upload ulang tidak membuat Rhpp duplikat ──

    it('Test 4 — Upload kedua memperbarui angka Rhpp tanpa membuat duplikat', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period = createPeriodWithStatus('completed');

        // Upload pertama
        $this->actingAs($manager)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            array_merge(rhppPayload(['net_profit' => 5000000]), ['document' => fakePdf('first.pdf')])
        );

        // Upload kedua dengan angka berbeda
        $this->actingAs($manager)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            array_merge(rhppPayload(['net_profit' => 9000000]), ['document' => fakePdf('second.pdf')])
        );

        // Hanya boleh ada 1 Rhpp (upsert)
        $this->assertDatabaseCount('rhpps', 1);

        // Tapi 2 dokumen (setiap upload membuat record dokumen baru)
        $this->assertDatabaseCount('rhpp_documents', 2);

        // Angka Rhpp harus yang terbaru
        $this->assertDatabaseHas('rhpps', [
            'period_id' => $period->id,
            'net_profit' => 9000000,
        ]);
    });

    // ── Validasi ──

    it('Validasi: mengembalikan 422 jika field numerik tidak ada', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period = createPeriodWithStatus('completed');

        $response = $this->actingAs($manager)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            ['document' => fakePdf()]
            // total_income, total_expense, net_profit tidak diisi
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['total_income', 'total_expense', 'net_profit']);
    });

    it('Validasi: mengembalikan 422 jika dokumen bukan PDF', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period = createPeriodWithStatus('completed');

        $fakeImage = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($manager)->postJson(
            "/api/v1/periods/{$period->id}/rhpp-documents",
            array_merge(rhppPayload(), ['document' => $fakeImage])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('document');
    });

    it('returns 401 when not authenticated', function () {
        $period = createPeriodWithStatus('completed');

        $this->postJson("/api/v1/periods/{$period->id}/rhpp-documents", rhppPayload())
            ->assertUnauthorized();
    });
});

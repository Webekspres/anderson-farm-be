<?php

declare(strict_types=1);

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->farm = Farm::factory()->create();
    $this->coop = Coop::factory()->create(['farm_id' => $this->farm->id]);
    $this->floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);

    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->investor = User::factory()->create(['role' => 'investor']);
    $this->abk = User::factory()->create(['role' => 'abk']);

    CoopUserAssignment::factory()->create([
        'user_id' => $this->manager->id,
        'coop_id' => $this->coop->id,
    ]);
    CoopUserAssignment::factory()->create([
        'user_id' => $this->abk->id,
        'coop_id' => $this->coop->id,
    ]);
});

it('successfully completes period setup journey with an investor', function () {
    // SyncPeriodInvestorRequest melarang sync investor jika start_date <= hari ini (dianggap periode sudah berjalan).
    $startDate = now()->addDay()->toDateString();

    Sanctum::actingAs($this->manager, ['*']);

    $periodResponse = $this->postJson('/api/v1/periods', [
        'floor_id' => $this->floor->id,
        'pic_id' => $this->manager->id,
        'start_date' => $startDate,
        'initial_stock' => 5000,
        'created_at_client' => now()->toIso8601String(),
    ]);

    $periodResponse->assertCreated()
        ->assertJsonPath('success', true);

    $periodId = $periodResponse->json('data.id');
    expect($periodId)->not->toBeEmpty();

    $investorResponse = $this->postJson("/api/v1/periods/{$periodId}/investors", [
        'investors' => [
            [
                'user_id' => $this->investor->id,
                'profit_share_percentage' => 40,
            ],
        ],
    ]);

    $investorResponse->assertOk()
        ->assertJsonPath('success', true);

    $uploadResponse = $this->post('/api/v1/uploads', [
        'file' => UploadedFile::fake()->create('spk_kemitraan.pdf', 1000, 'application/pdf'),
        'folder' => 'contracts',
    ]);

    $uploadResponse->assertCreated()
        ->assertJsonPath('success', true);

    $fileUrl = $uploadResponse->json('data.image_url');
    expect($fileUrl)->not->toBeEmpty();

    $contractResponse = $this->postJson("/api/v1/periods/{$periodId}/contracts", [
        'title' => 'SPK Kemitraan Lantai 1',
        'file_url' => $fileUrl,
    ]);

    $contractResponse->assertCreated()
        ->assertJsonPath('success', true);

    $contractId = $contractResponse->json('data.id');
    expect($contractId)->not->toBeEmpty();

    Sanctum::actingAs($this->abk, ['*']);

    $clientAcceptanceId = (string) Str::uuid();
    $acceptedAt = now()->utc()->format('Y-m-d\TH:i:s\Z');

    $syncResponse = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [
            [
                'id' => $clientAcceptanceId,
                'contract_id' => $contractId,
                'accepted_at' => $acceptedAt,
            ],
        ],
    ]);

    $syncResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.sync_results.0.id', $clientAcceptanceId)
        ->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    $this->assertDatabaseHas('production_periods', [
        'id' => $periodId,
        'initial_stock' => 5000,
    ]);

    $this->assertDatabaseHas('period_investors', [
        'period_id' => $periodId,
        'user_id' => $this->investor->id,
        'profit_share_percentage' => 40,
    ]);

    $this->assertDatabaseHas('contract_abks', [
        'id' => $contractId,
        'period_id' => $periodId,
        'title' => 'SPK Kemitraan Lantai 1',
    ]);

    $this->assertDatabaseHas('contract_acceptances', [
        'contract_id' => $contractId,
        'user_id' => $this->abk->id,
    ]);
});

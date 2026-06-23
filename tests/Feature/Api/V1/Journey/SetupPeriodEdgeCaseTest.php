<?php

declare(strict_types=1);

use App\Models\ContractAbk;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\Farm;
use App\Models\ProductionPeriod;
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
    $this->coopA = Coop::factory()->create(['farm_id' => $this->farm->id]);
    $this->coopB = Coop::factory()->create(['farm_id' => $this->farm->id]);
    $this->floorA = CoopFloor::factory()->create(['coop_id' => $this->coopA->id]);
    $this->floorB = CoopFloor::factory()->create(['coop_id' => $this->coopB->id]);

    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->investor = User::factory()->create(['role' => 'investor']);
    $this->abk_A = User::factory()->create(['role' => 'abk']);
    $this->abk_B = User::factory()->create(['role' => 'abk']);

    CoopUserAssignment::factory()->create([
        'user_id' => $this->abk_A->id,
        'coop_id' => $this->coopA->id,
    ]);
    CoopUserAssignment::factory()->create([
        'user_id' => $this->abk_B->id,
        'coop_id' => $this->coopB->id,
    ]);
});

/** SyncPeriodInvestorRequest mensyaratkan start_date setelah hari ini agar investor bisa di-assign. */
function edgeCaseFuturePeriodForInvestors(CoopFloor $floor, User $manager): ProductionPeriod
{
    return ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'pic_id' => $manager->id,
        'status' => 'active',
        'start_date' => now()->addDays(2)->toDateString(),
    ]);
}

it('rejects creating a new period if the floor already has an active period', function () {
    ProductionPeriod::factory()->create([
        'floor_id' => $this->floorA->id,
        'pic_id' => $this->manager->id,
        'status' => 'active',
    ]);

    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->postJson('/api/v1/periods', [
        'floor_id' => $this->floorA->id,
        'pic_id' => $this->manager->id,
        'start_date' => now()->addDay()->toDateString(),
        'initial_stock' => 3000,
        'created_at_client' => now()->toIso8601String(),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['floor_id']);
});

it('rejects investor assignment if total profit share exceeds 100 percent', function () {
    $period = edgeCaseFuturePeriodForInvestors($this->floorA, $this->manager);

    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->postJson("/api/v1/periods/{$period->id}/investors", [
        'investors' => [
            [
                'user_id' => $this->investor->id,
                'profit_share_percentage' => 150,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['investors.0.profit_share_percentage']);
});

it('rejects investor assignment if the assigned user is not an investor', function () {
    $period = edgeCaseFuturePeriodForInvestors($this->floorA, $this->manager);

    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->postJson("/api/v1/periods/{$period->id}/investors", [
        'investors' => [
            [
                'user_id' => $this->abk_A->id,
                'profit_share_percentage' => 40,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['investors']);
});

it('rejects contract upload if file is not a valid PDF', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->postJson('/api/v1/uploads', [
        'file' => UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload'),
        'folder' => 'contracts',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('forbids abk from creating a contract record', function () {
    $period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floorA->id,
        'pic_id' => $this->manager->id,
        'status' => 'active',
        'start_date' => now()->addDay()->toDateString(),
    ]);

    Sanctum::actingAs($this->abk_A, ['*']);

    $response = $this->postJson("/api/v1/periods/{$period->id}/contracts", [
        'title' => 'Kontrak Ilegal ABK',
        'file_url' => 'https://example.com/files/kontrak.pdf',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null);
});

it('returns FORBIDDEN in sync results if ABK accepts a contract outside their assigned coop', function () {
    $periodB = ProductionPeriod::factory()->create([
        'floor_id' => $this->floorB->id,
        'pic_id' => $this->manager->id,
        'status' => 'active',
        'start_date' => now()->addDay()->toDateString(),
    ]);

    $contractB = ContractAbk::factory()->create([
        'period_id' => $periodB->id,
        'uploaded_by' => $this->manager->id,
    ]);

    Sanctum::actingAs($this->abk_A, ['*']);

    $clientAcceptanceId = (string) Str::uuid();
    $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => $timestamp,
        'contract_acceptances' => [
            [
                'id' => $clientAcceptanceId,
                'contract_id' => $contractB->id,
                'accepted_at' => $timestamp,
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.sync_results.0.status', 'FORBIDDEN');

    $this->assertDatabaseMissing('contract_acceptances', [
        'contract_id' => $contractB->id,
        'user_id' => $this->abk_A->id,
    ]);
});

it('returns FAILED in sync results if contract ID does not exist', function () {
    Sanctum::actingAs($this->abk_A, ['*']);

    $clientAcceptanceId = (string) Str::uuid();
    $ghostContractId = (string) Str::uuid();
    $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => $timestamp,
        'contract_acceptances' => [
            [
                'id' => $clientAcceptanceId,
                'contract_id' => $ghostContractId,
                'accepted_at' => $timestamp,
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.sync_results.0.status', 'FAILED');

    $this->assertDatabaseMissing('contract_acceptances', [
        'id' => $clientAcceptanceId,
    ]);
});

it('prevents duplicate contract acceptances on double submit', function () {
    $period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floorA->id,
        'pic_id' => $this->manager->id,
        'status' => 'active',
        'start_date' => now()->addDay()->toDateString(),
    ]);

    $contract = ContractAbk::factory()->create([
        'period_id' => $period->id,
        'uploaded_by' => $this->manager->id,
    ]);

    Sanctum::actingAs($this->abk_A, ['*']);

    $clientAcceptanceId = (string) Str::uuid();
    $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

    $payload = [
        'sync_timestamp' => $timestamp,
        'contract_acceptances' => [
            [
                'id' => $clientAcceptanceId,
                'contract_id' => $contract->id,
                'accepted_at' => $timestamp,
            ],
        ],
    ];

    $first = $this->postJson('/api/v1/sync/periods', $payload);
    $first->assertOk()
        ->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    $second = $this->postJson('/api/v1/sync/periods', $payload);
    $second->assertOk()
        ->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    $this->assertDatabaseCount('contract_acceptances', 1);
});

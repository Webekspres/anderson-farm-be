<?php

use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create coop with floor and period
    $this->coop = \App\Models\Coop::factory()->create();
    $this->floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);
    $this->period = ProductionPeriod::factory()->create(['floor_id' => $this->floor->id]);

    // Create contract
    $this->contract = ContractAbk::factory()->create(['period_id' => $this->period->id]);

    // Create ABK user
    $this->abk = User::factory()->create(['role' => 'abk']);

    // Create PIC user
    $this->pic = User::factory()->create(['role' => 'pic']);

    // Create admin user (should be denied)
    $this->admin = User::factory()->create(['role' => 'admin']);

    // Create investor user (should be denied)
    $this->investor = User::factory()->create(['role' => 'investor']);

    // Assign ABK to coop
    CoopUserAssignment::factory()->create([
        'user_id' => $this->abk->id,
        'coop_id' => $this->coop->id,
    ]);

    // Assign PIC to coop
    CoopUserAssignment::factory()->create([
        'user_id' => $this->pic->id,
        'coop_id' => $this->coop->id,
    ]);
});

it('berhasil mengirim persetujuan kontrak oleh ABK (200)', function () {
    Sanctum::actingAs($this->abk, ['*']);

    $acceptanceId = (string) \Illuminate\Support\Str::uuid();
    $acceptedAt = now()->format('Y-m-d\TH:i:s\Z');

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [
            [
                'id' => $acceptanceId,
                'contract_id' => $this->contract->id,
                'accepted_at' => $acceptedAt,
            ],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.sync_results.0.id', $acceptanceId)
        ->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    // Verify record was created in DB
    $this->assertDatabaseHas('contract_acceptances', [
        'id' => $acceptanceId,
        'contract_id' => $this->contract->id,
        'user_id' => $this->abk->id,
    ]);
});

it('berhasil mengirim persetujuan oleh PIC (200)', function () {
    Sanctum::actingAs($this->pic, ['*']);

    $acceptanceId = (string) \Illuminate\Support\Str::uuid();

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [
            [
                'id' => $acceptanceId,
                'contract_id' => $this->contract->id,
                'accepted_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    $this->assertDatabaseHas('contract_acceptances', [
        'contract_id' => $this->contract->id,
        'user_id' => $this->pic->id,
    ]);
});

it('menolak jika role bukan ABK atau PIC (403)', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'contract_id' => $this->contract->id,
                'accepted_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ],
        ],
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false);

    // Test investor role too
    Sanctum::actingAs($this->investor, ['*']);

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'contract_id' => $this->contract->id,
                'accepted_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ],
        ],
    ]);

    $response->assertStatus(403);
});

it('idempotent - mengirim payload yang sama dua kali hanya membuat satu record', function () {
    Sanctum::actingAs($this->abk, ['*']);

    $acceptanceId = (string) \Illuminate\Support\Str::uuid();
    $timestamp = now()->format('Y-m-d\TH:i:s\Z');
    $payload = [
        'sync_timestamp' => $timestamp,
        'contract_acceptances' => [
            [
                'id' => $acceptanceId,
                'contract_id' => $this->contract->id,
                'accepted_at' => $timestamp,
            ],
        ],
    ];

    // First submission
    $response1 = $this->postJson('/api/v1/sync/periods', $payload);
    $response1->assertStatus(200)
        ->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    // Second submission (identical payload)
    $response2 = $this->postJson('/api/v1/sync/periods', $payload);
    $response2->assertStatus(200)
        ->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

    // Verify only one record exists in DB (not two)
    $count = ContractAcceptance::where('id', $acceptanceId)
        ->where('contract_id', $this->contract->id)
        ->where('user_id', $this->abk->id)
        ->count();

    expect($count)->toBe(1);
});

it('mengembalikan FORBIDDEN jika ABK tidak assigned ke coop milik kontrak', function () {
    // Create new coop and period that ABK is NOT assigned to
    $anotherCoop = \App\Models\Coop::factory()->create();
    $anotherFloor = CoopFloor::factory()->create(['coop_id' => $anotherCoop->id]);
    $anotherPeriod = ProductionPeriod::factory()->create(['floor_id' => $anotherFloor->id]);
    $anotherContract = ContractAbk::factory()->create(['period_id' => $anotherPeriod->id]);

    Sanctum::actingAs($this->abk, ['*']);

    $acceptanceId = (string) \Illuminate\Support\Str::uuid();

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [
            [
                'id' => $acceptanceId,
                'contract_id' => $anotherContract->id,
                'accepted_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ],
        ],
    ]);

    // Response should be 200 with sync_results containing FORBIDDEN
    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.sync_results.0.status', 'FORBIDDEN');

    // Verify no record was created
    $this->assertDatabaseMissing('contract_acceptances', [
        'contract_id' => $anotherContract->id,
        'user_id' => $this->abk->id,
    ]);
});

it('mengembalikan FAILED jika contract_id tidak ditemukan', function () {
    Sanctum::actingAs($this->abk, ['*']);

    $acceptanceId = (string) \Illuminate\Support\Str::uuid();
    $invalidContractId = (string) \Illuminate\Support\Str::uuid();

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [
            [
                'id' => $acceptanceId,
                'contract_id' => $invalidContractId,
                'accepted_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ],
        ],
    ]);

    // Response should be 200 with sync_results containing FAILED
    $response->assertStatus(200)
        ->assertJsonPath('data.sync_results.0.status', 'FAILED');

    // Verify no record was created
    $this->assertDatabaseMissing('contract_acceptances', [
        'id' => $acceptanceId,
    ]);
});

it('menolak jika tidak ada token autentikasi (401)', function () {
    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'contract_id' => $this->contract->id,
                'accepted_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ],
        ],
    ]);

    $response->assertStatus(401);
});

it('menolak jika sync_timestamp invalid (422)', function () {
    Sanctum::actingAs($this->abk, ['*']);

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => 'not-a-valid-timestamp',
        'contract_acceptances' => [
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'contract_id' => $this->contract->id,
                'accepted_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.sync_timestamp.0', 'Waktu sinkronisasi harus tanggal/waktu yang valid (ISO-8601).');
});

it('menolak jika contract_acceptances array kosong (422)', function () {
    Sanctum::actingAs($this->abk, ['*']);

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
        'contract_acceptances' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.contract_acceptances.0', 'Setidaknya satu penerimaan kontrak harus dikirim.');
});

it('memproses multiple acceptances dengan hasil campuran', function () {
    Sanctum::actingAs($this->abk, ['*']);

    // Create second contract that ABK CAN accept
    $contract2 = ContractAbk::factory()->create(['period_id' => $this->period->id]);

    // Create third contract in another coop (for FORBIDDEN test)
    $anotherCoop = \App\Models\Coop::factory()->create();
    $anotherFloor = CoopFloor::factory()->create(['coop_id' => $anotherCoop->id]);
    $anotherPeriod = ProductionPeriod::factory()->create(['floor_id' => $anotherFloor->id]);
    $contract3 = ContractAbk::factory()->create(['period_id' => $anotherPeriod->id]);

    $acceptanceId1 = (string) \Illuminate\Support\Str::uuid();
    $acceptanceId2 = (string) \Illuminate\Support\Str::uuid();
    $acceptanceId3 = (string) \Illuminate\Support\Str::uuid();
    $acceptanceId4 = (string) \Illuminate\Support\Str::uuid();

    $response = $this->postJson('/api/v1/sync/periods', [
        'sync_timestamp' => now()->toIso8601String(),
        'contract_acceptances' => [
            [
                'id' => $acceptanceId1,
                'contract_id' => $this->contract->id,
                'accepted_at' => now()->toIso8601String(),
            ],
            [
                'id' => $acceptanceId2,
                'contract_id' => $contract2->id,
                'accepted_at' => now()->toIso8601String(),
            ],
            [
                'id' => $acceptanceId3,
                'contract_id' => $contract3->id,  // Not allowed (FORBIDDEN)
                'accepted_at' => now()->toIso8601String(),
            ],
            [
                'id' => $acceptanceId4,
                'contract_id' => (string) \Illuminate\Support\Str::uuid(),  // Not found (FAILED)
                'accepted_at' => now()->toIso8601String(),
            ],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.sync_results.0.status', 'SUCCESS')
        ->assertJsonPath('data.sync_results.1.status', 'SUCCESS')
        ->assertJsonPath('data.sync_results.2.status', 'FORBIDDEN')
        ->assertJsonPath('data.sync_results.3.status', 'FAILED');

    // Verify two records were created (SUCCESS cases)
    expect(ContractAcceptance::where('user_id', $this->abk->id)->count())->toBe(2);
});

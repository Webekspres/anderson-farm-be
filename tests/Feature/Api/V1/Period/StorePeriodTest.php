<?php

use App\Models\Coop;
use App\Models\User;
use App\Models\ProductionPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('POST /api/v1/periods', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('successfully creates a new production period (happy path)', function () {
        $coop = Coop::factory()->create();
        $pic = User::factory()->create();
        $payload = [
            'coop_id' => $coop->id,
            'pic_id' => $pic->id,
            'start_date' => now()->toDateString(),
            'initial_stock' => 1000,
            'created_at_client' => now()->toIso8601String(),
        ];
        $response = postJson('/api/v1/periods', $payload);
        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Siklus produksi berhasil dibuat.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'period_code',
                    'status',
                    'coop',
                    'pic',
                    'start_date',
                    'initial_stock',
                    'sync_status',
                    'created_at_client',
                    'created_at_server',
                    'updated_at_client',
                    'updated_at_server',
                    'deleted_at'
                ]
            ]);
        $this->assertDatabaseHas('production_periods', [
            'coop_id' => $coop->id,
            'pic_id' => $pic->id,
            'initial_stock' => 1000,
        ]);
    });

    it('successfully creates a period without period_code (auto-generated)', function () {
        $coop = Coop::factory()->create();
        $pic = User::factory()->create();
        $payload = [
            'coop_id' => $coop->id,
            'pic_id' => $pic->id,
            'start_date' => now()->toDateString(),
            'initial_stock' => 500,
            'created_at_client' => now()->toIso8601String(),
        ];
        $response = postJson('/api/v1/periods', $payload);
        $response->assertCreated();
        $this->assertNotNull($response->json('data.period_code'));
        $this->assertStringStartsWith('PRD-', $response->json('data.period_code'));
    });

    it('fails with 422 if coop already has active period', function () {
        $coop = Coop::factory()->create();
        $pic = User::factory()->create();
        ProductionPeriod::factory()->create([
            'coop_id' => $coop->id,
            'status' => 'active',
        ]);
        $payload = [
            'coop_id' => $coop->id,
            'pic_id' => $pic->id,
            'start_date' => now()->toDateString(),
            'initial_stock' => 100,
            'created_at_client' => now()->toIso8601String(),
        ];
        $response = postJson('/api/v1/periods', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('coop_id');
    });

    it('fails with 422 if initial_stock < 1', function () {
        $coop = Coop::factory()->create();
        $pic = User::factory()->create();
        $payload = [
            'coop_id' => $coop->id,
            'pic_id' => $pic->id,
            'start_date' => now()->toDateString(),
            'initial_stock' => 0,
            'created_at_client' => now()->toIso8601String(),
        ];
        $response = postJson('/api/v1/periods', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('initial_stock');
    });
});
it('returns 401 if not authenticated', function () {
    // Jangan panggil Sanctum::actingAs()
    $coop = Coop::factory()->create();
    $pic = User::factory()->create();
    $payload = [
        'coop_id' => $coop->id,
        'pic_id' => $pic->id,
        'start_date' => now()->toDateString(),
        'initial_stock' => 100,
        'created_at_client' => now()->toIso8601String(),
    ];
    $response = postJson('/api/v1/periods', $payload);
    $response->assertUnauthorized();
});

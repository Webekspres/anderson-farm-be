<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('POST /api/v1/periods', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create(), ['*']);
    });

    it('successfully creates a new production period (happy path)', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $pic = User::factory()->create();
        $payload = [
            'floor_id' => $floor->id,
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
                    'floor',
                    'pic',
                    'start_date',
                    'initial_stock',
                    'sync_status',
                    'created_at_client',
                    'created_at_server',
                    'updated_at_client',
                    'updated_at_server',
                    'deleted_at',
                ],
            ]);
        assertDatabaseHas('production_periods', [
            'floor_id' => $floor->id,
            'pic_id' => $pic->id,
            'initial_stock' => 1000,
            'status' => 'draft',
        ]);
        expect($response->json('data.status'))->toBe('draft');
    });

    it('successfully creates a period without period_code (auto-generated)', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $pic = User::factory()->create();
        $payload = [
            'floor_id' => $floor->id,
            'pic_id' => $pic->id,
            'start_date' => now()->toDateString(),
            'initial_stock' => 500,
            'created_at_client' => now()->toIso8601String(),
        ];
        $response = postJson('/api/v1/periods', $payload);
        $response->assertCreated();
        expect($response->json('data.period_code'))
            ->not->toBeNull()
            ->toStartWith('PRD-');
    });

    it('auto-generates unique period_code when creating again on the same floor after close', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $pic = User::factory()->create();

        $first = postJson('/api/v1/periods', [
            'floor_id' => $floor->id,
            'pic_id' => $pic->id,
            'start_date' => now()->toDateString(),
            'initial_stock' => 500,
            'created_at_client' => now()->toIso8601String(),
        ]);
        $first->assertCreated();
        $firstCode = $first->json('data.period_code');
        $periodId = $first->json('data.id');

        ProductionPeriod::query()->whereKey($periodId)->update([
            'status' => 'closed',
            'closed_at' => now(),
            'end_date' => now()->toDateString(),
        ]);

        $second = postJson('/api/v1/periods', [
            'floor_id' => $floor->id,
            'pic_id' => $pic->id,
            'start_date' => now()->toDateString(),
            'initial_stock' => 600,
            'created_at_client' => now()->toIso8601String(),
        ]);
        $second->assertCreated();
        $secondCode = $second->json('data.period_code');

        expect($secondCode)->not->toBe($firstCode)
            ->and($secondCode)->toStartWith('PRD-');
    });

    it('fails with 422 if coop already has active period', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $pic = User::factory()->create();
        ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'status' => 'active',
        ]);
        $payload = [
            'floor_id' => $floor->id,
            'pic_id' => $pic->id,
            'start_date' => now()->toDateString(),
            'initial_stock' => 100,
            'created_at_client' => now()->toIso8601String(),
        ];
        $response = postJson('/api/v1/periods', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('floor_id');
    });

    it('fails with 422 if initial_stock < 1', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $pic = User::factory()->create();
        $payload = [
            'floor_id' => $floor->id,
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
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
    $pic = User::factory()->create();
    $payload = [
        'floor_id' => $floor->id,
        'pic_id' => $pic->id,
        'start_date' => now()->toDateString(),
        'initial_stock' => 100,
        'created_at_client' => now()->toIso8601String(),
    ];
    $response = postJson('/api/v1/periods', $payload);
    $response->assertUnauthorized();
});

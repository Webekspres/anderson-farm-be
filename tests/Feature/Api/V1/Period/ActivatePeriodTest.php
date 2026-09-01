<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createDraftPeriodWithAssignment(User $user): ProductionPeriod
{
    $coop = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    CoopUserAssignment::factory()->create([
        'user_id' => $user->id,
        'coop_id' => $coop->id,
    ]);

    return ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'pic_id' => $user->id,
        'status' => 'draft',
    ]);
}

function activatePayload(array $overrides = []): array
{
    return array_merge([
        'sync_timestamp' => now()->toIso8601String(),
    ], $overrides);
}

describe('POST /api/v1/periods/{id}/activate', function () {
    it('activates a draft period for pic', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = createDraftPeriodWithAssignment($pic);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/activate", activatePayload());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('production_periods', [
            'id' => $period->id,
            'status' => 'active',
        ]);
    });

    it('rejects double activate with 400', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = createDraftPeriodWithAssignment($pic);
        $period->update(['status' => 'active']);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/activate", activatePayload());

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    });

    it('rejects activate when another open period exists on the same floor', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = createDraftPeriodWithAssignment($pic);

        // Overlap rule is per-floor (same as StorePeriodRequest), not per-coop.
        ProductionPeriod::factory()->create([
            'floor_id' => $period->floor_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/activate", activatePayload());

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    });

    it('allows activate when another active period exists on a different floor of the same coop', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = createDraftPeriodWithAssignment($pic);
        $otherFloor = CoopFloor::factory()->create(['coop_id' => $period->floor->coop_id]);

        ProductionPeriod::factory()->create([
            'floor_id' => $otherFloor->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($pic)
            ->postJson("/api/v1/periods/{$period->id}/activate", activatePayload());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');
    });

    it('forbids abk from activating a period', function () {
        $abk = User::factory()->create(['role' => 'abk']);
        $period = createDraftPeriodWithAssignment($abk);

        $response = $this->actingAs($abk)
            ->postJson("/api/v1/periods/{$period->id}/activate", activatePayload());

        $response->assertForbidden();
        $this->assertDatabaseHas('production_periods', [
            'id' => $period->id,
            'status' => 'draft',
        ]);
    });
});

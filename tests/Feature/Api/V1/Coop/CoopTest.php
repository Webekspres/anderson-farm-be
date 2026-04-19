<?php

use App\Models\Coop;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use Laravel\Sanctum\Sanctum;

describe('Coop API', function () {


    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('can list coops', function () {
        Coop::factory()->count(3)->create();
        $response = $this->getJson('/api/v1/coops');
        $response->assertOk()->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'uuid',
                    'farm_id',
                    'name',
                    'capacity',
                    'floor',
                    'coop_type',
                    'note',
                    'is_active',
                    'sync_status',
                    'created_at_client',
                    'created_at_server',
                    'updated_at_client',
                    'updated_at_server',
                    'deleted_at'
                ]
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total']
        ]);
    });

    it('can create a coop', function () {
        $farm = Farm::factory()->create();
        $payload = [
            'farm_id' => $farm->id,
            'name' => 'Kandang A',
            'capacity' => 2000,
            'floor' => 2,
            'coop_type' => 'open_house',
            'note' => 'Catatan',
            'is_active' => true,
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now()->toIso8601String(),
            'updated_at_client' => now()->toIso8601String(),
        ];
        $response = $this->postJson('/api/v1/coops', $payload);
        $response->assertCreated()->assertJsonPath('data.name', 'Kandang A');
        $this->assertDatabaseHas('coops', ['name' => 'Kandang A']);
    });

    it('can show a coop', function () {
        $coop = Coop::factory()->create();
        $response = $this->getJson('/api/v1/coops/' . $coop->id);
        $response->assertOk()->assertJsonPath('data.uuid', $coop->id);
    });

    it('can update a coop', function () {
        $coop = Coop::factory()->create();
        $response = $this->patchJson('/api/v1/coops/' . $coop->id, [
            'name' => 'Kandang B',
            'capacity' => 3000
        ]);
        $response->assertOk()->assertJsonPath('data.name', 'Kandang B');
        $this->assertDatabaseHas('coops', ['id' => $coop->id, 'name' => 'Kandang B', 'capacity' => 3000]);
    });

    it('can delete a coop', function () {
        $coop = Coop::factory()->create();
        $response = $this->deleteJson('/api/v1/coops/' . $coop->id);
        $response->assertOk()->assertJson(['message' => 'Deleted successfully']);
        $this->assertSoftDeleted('coops', ['id' => $coop->id]);
    });
});

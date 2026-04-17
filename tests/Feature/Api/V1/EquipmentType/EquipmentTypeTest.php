<?php

use App\Models\EquipmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('EquipmentType API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('can list equipment types with page and cursor pagination', function () {
        EquipmentType::factory()->count(20)->create();
        // Page pagination
        $response = $this->getJson('/api/v1/equipment-types?per_page=5');
        $response->assertOk()->assertJsonStructure([
            'data' => [[
                'id',
                'uuid',
                'name',
                'description',
                'sync_status',
                'created_at_client',
                'created_at_server',
                'updated_at_client',
                'updated_at_server',
                'deleted_at'
            ]],
            'meta' => ['current_page', 'last_page', 'per_page', 'total']
        ]);
        // Cursor pagination
        $response = $this->getJson('/api/v1/equipment-types?per_page=5&cursor=1');
        $response->assertOk()->assertJsonStructure([
            'data' => [[
                'id',
                'uuid',
                'name',
                'description',
                'sync_status',
                'created_at_client',
                'created_at_server',
                'updated_at_client',
                'updated_at_server',
                'deleted_at'
            ]],
            'meta' => ['per_page', 'next_cursor', 'prev_cursor']
        ]);
    });

    it('can filter equipment types by search', function () {
        EquipmentType::factory()->create(['name' => 'Traktor']);
        EquipmentType::factory()->create(['name' => 'Pemanas']);
        $response = $this->getJson('/api/v1/equipment-types?search=trak');
        $response->assertOk()->assertJsonFragment(['name' => 'Traktor']);
        $response->assertJsonMissing(['name' => 'Pemanas']);
    });

    it('can create a new equipment type', function () {
        $payload = [
            'name' => 'Alat Baru',
            'description' => 'Deskripsi alat baru',
            'created_at_client' => now()->toISOString(),
            'updated_at_client' => now()->toISOString(),
        ];
        $response = $this->postJson('/api/v1/equipment-types', $payload);
        $response->assertCreated()->assertJsonPath('data.name', 'Alat Baru');
        $this->assertDatabaseHas('equipment_types', ['name' => 'Alat Baru']);
    });

    it('cannot create equipment type with duplicate name', function () {
        EquipmentType::factory()->create(['name' => 'Unik']);
        $payload = [
            'name' => 'Unik',
            'description' => 'Deskripsi',
            'created_at_client' => now()->toISOString(),
            'updated_at_client' => now()->toISOString(),
        ];
        $response = $this->postJson('/api/v1/equipment-types', $payload);
        $response->assertStatus(422)->assertJsonValidationErrors('name');
    });

    it('can update equipment type name', function () {
        $equipmentType = EquipmentType::factory()->create(['name' => 'Lama']);
        $response = $this->patchJson('/api/v1/equipment-types/' . $equipmentType->id, [
            'name' => 'Baru',
        ]);
        $response->assertOk()->assertJsonPath('data.name', 'Baru');
        $this->assertDatabaseHas('equipment_types', ['id' => $equipmentType->id, 'name' => 'Baru']);
    });

    it('cannot update equipment type to duplicate name', function () {
        EquipmentType::factory()->create(['name' => 'Satu']);
        $dua = EquipmentType::factory()->create(['name' => 'Dua']);
        $response = $this->patchJson('/api/v1/equipment-types/' . $dua->id, [
            'name' => 'Satu',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors('name');
    });

    it('can soft delete equipment type', function () {
        $equipmentType = EquipmentType::factory()->create();
        $response = $this->deleteJson('/api/v1/equipment-types/' . $equipmentType->id);
        $response->assertOk()->assertJson(['message' => 'Deleted successfully', 'data' => null]);
        $this->assertSoftDeleted('equipment_types', ['id' => $equipmentType->id]);
    });


    describe('EquipmentType API unauthenticated', function () {
        // it('returns 401 if not authenticated', function () {
        //     config(['auth.defaults.guard' => 'sanctum']);
        //     $response = $this->withHeaders([
        //         'Authorization' => 'Bearer invalidtoken',
        //         'Accept' => 'application/json',
        //     ])->getJson('/api/v1/equipment-types');
        //     $response->assertStatus(401);
        // });

        it('returns 401 on GET if not authenticated', function () {
            $this->refreshApplication();
            $this->getJson("/api/v1/equipment-types/")->assertUnauthorized();
        });

        it('returns 401 on POST if not authenticated', function () {
            $this->refreshApplication();
            $this->postJson("/api/v1/equipment-types/", [])->assertUnauthorized();
        });
    });
});

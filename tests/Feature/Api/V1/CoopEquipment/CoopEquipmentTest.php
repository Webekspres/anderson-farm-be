<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopEquipment;
use App\Models\CoopFormAssignment;
use App\Models\EquipmentType;
use App\Models\FormConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('CoopEquipment API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('can list equipments for a coop with equipment_type relation', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $equipmentType = EquipmentType::factory()->create(['name' => 'Sensor']);
        CoopEquipment::factory()->count(2)->create(['floor_id' => $floor->id, 'equipment_type_id' => $equipmentType->id]);

        $response = $this->getJson("/api/v1/coops/{$coop->id}/equipments");
        $response->assertOk()->assertJsonStructure([
            'data' => [[
                'id',
                'uuid',
                'floor_id',
                'equipment_type',
                'unit_code',
                'installed_at',
                'sync_status',
                'created_at_client'
            ]],
            'success',
            'message'
        ]);
    });

    it('can create a new equipment', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $equipmentType = EquipmentType::factory()->create();

        $payload = ['floor_id' => $floor->id, 'equipment_type_id' => $equipmentType->id, 'unit_code' => 'UNIT-1', 'installed_at' => now()->toISOString()];
        $response = $this->postJson("/api/v1/coops/{$coop->id}/equipments", $payload);

        $response->assertCreated()->assertJsonPath('data.unit_code', 'UNIT-1');
        $this->assertDatabaseHas('coop_equipments', ['unit_code' => 'UNIT-1', 'floor_id' => $floor->id]);
    });

    it('fails with duplicate unit_code in same coop', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $equipmentType = EquipmentType::factory()->create();
        CoopEquipment::factory()->create(['floor_id' => $floor->id, 'equipment_type_id' => $equipmentType->id, 'unit_code' => 'DUP-1']);

        $payload = ['floor_id' => $floor->id, 'equipment_type_id' => $equipmentType->id, 'unit_code' => 'DUP-1'];
        $response = $this->postJson("/api/v1/coops/{$coop->id}/equipments", $payload);

        $response->assertStatus(422)->assertJsonValidationErrors('unit_code');
    });

    it('allows same unit_code in different coop', function () {
        $coop1 = Coop::factory()->create();
        $coop2 = Coop::factory()->create();
        $floor1 = CoopFloor::factory()->create(['coop_id' => $coop1->id]);
        $floor2 = CoopFloor::factory()->create(['coop_id' => $coop2->id]);
        $equipmentType = EquipmentType::factory()->create();

        CoopEquipment::factory()->create(['floor_id' => $floor1->id, 'equipment_type_id' => $equipmentType->id, 'unit_code' => 'SAME-1']);
        $payload = ['floor_id' => $floor2->id, 'equipment_type_id' => $equipmentType->id, 'unit_code' => 'SAME-1'];
        $response = $this->postJson("/api/v1/coops/{$coop2->id}/equipments", $payload);

        $response->assertCreated()->assertJsonPath('data.unit_code', 'SAME-1');
    });

    it('delete cascades coop_form_assignments', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $equipmentType = EquipmentType::factory()->create();
        $equipment = CoopEquipment::factory()->create(['floor_id' => $floor->id, 'equipment_type_id' => $equipmentType->id]);
        $formConfig = FormConfig::factory()->create();
        CoopFormAssignment::factory()->create(['coop_equipment_id' => $equipment->id, 'form_config_id' => $formConfig->id]);

        $this->assertDatabaseHas('coop_form_assignments', ['coop_equipment_id' => $equipment->id]);

        $response = $this->deleteJson("/api/v1/coops/{$coop->id}/equipments/{$equipment->id}");
        $response->assertOk();

        $this->assertSoftDeleted('coop_equipments', ['id' => $equipment->id]);
        $this->assertSoftDeleted('coop_form_assignments', ['coop_equipment_id' => $equipment->id]);
    });

    it('returns 404 when deleting equipment of another coop', function () {
        $coop1 = Coop::factory()->create();
        $coop2 = Coop::factory()->create();
        $floor1 = CoopFloor::factory()->create(['coop_id' => $coop1->id]);
        $floor2 = CoopFloor::factory()->create(['coop_id' => $coop2->id]);
        $equipmentType = EquipmentType::factory()->create();
        $equipment = CoopEquipment::factory()->create(['floor_id' => $floor1->id, 'equipment_type_id' => $equipmentType->id]);

        $response = $this->deleteJson("/api/v1/coops/{$coop2->id}/equipments/{$equipment->id}");
        $response->assertStatus(404);
        $this->assertDatabaseHas('coop_equipments', ['id' => $equipment->id, 'deleted_at' => null]);
    });
});

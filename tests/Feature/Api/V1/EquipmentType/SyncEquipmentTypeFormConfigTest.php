<?php

use App\Models\EquipmentType;
use App\Models\FormConfig;
use App\Models\EquipmentTypeFormConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('SyncEquipmentTypeFormConfig API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('successfully syncs form assignments (happy path)', function () {
        $equipmentType = EquipmentType::factory()->create();
        $formA = FormConfig::factory()->create();
        $formB = FormConfig::factory()->create();
        $payload = [
            'form_assignments' => [
                ['form_config_id' => $formA->id, 'display_order' => 1],
                ['form_config_id' => $formB->id, 'display_order' => 2],
            ]
        ];
        $response = $this->postJson("/api/v1/equipment-types/{$equipmentType->id}/form-configs", $payload);
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('equipment_type_form_configs', [
            'equipment_type_id' => $equipmentType->id,
            'form_config_id' => $formA->id,
        ]);
        $this->assertDatabaseHas('equipment_type_form_configs', [
            'equipment_type_id' => $equipmentType->id,
            'form_config_id' => $formB->id,
        ]);
    });

    it('successfully clears all assignments (empty array)', function () {
        $equipmentType = EquipmentType::factory()->create();
        $formA = FormConfig::factory()->create();
        EquipmentTypeFormConfig::factory()->create([
            'equipment_type_id' => $equipmentType->id,
            'form_config_id' => $formA->id,
        ]);
        $payload = ['form_assignments' => []];
        $response = $this->postJson("/api/v1/equipment-types/{$equipmentType->id}/form-configs", $payload);
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('equipment_type_form_configs', [
            'equipment_type_id' => $equipmentType->id,
        ]);
    });

    it('returns 404 if equipment type not found', function () {
        $payload = ['form_assignments' => []];
        $response = $this->postJson("/api/v1/equipment-types/invalid-id/form-configs", $payload);
        $response->assertStatus(404);
    });

    it('returns 422 if form_config_id does not exist', function () {
        $equipmentType = EquipmentType::factory()->create();
        $payload = [
            'form_assignments' => [
                ['form_config_id' => 'not-exist', 'display_order' => 1],
            ]
        ];
        $response = $this->postJson("/api/v1/equipment-types/{$equipmentType->id}/form-configs", $payload);
        $response->assertStatus(422)->assertJsonValidationErrors('form_assignments.0.form_config_id');
    });
});

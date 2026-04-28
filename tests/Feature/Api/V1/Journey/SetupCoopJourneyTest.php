<?php

use App\Models\User;
use App\Models\Farm;
use App\Models\CoopFloor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('successfully completes the end-to-end coop setup journey', function () {
    // --- SETUP USER & FARM (Factory allowed only here) ---
    $user = User::factory()->create();
    $farm = Farm::factory()->create();
    Sanctum::actingAs($user);

    // --- FASE 1: MASTER DATA ---

    // STEP 1: Create Equipment Type
    $equipmentTypePayload = [
        'server_id' => 10001,
        'name' => 'Kipas Blower',
        'description' => 'Kipas untuk sirkulasi udara',
        'created_at_client' => now()->toISOString(),
        'updated_at_client' => now()->toISOString(),
    ];
    $response = $this->postJson('/api/v1/equipment-types', $equipmentTypePayload);
    $response->assertStatus(201)->assertJson(['success' => true]);
    $equipmentTypeId = $response->json('data.uuid');

    // STEP 2: Create Form Config
    $formConfigPayload = [
        'category' => 'EQUIPMENT',
        'key_name' => 'suhu_udara',
        'config_value' => json_encode(['label' => 'Suhu Udara', 'type' => 'number']),
        'created_at_client' => now()->toISOString(),
        'updated_at_client' => now()->toISOString(),
    ];
    $response = $this->postJson('/api/v1/form-configs', $formConfigPayload);
    $response->assertStatus(201)->assertJson(['success' => true]);
    $formConfigId = $response->json('data.id');

    // STEP 3: Sync EquipmentType <-> FormConfig
    $syncFormConfigPayload = [
        'form_assignments' => [
            [
                'form_config_id' => $formConfigId,
                'display_order' => 1,
                'is_active' => true,
            ],
        ],
    ];
    $response = $this->postJson("/api/v1/equipment-types/{$equipmentTypeId}/form-configs", $syncFormConfigPayload);
    $response->assertStatus(200)->assertJson(['success' => true]);

    // --- FASE 2: SETUP KANDANG ---

    // STEP 4: Create Coop
    $coopPayload = [
        'server_id' => 20001,
        'farm_id' => $farm->id,
        'name' => 'Kandang Alpha',
        'capacity' => 1000,
        'coop_type' => 'CH_POSTAL',
        'is_active' => true,
        'note' => 'Kandang utama',
        'created_at_client' => now()->toISOString(),
        'updated_at_client' => now()->toISOString(),
    ];
    $response = $this->postJson('/api/v1/coops', $coopPayload);
    $response->assertStatus(201)->assertJson(['success' => true]);
    $coopId = $response->json('data.uuid');

    $floor = CoopFloor::factory()->create([
        'coop_id' => $coopId,
    ]);

    // STEP 5: Install Equipment in Coop
    $coopEquipmentPayload = [
        'floor_id' => $floor->id,
        'equipment_type_id' => $equipmentTypeId,
        'unit_code' => 'KIPAS-001',
        'installed_at' => now()->toISOString(),
        'created_at_client' => now()->toISOString(),
        'updated_at_client' => now()->toISOString(),
    ];
    $response = $this->postJson("/api/v1/coops/{$coopId}/equipments", $coopEquipmentPayload);
    $response->assertStatus(201)->assertJson(['success' => true]);
    $coopEquipmentId = $response->json('data.uuid');

    // STEP 6: Assign Form to Equipment in Coop
    $formAssignmentPayload = [
        'assignments' => [
            [
                'coop_equipment_id' => $coopEquipmentId,
                'form_config_id' => $formConfigId,
                'display_order' => 1,
                'is_active' => true,
            ],
        ],
    ];
    $response = $this->postJson("/api/v1/coops/{$coopId}/form-assignments", $formAssignmentPayload);
    $response->assertStatus(200)->assertJson(['success' => true]);

    // STEP 7: Assign User to Coop
    $userAssignmentPayload = [
        'assignments' => [
            [
                'user_id' => $user->id,
                'role_in_coop' => 'manager',
                'assigned_at' => now()->toISOString(),
            ],
        ],
    ];
    $response = $this->postJson("/api/v1/coops/{$coopId}/user-assignments", $userAssignmentPayload);
    $response->assertStatus(200)->assertJson(['success' => true]);

    // --- FASE 3: ASSERTIONS ---

    $this->assertDatabaseHas('equipment_types', [
        'id' => $equipmentTypeId,
        'name' => 'Kipas Blower',
    ]);
    $this->assertDatabaseHas('form_configs', [
        'id' => $formConfigId,
        'key_name' => 'suhu_udara',
    ]);
    $this->assertDatabaseHas('equipment_type_form_configs', [
        'equipment_type_id' => $equipmentTypeId,
        'form_config_id' => $formConfigId,
    ]);
    $this->assertDatabaseHas('coops', [
        'id' => $coopId,
        'name' => 'Kandang Alpha',
    ]);
    $this->assertDatabaseHas('coop_equipments', [
        'id' => $coopEquipmentId,
        'floor_id' => $floor->id,
        'equipment_type_id' => $equipmentTypeId,
    ]);
    $this->assertDatabaseHas('coop_form_assignments', [
        'coop_equipment_id' => $coopEquipmentId,
        'form_config_id' => $formConfigId,
    ]);
    $this->assertDatabaseHas('coop_user_assignments', [
        'user_id' => $user->id,
        'coop_id' => $coopId,
    ]);
});

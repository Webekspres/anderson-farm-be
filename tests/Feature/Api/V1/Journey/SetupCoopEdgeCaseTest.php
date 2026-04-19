<?php

use App\Models\User;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\EquipmentType;
use App\Models\CoopEquipment;
use App\Models\FormConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// 1. Serangan Lintas Kandang (Cross-Coop ID Injection)
it('rejects cross-coop equipment id injection on form assignment', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create();
    $coopA = Coop::factory()->create(['farm_id' => $farm->id]);
    $coopB = Coop::factory()->create(['farm_id' => $farm->id]);
    $equipmentType = EquipmentType::factory()->create();
    $equipA = CoopEquipment::factory()->create([
        'coop_id' => $coopA->id,
        'equipment_type_id' => $equipmentType->id,
    ]);
    $formConfig = FormConfig::factory()->create();

    Sanctum::actingAs($user);

    $payload = [
        'assignments' => [
            [
                'coop_equipment_id' => $equipA->id,
                'form_config_id' => $formConfig->id,
                'display_order' => 1,
                'is_active' => true,
            ],
        ],
    ];
    $response = $this->postJson("/api/v1/coops/{$coopB->id}/form-assignments", $payload);
    $response->assertStatus(422);
});

// 2. Master Data Hilang di Tengah Jalan (Ghost Reference)
it('rejects equipment install if equipment type is soft deleted', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create();
    $coop = Coop::factory()->create(['farm_id' => $farm->id]);
    $equipmentType = EquipmentType::factory()->create();
    $equipmentType->delete(); // Soft delete

    Sanctum::actingAs($user);

    $payload = [
        'equipment_type_id' => $equipmentType->id,
        'unit_code' => 'SN-002',
        'installed_at' => now()->toISOString(),
        'created_at_client' => now()->toISOString(),
        'updated_at_client' => now()->toISOString(),
    ];
    $response = $this->postJson("/api/v1/coops/{$coop->id}/equipments", $payload);
    $response->assertStatus(422);
});

// 3. Duplikasi Nomor Seri (Double Unit Code)
it('rejects duplicate unit_code per coop', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create();
    $coop = Coop::factory()->create(['farm_id' => $farm->id]);
    $equipmentType = EquipmentType::factory()->create();

    Sanctum::actingAs($user);

    $payload = [
        'equipment_type_id' => $equipmentType->id,
        'unit_code' => 'SN-001',
        'installed_at' => now()->toISOString(),
        'created_at_client' => now()->toISOString(),
        'updated_at_client' => now()->toISOString(),
    ];
    $response1 = $this->postJson("/api/v1/coops/{$coop->id}/equipments", $payload);
    $response1->assertStatus(201);

    $response2 = $this->postJson("/api/v1/coops/{$coop->id}/equipments", $payload);
    $response2->assertStatus(422);
});

// 4. Pembatalan Setengah Jalan (Orphan Prevention / Cascade)
it('cascades delete coop_form_assignments when equipment is deleted', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->create();
    $coop = Coop::factory()->create(['farm_id' => $farm->id]);
    $equipmentType = EquipmentType::factory()->create();
    $equip = CoopEquipment::factory()->create([
        'coop_id' => $coop->id,
        'equipment_type_id' => $equipmentType->id,
    ]);
    $formConfig = FormConfig::factory()->create();

    Sanctum::actingAs($user);

    // Assign form to equipment
    $assignPayload = [
        'assignments' => [
            [
                'coop_equipment_id' => $equip->id,
                'form_config_id' => $formConfig->id,
                'display_order' => 1,
                'is_active' => true,
            ],
        ],
    ];
    $this->postJson("/api/v1/coops/{$coop->id}/form-assignments", $assignPayload)
        ->assertStatus(200);

    // Delete equipment
    $this->deleteJson("/api/v1/coops/{$coop->id}/equipments/{$equip->id}")
        ->assertSuccessful();

    // Assert assignment is deleted (cascade)
    $this->assertDatabaseMissing('coop_form_assignments', [
        'coop_equipment_id' => $equip->id,
        'form_config_id' => $formConfig->id,
        'deleted_at' => null,
    ]);
});

// 5. Setup Ulang Pekerja (Bulk Overwrite Logic)
it('overwrites coop user assignments in bulk sync', function () {
    $farm = Farm::factory()->create();
    $coop = Coop::factory()->create(['farm_id' => $farm->id]);
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $userC = User::factory()->create();

    Sanctum::actingAs($userA);

    // Assign User A & B
    $payload1 = [
        'assignments' => [
            [
                'user_id' => $userA->id,
                'role_in_coop' => 'worker',
                'assigned_at' => now()->toISOString(),
            ],
            [
                'user_id' => $userB->id,
                'role_in_coop' => 'worker',
                'assigned_at' => now()->toISOString(),
            ],
        ],
    ];
    $this->postJson("/api/v1/coops/{$coop->id}/user-assignments", $payload1)
        ->assertStatus(200);

    // Overwrite with only User C
    $payload2 = [
        'assignments' => [
            [
                'user_id' => $userC->id,
                'role_in_coop' => 'worker',
                'assigned_at' => now()->toISOString(),
            ],
        ],
    ];
    $this->postJson("/api/v1/coops/{$coop->id}/user-assignments", $payload2)
        ->assertStatus(200);

    // Assert only User C remains
    $this->assertDatabaseHas('coop_user_assignments', [
        'user_id' => $userC->id,
        'coop_id' => $coop->id,
        'deleted_at' => null,
    ]);
    $this->assertDatabaseMissing('coop_user_assignments', [
        'user_id' => $userA->id,
        'coop_id' => $coop->id,
        'deleted_at' => null,
    ]);
    $this->assertDatabaseMissing('coop_user_assignments', [
        'user_id' => $userB->id,
        'coop_id' => $coop->id,
        'deleted_at' => null,
    ]);
});

<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopEquipment;
use App\Models\FormConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('Authenticated User', function () {


    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    it('berhasil melakukan sync form untuk beberapa alat sekaligus di satu kandang', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $equip1 = CoopEquipment::factory()->create(['floor_id' => $floor->id]);
        $equip2 = CoopEquipment::factory()->create(['floor_id' => $floor->id]);
        $form1 = FormConfig::factory()->create();
        $form2 = FormConfig::factory()->create();

        $payload = [
            'assignments' => [
                [
                    'coop_equipment_id' => $equip1->id,
                    'form_config_id' => $form1->id,
                    'display_order' => 1,
                    'is_active' => true,
                ],
                [
                    'coop_equipment_id' => $equip2->id,
                    'form_config_id' => $form2->id,
                    'display_order' => 2,
                    'is_active' => true,
                ],
            ],
        ];

        $response = postJson(
            "/api/v1/coops/{$coop->id}/form-assignments",
            $payload
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sinkronisasi form assignment berhasil',
            ]);
    });

    it('berhasil mengosongkan form (array kosong)', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $equip = CoopEquipment::factory()->create(['floor_id' => $floor->id]);
        $form = FormConfig::factory()->create();
        // Seed awal assignment
        $equip->coopFormAssignments()->create([
            'form_config_id' => $form->id,
            'display_order' => 1,
            'is_active' => true,
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ]);
        $payload = ['assignments' => []];
        $response = postJson("/api/v1/coops/{$coop->id}/form-assignments", $payload);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sinkronisasi form assignment berhasil',
            ]);
    });

    it('gagal 422 jika ada coop_equipment_id bukan milik coop', function () {
        $coop = Coop::factory()->create();
        $otherCoop = Coop::factory()->create();
        $otherFloor = CoopFloor::factory()->create(['coop_id' => $otherCoop->id]);
        $equip = CoopEquipment::factory()->create(['floor_id' => $otherFloor->id]);
        $form = FormConfig::factory()->create();
        $payload = [
            'assignments' => [
                [
                    'coop_equipment_id' => $equip->id,
                    'form_config_id' => $form->id,
                    'display_order' => 1,
                    'is_active' => true,
                ],
            ],
        ];
        $response = postJson("/api/v1/coops/{$coop->id}/form-assignments", $payload);
        $response->assertStatus(422);
    });

    it('gagal 404 jika coop_id tidak ditemukan', function () {
        $payload = ['assignments' => []];
        $response = postJson("/api/v1/coops/invalid-id/form-assignments", $payload);
        $response->assertStatus(404);
    });
});


it('gagal 401 jika tanpa token', function () {
    // Test tanpa user login, jangan panggil Sanctum::actingAs
    $coop = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
    $equip = CoopEquipment::factory()->create(['floor_id' => $floor->id]);
    $form = FormConfig::factory()->create();
    $payload = [
        'assignments' => [
            [
                'coop_equipment_id' => $equip->id,
                'form_config_id' => $form->id,
                'display_order' => 1,
                'is_active' => true,
            ],
        ],
    ];
    $response = postJson("/api/v1/coops/{$coop->id}/form-assignments", $payload);
    $response->assertStatus(401);
});

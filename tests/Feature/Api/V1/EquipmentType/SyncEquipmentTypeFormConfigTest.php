<?php

use App\Models\EquipmentType;
use App\Models\FormConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;


use App\Models\EquipmentTypeFormConfig;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('Unauthenticated User', function () {
    it('gagal 401 jika tanpa token sanctum', function () {
        $this->withoutMiddleware('auth:sanctum');
        $equipmentType = EquipmentType::factory()->create();
        $form = FormConfig::factory()->create();
        $payload = [
            'form_assignments' => [
                [
                    'form_config_id' => $form->id,
                    'display_order' => 1,
                ]
            ],
        ];

        $response = postJson(
            "/api/v1/equipment-types/{$equipmentType->id}/form-configs",
            $payload
        );

        $response->assertStatus(401);
    });
});

describe('Authenticated User', function () {


    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    it('berhasil melakukan sync (2 form)', function () {
        $equipmentType = EquipmentType::factory()->create();
        $form1 = FormConfig::factory()->create();
        $form2 = FormConfig::factory()->create();

        $payload = [
            'form_assignments' => [
                [
                    'form_config_id' => $form1->id,
                    'display_order' => 1,
                ],
                [
                    'form_config_id' => $form2->id,
                    'display_order' => 2,
                ],
            ],
        ];

        $response = postJson(
            "/api/v1/equipment-types/{$equipmentType->id}/form-configs",
            $payload
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Master SOP alat berhasil diperbarui',
                'data' => null,
            ]);

        expect($equipmentType->formConfigs()->count())->toBe(2);
    });

    it('berhasil mengosongkan relasi jika array kosong', function () {
        $equipmentType = EquipmentType::factory()->create();
        $form = FormConfig::factory()->create();
        // Seed awal relasi dengan id UUID di pivot
        $equipmentType->formConfigs()->attach($form->id, [
            'id' => (string) Illuminate\Support\Str::uuid(),
            'display_order' => 1,
            'sync_status' => 'PENDING_SYNC',
            'created_at_client' => now(),
            'updated_at_client' => now(),
        ]);

        $payload = [
            'form_assignments' => [],
        ];

        $response = postJson(
            "/api/v1/equipment-types/{$equipmentType->id}/form-configs",
            $payload
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Master SOP alat berhasil diperbarui',
                'data' => null,
            ]);

        expect($equipmentType->formConfigs()->count())->toBe(0);
    });

    it('gagal 404 jika equipment_type_id tidak ditemukan', function () {
        // Kirim payload valid agar lolos validasi FormRequest
        $form = FormConfig::factory()->create();
        $payload = [
            'form_assignments' => [
                [
                    'form_config_id' => $form->id,
                    'display_order' => 1,
                ]
            ],
        ];

        $response = postJson(
            "/api/v1/equipment-types/invalid-id/form-configs",
            $payload
        );

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'data' => null,
            ]);
    });

    it('gagal 422 jika form_config_id tidak valid', function () {
        $equipmentType = EquipmentType::factory()->create();
        $payload = [
            'form_assignments' => [
                [
                    'form_config_id' => 'not-exist',
                    'display_order' => 1,
                ],
            ],
        ];

        $response = postJson(
            "/api/v1/equipment-types/{$equipmentType->id}/form-configs",
            $payload
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['form_assignments.0.form_config_id']);
    });


    describe('SyncEquipmentTypeFormConfig API', function () {
        // beforeEach(function () {
        //     $this->user = User::factory()->create();
        //     Sanctum::actingAs($this->user, ['*']);
        // });

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
});

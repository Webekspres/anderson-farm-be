<?php

declare(strict_types=1);

use App\Models\PeriodFormAssignment;
use App\Models\ProductionPeriod;
use App\Models\FormConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

const FORM_ASSIGNMENT_ENDPOINT = '/api/v1/periods/%s/form-assignments';

uses(RefreshDatabase::class);
describe('Authenticated', function () {
    beforeEach(function () {
        $this->user = \App\Models\User::factory()->create();
        actingAs($this->user, 'sanctum');
        $this->period = \App\Models\ProductionPeriod::factory()->create();
        $this->formConfigs = \App\Models\FormConfig::factory()->count(3)->create();
    });


    it('returns active form blueprint sorted by display_order with parsed ui_metadata', function () {
        $configHbe = FormConfig::factory()->create([
            'category' => 'HBE',
            'key_name' => 'hbe_panting',
            'config_value' => json_encode([
                'type' => 'scale',
                'min' => 1,
                'max' => 5,
                'label' => 'Tingkat Panting',
            ], JSON_THROW_ON_ERROR),
        ]);

        $configEquipment = FormConfig::factory()->create([
            'category' => 'EQUIPMENT',
            'key_name' => 'temp_sensor_1',
            'config_value' => json_encode([
                'type' => 'number',
                'label' => 'Suhu Kandang',
            ], JSON_THROW_ON_ERROR),
        ]);

        $second = PeriodFormAssignment::factory()->create([
            'period_id' => $this->period->id,
            'form_config_id' => $configEquipment->id,
            'display_order' => 2,
            'is_active' => true,
        ]);

        $first = PeriodFormAssignment::factory()->create([
            'period_id' => $this->period->id,
            'form_config_id' => $configHbe->id,
            'display_order' => 1,
            'is_active' => true,
        ]);

        PeriodFormAssignment::factory()->create([
            'period_id' => $this->period->id,
            'form_config_id' => FormConfig::factory()->create()->id,
            'display_order' => 3,
            'is_active' => false,
        ]);

        $response = getJson(sprintf(FORM_ASSIGNMENT_ENDPOINT, $this->period->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.form_config_id', $configHbe->id)
            ->assertJsonPath('data.0.category', 'HBE')
            ->assertJsonPath('data.0.key_name', 'hbe_panting')
            ->assertJsonPath('data.0.display_order', 1)
            ->assertJsonPath('data.0.ui_metadata.type', 'scale')
            ->assertJsonPath('data.0.ui_metadata.label', 'Tingkat Panting')
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('data.1.category', 'EQUIPMENT')
            ->assertJsonPath('data.1.key_name', 'temp_sensor_1')
            ->assertJsonPath('data.1.display_order', 2);

        expect($response->json('data.0.ui_metadata'))->toBeArray()
            ->and($response->json('data.0.ui_metadata'))->not->toBeString();
    });

    it('returns empty ui_metadata when config_value json is invalid', function () {
        $config = FormConfig::factory()->create([
            'key_name' => 'broken_json_field',
        ]);

        DB::table('form_configs')
            ->where('id', $config->id)
            ->update(['config_value' => '{not-valid-json']);

        $config->refresh();

        PeriodFormAssignment::factory()->create([
            'period_id' => $this->period->id,
            'form_config_id' => $config->id,
            'is_active' => true,
        ]);

        $response = getJson(sprintf(FORM_ASSIGNMENT_ENDPOINT, $this->period->id));

        $response->assertOk()
            ->assertJsonPath('data.0.ui_metadata', []);
    });

    it('returns 404 when period uuid does not exist', function () {
        $response = getJson(sprintf(FORM_ASSIGNMENT_ENDPOINT, (string) Str::uuid()));

        $response->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        Auth::guard('sanctum')->forgetUser();

        $response = getJson(sprintf(FORM_ASSIGNMENT_ENDPOINT, $this->period->id));

        $response->assertUnauthorized();
    });


    describe('POST /api/v1/periods/{period_id}/form-assignments', function () {
        it('syncs assignments and returns the new list', function () {
            // Seed initial assignments
            PeriodFormAssignment::factory()->count(2)->create([
                'period_id' => $this->period->id,
            ]);

            $payload = [
                'assignments' => [
                    [
                        'form_config_id' => $this->formConfigs[0]->id,
                        'display_order' => 1,
                        'is_active' => true,
                    ],
                    [
                        'form_config_id' => $this->formConfigs[1]->id,
                        'display_order' => 2,
                        'is_active' => false,
                    ],
                ]
            ];

            $response = postJson("/api/v1/periods/{$this->period->id}/form-assignments", $payload);
            $response->assertOk();
            $response->assertJsonFragment(['success' => true]);
            $response->assertJsonCount(2, 'data');
            $response->assertJsonPath('data.0.form_config.id', $this->formConfigs[0]->id);
            $response->assertJsonPath('data.1.form_config.id', $this->formConfigs[1]->id);
        });

        it('syncs empty assignments (clear all)', function () {
            PeriodFormAssignment::factory()->count(2)->create([
                'period_id' => $this->period->id,
            ]);
            $payload = ['assignments' => []];
            $response = postJson("/api/v1/periods/{$this->period->id}/form-assignments", $payload);
            $response->assertOk();
            $response->assertJsonCount(0, 'data');
            $this->assertDatabaseMissing('period_form_assignments', [
                'period_id' => $this->period->id,
                'deleted_at' => null,
            ]);
        });

        it('validates assignments payload', function () {
            $payload = [
                'assignments' => [
                    [
                        'form_config_id' => null,
                        'display_order' => 'not-an-int',
                        'is_active' => 'not-bool',
                    ],
                ]
            ];
            $response = postJson("/api/v1/periods/{$this->period->id}/form-assignments", $payload);
            $response->assertStatus(422);
            $response->assertJsonValidationErrors([
                'assignments.0.form_config_id',
                'assignments.0.display_order',
                'assignments.0.is_active',
            ]);
        });

        it('fails with 422 if form_config_id not found', function () {
            $payload = [
                'assignments' => [
                    [
                        'form_config_id' => '00000000-0000-0000-0000-000000000000',
                        'display_order' => 1,
                        'is_active' => true,
                    ],
                ]
            ];
            $response = postJson("/api/v1/periods/{$this->period->id}/form-assignments", $payload);
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['assignments.0.form_config_id']);
        });
    });
});

it('fails with 401 if no token (GET)', function () {
    $period = \App\Models\ProductionPeriod::factory()->create();
    $response = getJson("/api/v1/periods/{$period->id}/form-assignments");
    $response->assertStatus(401);
});

it('fails with 401 if no token (POST)', function () {
    $period = \App\Models\ProductionPeriod::factory()->create();
    $payload = ['assignments' => []];
    $response = postJson("/api/v1/periods/{$period->id}/form-assignments", $payload);
    $response->assertStatus(401);
});

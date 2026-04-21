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

uses(RefreshDatabase::class);
describe('Authenticated', function () {
    beforeEach(function () {
        $this->user = \App\Models\User::factory()->create();
        actingAs($this->user, 'sanctum');
        $this->period = \App\Models\ProductionPeriod::factory()->create();
        $this->formConfigs = \App\Models\FormConfig::factory()->count(3)->create();
    });

    describe('GET /api/v1/periods/{period_id}/form-assignments', function () {
        it('returns assignments for the period', function () {
            $assignments = [
                PeriodFormAssignment::factory()->create([
                    'period_id' => $this->period->id,
                    'form_config_id' => $this->formConfigs[0]->id,
                ]),
                PeriodFormAssignment::factory()->create([
                    'period_id' => $this->period->id,
                    'form_config_id' => $this->formConfigs[1]->id,
                ]),
            ];

            $response = getJson("/api/v1/periods/{$this->period->id}/form-assignments");
            $response->assertOk();
            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [['id', 'form_config_id', 'form_config']]
            ]);
            $response->assertJsonFragment([
                'period_id' => $this->period->id,
            ]);
        });
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

<?php

use App\Models\ChecklistTask;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

const CHECKLIST_TASK_ENDPOINT = '/api/v1/periods/%s/checklist-tasks';

describe('GET /api/v1/periods/{period_id}/checklist-tasks', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->period = ProductionPeriod::factory()->create();
        Sanctum::actingAs($this->user);
    });

    it('returns active checklist tasks with erd business fields', function () {
        $activeTask = ChecklistTask::factory()->create([
            'period_id' => $this->period->id,
            'task_name' => 'Pembakaran belerang 15kg',
            'task_type' => 'BOOLEAN',
            'description' => 'Bakar belerang merata per lantai sebelum DOC masuk.',
            'is_active' => true,
        ]);

        ChecklistTask::factory()->create([
            'period_id' => $this->period->id,
            'task_name' => 'Tugas nonaktif',
            'is_active' => false,
        ]);

        $response = $this->getJson(sprintf(CHECKLIST_TASK_ENDPOINT, $this->period->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    [
                        'id',
                        'period_id',
                        'task_name',
                        'task_type',
                        'description',
                        'is_active',
                    ],
                ],
            ])
            ->assertJsonPath('data.0.id', $activeTask->id)
            ->assertJsonPath('data.0.period_id', $this->period->id)
            ->assertJsonPath('data.0.task_name', 'Pembakaran belerang 15kg')
            ->assertJsonPath('data.0.task_type', 'BOOLEAN')
            ->assertJsonPath('data.0.description', 'Bakar belerang merata per lantai sebelum DOC masuk.')
            ->assertJsonPath('data.0.is_active', true);
    });

    it('returns 404 when period uuid does not exist', function () {
        $response = $this->getJson(sprintf(CHECKLIST_TASK_ENDPOINT, (string) Str::uuid()));

        $response->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        Auth::guard('sanctum')->forgetUser();

        $response = $this->getJson(sprintf(CHECKLIST_TASK_ENDPOINT, $this->period->id));

        $response->assertUnauthorized();
    });
});

describe('POST /api/v1/periods/{period_id}/checklist-tasks', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->period = ProductionPeriod::factory()->create();
        Sanctum::actingAs($this->user);
    });

    it('can bulk sync checklist tasks (replace old with new)', function () {
        ChecklistTask::factory()->count(2)->create([
            'period_id' => $this->period->id,
        ]);

        $payload = [
            'tasks' => [
                [
                    'task_name' => 'Tugas Baru 1',
                    'task_type' => 'TEXT',
                    'description' => 'Ini tugas baru',
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->postJson(sprintf(CHECKLIST_TASK_ENDPOINT, $this->period->id), $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('checklist_tasks', 1);
        $this->assertDatabaseHas('checklist_tasks', [
            'task_name' => 'Tugas Baru 1',
            'task_type' => 'TEXT',
        ]);
    });

    it('can clear all checklist tasks by sending empty array', function () {
        ChecklistTask::factory()->count(2)->create([
            'period_id' => $this->period->id,
        ]);

        $response = $this->postJson(sprintf(CHECKLIST_TASK_ENDPOINT, $this->period->id), [
            'tasks' => [],
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('checklist_tasks', 0);
    });

    it('fails validation when task_type is invalid', function () {
        $payload = [
            'tasks' => [
                [
                    'task_name' => 'Tugas Salah',
                    'task_type' => 'GAMBAR',
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->postJson(sprintf(CHECKLIST_TASK_ENDPOINT, $this->period->id), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tasks.0.task_type']);
    });

    it('returns 401 when unauthenticated', function () {
        Auth::guard('sanctum')->forgetUser();

        $response = $this->postJson(sprintf(CHECKLIST_TASK_ENDPOINT, $this->period->id), [
            'tasks' => [],
        ]);

        $response->assertUnauthorized();
    });
});

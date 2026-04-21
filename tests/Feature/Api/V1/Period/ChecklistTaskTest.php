<?php

use App\Models\ChecklistTask;
use App\Models\ProductionPeriod;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Authenticated', function () {
    beforeEach(function () {
        // Siapkan data dasar untuk testing
        $this->user = User::factory()->create();
        $this->period = ProductionPeriod::factory()->create();

        // Otentikasi otomatis sebelum tiap test
        Sanctum::actingAs($this->user);
    });

    it('can fetch checklist tasks for a period', function () {
        // Siapkan 3 data dummy
        ChecklistTask::factory()->count(3)->create([
            'period_id' => $this->period->id,
        ]);

        $response = $this->getJson("/api/v1/periods/{$this->period->id}/checklist-tasks");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'task_name', 'task_type', 'is_active']
                ]
            ]);

        expect($response->json('data'))->toHaveCount(3);
    });

    it('can bulk sync checklist tasks (replace old with new)', function () {
        // Buat 2 tugas lama
        ChecklistTask::factory()->count(2)->create([
            'period_id' => $this->period->id,
        ]);

        // Siapkan payload 1 tugas baru
        $payload = [
            'tasks' => [
                [
                    'task_name' => 'Tugas Baru 1',
                    'task_type' => 'TEXT',
                    'description' => 'Ini tugas baru',
                    'is_active' => true,
                ]
            ]
        ];

        $response = $this->postJson("/api/v1/periods/{$this->period->id}/checklist-tasks", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Pastikan di database sisa 1 tugas (tugas lama terhapus)
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

        $payload = [
            'tasks' => [] // Kirim array kosong
        ];

        $response = $this->postJson("/api/v1/periods/{$this->period->id}/checklist-tasks", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseCount('checklist_tasks', 0); // Pastikan tabel kosong untuk periode tsb
    });

    it('fails validation when task_type is invalid', function () {
        $payload = [
            'tasks' => [
                [
                    'task_name' => 'Tugas Salah',
                    'task_type' => 'GAMBAR', // Tipe tidak diizinkan
                    'is_active' => true,
                ]
            ]
        ];

        $response = $this->postJson("/api/v1/periods/{$this->period->id}/checklist-tasks", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.task_type']);
    });
    it('returns 401 when unauthenticated', function () {
        // Hapus otentikasi Sanctum
        Auth::guard('sanctum')->forgetUser();


        $response = $this->getJson("/api/v1/periods/{$this->period->id}/checklist-tasks");

        $response->assertStatus(401);
    });
});

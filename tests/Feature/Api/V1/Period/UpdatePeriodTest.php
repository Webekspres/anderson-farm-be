<?php

use App\Models\CoopFloor;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PATCH /api/v1/periods/{period_id}', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('berhasil memperbarui stok awal melalui PATCH', function () {
        $floor = CoopFloor::factory()->create(['capacity' => 20000]);
        $period = ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'initial_stock' => 10000,
        ]);

        $payload = [
            'initial_stock' => 15000,
            'updated_at_client' => now()->toIso8601String(),
        ];

        $response = $this->patchJson("/api/v1/periods/{$period->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.initial_stock', 15000);

        $this->assertDatabaseHas('production_periods', [
            'id' => $period->id,
            'initial_stock' => 15000,
        ]);
    });

    it('gagal update jika initial_stock melebihi kapasitas lantai', function () {
        $floor = CoopFloor::factory()->create(['capacity' => 1000]);
        $period = ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'initial_stock' => 800,
        ]);

        $response = $this->patchJson("/api/v1/periods/{$period->id}", [
            'initial_stock' => 1001,
            'updated_at_client' => now()->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['initial_stock']);
    });

    it('gagal update jika period_code sudah digunakan oleh periode lain', function () {
        $periodA = ProductionPeriod::factory()->create(['period_code' => 'KODE-A']);
        $periodB = ProductionPeriod::factory()->create(['period_code' => 'KODE-B']);

        $payload = [
            'period_code' => 'KODE-A', // Mencoba pakai kode milik A
            'updated_at_client' => now(),
        ];

        $response = $this->patchJson("/api/v1/periods/{$periodB->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period_code']);
    });
});

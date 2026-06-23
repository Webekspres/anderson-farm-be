<?php

use App\Models\Coop;
use App\Models\User;
use App\Models\ProductionPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('POST /api/v1/periods', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('berhasil memperbarui stok awal melalui PATCH', function () {
        Sanctum::actingAs($this->user);
        $period = ProductionPeriod::factory()->create(['initial_stock' => 10000]);

        $payload = [
            'initial_stock' => 15000,
            'updated_at_client' => now()->toIso8601String()
        ];

        $response = $this->patchJson("/api/v1/periods/{$period->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.initial_stock', 15000);

        $this->assertDatabaseHas('production_periods', [
            'id' => $period->id,
            'initial_stock' => 15000
        ]);
    });

    it('gagal update jika period_code sudah digunakan oleh periode lain', function () {
        Sanctum::actingAs($this->user);

        $periodA = ProductionPeriod::factory()->create(['period_code' => 'KODE-A']);
        $periodB = ProductionPeriod::factory()->create(['period_code' => 'KODE-B']);

        $payload = [
            'period_code' => 'KODE-A', // Mencoba pakai kode milik A
            'updated_at_client' => now()
        ];

        $response = $this->patchJson("/api/v1/periods/{$periodB->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period_code']);
    });
});

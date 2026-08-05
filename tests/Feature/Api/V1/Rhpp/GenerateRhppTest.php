<?php

use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('POST /api/v1/periods/{id}/rhpp/generate', function () {
    beforeEach(function () {
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->period = ProductionPeriod::factory()->create([
            'status' => 'completed',
            'initial_stock' => 1000,
        ]);
    });

    it('creates draft RHPP totals from operational calculation', function () {
        Sanctum::actingAs($this->manager);

        $response = $this->postJson("/api/v1/periods/{$this->period->id}/rhpp/generate");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.publish_status', 'DRAFT');

        expect($response->json('data.total_income'))->toBeNumeric();
        expect($response->json('data.total_expense'))->toBeNumeric();
        expect($response->json('data.net_profit'))->toBeNumeric();

        $this->assertDatabaseHas('rhpps', [
            'period_id' => $this->period->id,
            'publish_status' => 'DRAFT',
        ]);
    });

    it('rejects generate when period is still active', function () {
        Sanctum::actingAs($this->manager);
        $active = ProductionPeriod::factory()->create(['status' => 'active']);

        $this->postJson("/api/v1/periods/{$active->id}/rhpp/generate")
            ->assertStatus(400);
    });

    it('rejects generate when RHPP already published', function () {
        Sanctum::actingAs($this->manager);

        Rhpp::factory()->create([
            'period_id' => $this->period->id,
            'publish_status' => 'PUBLISHED',
            'total_income' => 1,
            'total_expense' => 1,
            'net_profit' => 0,
        ]);

        $this->postJson("/api/v1/periods/{$this->period->id}/rhpp/generate")
            ->assertStatus(400);
    });
});

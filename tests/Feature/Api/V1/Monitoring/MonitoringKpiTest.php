<?php

use App\Models\DailyActivityHeader;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Monitoring KPI & deviations', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['role' => 'manager']);
        $this->period = ProductionPeriod::factory()->create([
            'status' => 'active',
            'initial_stock' => 1000,
            'start_date' => now()->subDays(20)->toDateString(),
        ]);
    });

    it('returns KPI payload with daily_summary for a period', function () {
        Sanctum::actingAs($this->user);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'date' => now()->subDays(2)->toDateString(),
            'mortality_count' => 2,
            'cull_count' => 1,
            'feed_consumption_kg' => 120,
            'average_weight' => 1500,
        ]);

        $response = $this->getJson('/api/v1/monitoring/kpi?period_id='.$this->period->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'deplesi',
                    'avg_bw',
                    'fcr',
                    'ip',
                    'age_days',
                    'total_harvest',
                    'daily_summary',
                ],
            ]);

        expect($response->json('data.daily_summary'))->toBeArray()->not->toBeEmpty();
        expect($response->json('data.initial_stock'))->toBe(1000);
    });

    it('returns deviations array for a period', function () {
        Sanctum::actingAs($this->user);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'date' => now()->subDay()->toDateString(),
            'mortality_count' => 80,
            'cull_count' => 20,
            'feed_consumption_kg' => 500,
            'average_weight' => 1200,
        ]);

        $response = $this->getJson('/api/v1/monitoring/deviations?period_id='.$this->period->id);

        $response->assertOk()
            ->assertJsonPath('success', true);

        expect($response->json('data'))->toBeArray();
        if (count($response->json('data')) > 0) {
            expect($response->json('data.0.acknowledged'))->toBeFalse();
        }
    });

    it('acknowledges a deviation and returns acknowledged flag on refetch', function () {
        Sanctum::actingAs($this->user);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'date' => now()->subDay()->toDateString(),
            'mortality_count' => 80,
            'cull_count' => 20,
            'feed_consumption_kg' => 500,
            'average_weight' => 1200,
        ]);

        $deviations = $this->getJson('/api/v1/monitoring/deviations?period_id='.$this->period->id)
            ->json('data');

        expect($deviations)->not->toBeEmpty();
        $metric = $deviations[0]['metric'];

        $ackResponse = $this->postJson('/api/v1/monitoring/deviations/acknowledge', [
            'period_id' => $this->period->id,
            'metric' => $metric,
            'date' => $deviations[0]['date'] ?? null,
        ]);

        $ackResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metric', $metric);

        $refetch = $this->getJson('/api/v1/monitoring/deviations?period_id='.$this->period->id);
        $matched = collect($refetch->json('data'))->firstWhere('metric', $metric);
        expect($matched['acknowledged'] ?? false)->toBeTrue();
    });
});

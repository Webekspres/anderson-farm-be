<?php

use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('Daily activity sync contract gate', function () {
    it('rejects pic sync when period contract is not accepted', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = ProductionPeriod::factory()->create([
            'status' => 'active',
            'start_date' => now()->subMonths(6)->format('Y-m-d'),
        ]);
        $period->loadMissing('floor');
        CoopUserAssignment::factory()->create([
            'user_id' => $pic->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        ContractAbk::factory()->create([
            'period_id' => $period->id,
            'uploaded_by' => $pic->id,
        ]);

        $activityDay = now()->subDays(2)->format('Y-m-d');
        $clientTimestamp = $activityDay.'T06:00:00Z';
        $headerId = Str::uuid()->toString();

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/daily-activities', [
            'headers' => [
                [
                    'id' => $headerId,
                    'period_id' => $period->id,
                    'user_id' => $pic->id,
                    'date' => $activityDay,
                    'age_days' => 15,
                    'mortality_count' => 0,
                    'cull_count' => 0,
                    'feed_consumption_kg' => 10,
                    'average_weight' => 1.1,
                    'business_status' => 'DRAFT',
                    'created_at_client' => $clientTimestamp,
                    'updated_at_client' => $clientTimestamp,
                    'dynamic_logs' => [],
                    'harvests' => [],
                    'ovk_usages' => [],
                    'photos' => [],
                    'checklist_logs' => [],
                ],
            ],
        ]);

        $response->assertOk();
        expect($response->json('data.sync_results.0.status'))->toBe('CONTRACT_PENDING');
        $this->assertDatabaseMissing('daily_activity_headers', ['id' => $headerId]);
    });

    it('allows pic sync after accepting the period contract', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = ProductionPeriod::factory()->create([
            'status' => 'active',
            'start_date' => now()->subMonths(6)->format('Y-m-d'),
        ]);
        $period->loadMissing('floor');
        CoopUserAssignment::factory()->create([
            'user_id' => $pic->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        $contract = ContractAbk::factory()->create([
            'period_id' => $period->id,
            'uploaded_by' => $pic->id,
        ]);

        ContractAcceptance::factory()->create([
            'contract_id' => $contract->id,
            'user_id' => $pic->id,
            'accepted_at' => now(),
        ]);

        $activityDay = now()->subDays(2)->format('Y-m-d');
        $clientTimestamp = $activityDay.'T06:00:00Z';
        $headerId = Str::uuid()->toString();

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/daily-activities', [
            'headers' => [
                [
                    'id' => $headerId,
                    'period_id' => $period->id,
                    'user_id' => $pic->id,
                    'date' => $activityDay,
                    'age_days' => 15,
                    'mortality_count' => 0,
                    'cull_count' => 0,
                    'feed_consumption_kg' => 10,
                    'average_weight' => 1.1,
                    'business_status' => 'DRAFT',
                    'created_at_client' => $clientTimestamp,
                    'updated_at_client' => $clientTimestamp,
                    'dynamic_logs' => [],
                    'harvests' => [],
                    'ovk_usages' => [],
                    'photos' => [],
                    'checklist_logs' => [],
                ],
            ],
        ]);

        $response->assertOk();
        expect($response->json('data.sync_results.0.status'))->toBe('SYNCED');
        $this->assertDatabaseHas('daily_activity_headers', ['id' => $headerId]);
    });

    it('allows manager sync even when contract is pending', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        $period = ProductionPeriod::factory()->create([
            'status' => 'active',
            'start_date' => now()->subMonths(6)->format('Y-m-d'),
        ]);
        $period->loadMissing('floor');
        CoopUserAssignment::factory()->create([
            'user_id' => $manager->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        ContractAbk::factory()->create([
            'period_id' => $period->id,
            'uploaded_by' => $manager->id,
        ]);

        $activityDay = now()->subDays(2)->format('Y-m-d');
        $clientTimestamp = $activityDay.'T06:00:00Z';
        $headerId = Str::uuid()->toString();

        $response = $this->actingAs($manager)->postJson('/api/v1/sync/daily-activities', [
            'headers' => [
                [
                    'id' => $headerId,
                    'period_id' => $period->id,
                    'user_id' => $manager->id,
                    'date' => $activityDay,
                    'age_days' => 15,
                    'mortality_count' => 0,
                    'cull_count' => 0,
                    'feed_consumption_kg' => 10,
                    'average_weight' => 1.1,
                    'business_status' => 'DRAFT',
                    'created_at_client' => $clientTimestamp,
                    'updated_at_client' => $clientTimestamp,
                    'dynamic_logs' => [],
                    'harvests' => [],
                    'ovk_usages' => [],
                    'photos' => [],
                    'checklist_logs' => [],
                ],
            ],
        ]);

        $response->assertOk();
        expect($response->json('data.sync_results.0.status'))->toBe('SYNCED');
    });
});

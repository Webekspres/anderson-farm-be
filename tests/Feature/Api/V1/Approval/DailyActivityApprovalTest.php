<?php

use App\Models\Area;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\Farm;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createApprovalScenario(): array
{
    $manager = User::factory()->create(['role' => 'manager']);
    $abk = User::factory()->create(['role' => 'abk']);

    $area = Area::factory()->create(['manager_id' => $manager->id]);
    $farm = Farm::factory()->create(['area_id' => $area->id]);
    $coop = Coop::factory()->create(['farm_id' => $farm->id]);
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
    $period = ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'status' => 'active',
        'start_date' => now()->subMonth()->format('Y-m-d'),
    ]);

    CoopUserAssignment::factory()->create([
        'coop_id' => $coop->id,
        'user_id' => $abk->id,
    ]);

    $header = DailyActivityHeader::factory()->submitted()->create([
        'period_id' => $period->id,
        'user_id' => $abk->id,
        'date' => now()->subDay()->format('Y-m-d H:i:s'),
    ]);

    return compact('manager', 'abk', 'area', 'farm', 'coop', 'floor', 'period', 'header');
}

describe('GET /api/v1/approvals/daily-activities', function () {
    it('returns submitted headers for area manager', function () {
        ['manager' => $manager, 'header' => $header] = createApprovalScenario();

        $response = $this->actingAs($manager)->getJson('/api/v1/approvals/daily-activities');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $header->id)
            ->assertJsonPath('data.items.0.business_status', 'SUBMITTED');
    });

    it('returns 403 for abk role', function () {
        ['abk' => $abk] = createApprovalScenario();

        $response = $this->actingAs($abk)->getJson('/api/v1/approvals/daily-activities');

        $response->assertForbidden()
            ->assertJsonPath('success', false);
    });

    it('returns submitted headers for finance role', function () {
        ['header' => $header] = createApprovalScenario();
        $finance = User::factory()->create(['role' => 'finance']);

        $response = $this->actingAs($finance)->getJson('/api/v1/approvals/daily-activities');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $header->id);
    });

    it('filters by approved business_status', function () {
        ['manager' => $manager, 'header' => $header] = createApprovalScenario();

        $this->actingAs($manager)->postJson("/api/v1/approvals/daily-activities/{$header->id}", [
            'action' => 'approve',
        ])->assertOk();

        $waiting = $this->actingAs($manager)->getJson('/api/v1/approvals/daily-activities');
        $waiting->assertOk()->assertJsonCount(0, 'data.items');

        $approved = $this->actingAs($manager)->getJson('/api/v1/approvals/daily-activities?'.http_build_query([
            'business_status' => 'APPROVED',
        ]));

        $approved->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $header->id)
            ->assertJsonPath('data.items.0.business_status', 'APPROVED');
    });

    it('returns empty list for manager outside scope', function () {
        createApprovalScenario();
        $outsiderManager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($outsiderManager)->getJson('/api/v1/approvals/daily-activities');

        $response->assertOk()
            ->assertJsonCount(0, 'data.items');
    });
});

describe('GET /api/v1/approvals/daily-activities/{id}', function () {
    it('returns detail for authorized manager', function () {
        ['manager' => $manager, 'header' => $header] = createApprovalScenario();

        $response = $this->actingAs($manager)->getJson("/api/v1/approvals/daily-activities/{$header->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $header->id)
            ->assertJsonPath('data.business_status', 'SUBMITTED');
    });

    it('returns 403 for manager outside scope', function () {
        ['header' => $header] = createApprovalScenario();
        $outsiderManager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($outsiderManager)->getJson("/api/v1/approvals/daily-activities/{$header->id}");

        $response->assertForbidden();
    });
});

describe('POST /api/v1/approvals/daily-activities/{id}', function () {
    it('approves submitted header and creates notification', function () {
        ['manager' => $manager, 'abk' => $abk, 'header' => $header] = createApprovalScenario();

        $response = $this->actingAs($manager)->postJson("/api/v1/approvals/daily-activities/{$header->id}", [
            'action' => 'approve',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.business_status', 'APPROVED')
            ->assertJsonPath('data.approved_by', $manager->id);

        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $header->id,
            'business_status' => 'APPROVED',
            'approved_by' => $manager->id,
            'rejection_reason' => null,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $abk->id,
            'reference_id' => $header->id,
            'reference_type' => 'DAILY_ACTIVITY',
        ]);
    });

    it('rejects submitted header with reason', function () {
        ['manager' => $manager, 'header' => $header] = createApprovalScenario();

        $response = $this->actingAs($manager)->postJson("/api/v1/approvals/daily-activities/{$header->id}", [
            'action' => 'reject',
            'rejection_reason' => 'Mortality count tidak sesuai foto.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.business_status', 'REJECTED')
            ->assertJsonPath('data.rejection_reason', 'Mortality count tidak sesuai foto.');

        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $header->id,
            'business_status' => 'REJECTED',
        ]);
    });

    it('returns 422 when reject without rejection_reason', function () {
        ['manager' => $manager, 'header' => $header] = createApprovalScenario();

        $response = $this->actingAs($manager)->postJson("/api/v1/approvals/daily-activities/{$header->id}", [
            'action' => 'reject',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    });

    it('returns 422 when approving already approved header', function () {
        ['manager' => $manager, 'period' => $period, 'abk' => $abk] = createApprovalScenario();

        $approvedHeader = DailyActivityHeader::factory()->approved()->create([
            'period_id' => $period->id,
            'user_id' => $abk->id,
            'approved_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->postJson("/api/v1/approvals/daily-activities/{$approvedHeader->id}", [
            'action' => 'approve',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    });

    it('returns 403 for abk role', function () {
        ['abk' => $abk, 'header' => $header] = createApprovalScenario();

        $response = $this->actingAs($abk)->postJson("/api/v1/approvals/daily-activities/{$header->id}", [
            'action' => 'approve',
        ]);

        $response->assertForbidden();
    });

    it('allows finance to approve submitted header', function () {
        ['abk' => $abk, 'header' => $header] = createApprovalScenario();
        $finance = User::factory()->create(['role' => 'finance']);

        $response = $this->actingAs($finance)->postJson("/api/v1/approvals/daily-activities/{$header->id}", [
            'action' => 'approve',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.business_status', 'APPROVED')
            ->assertJsonPath('data.approved_by', $finance->id);

        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $header->id,
            'business_status' => 'APPROVED',
            'approved_by' => $finance->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $abk->id,
            'reference_id' => $header->id,
            'reference_type' => 'DAILY_ACTIVITY',
        ]);
    });

    it('returns 401 for guest', function () {
        ['header' => $header] = createApprovalScenario();

        $response = $this->postJson("/api/v1/approvals/daily-activities/{$header->id}", [
            'action' => 'approve',
        ]);

        $response->assertUnauthorized();
    });
});

describe('Approval integration with sync', function () {
    it('abk receives APPROVED status via GET sync after manager approval', function () {
        ['manager' => $manager, 'abk' => $abk, 'period' => $period, 'coop' => $coop] = createApprovalScenario();

        $headerId = Str::uuid()->toString();
        $activityDay = now()->subDays(2)->format('Y-m-d');

        $this->actingAs($abk)->postJson('/api/v1/sync/daily-activities', [
            'headers' => [[
                'id' => $headerId,
                'period_id' => $period->id,
                'user_id' => $abk->id,
                'date' => $activityDay,
                'age_days' => 10,
                'mortality_count' => 2,
                'cull_count' => 0,
                'feed_consumption_kg' => 80,
                'average_weight' => 1.1,
                'business_status' => 'SUBMITTED',
                'created_at_client' => now()->subDays(2)->toIso8601String(),
                'updated_at_client' => now()->subDays(2)->toIso8601String(),
                'dynamic_logs' => [],
                'harvests' => [],
                'ovk_usages' => [],
                'photos' => [],
                'checklist_logs' => [],
            ]],
        ])->assertOk();

        $this->actingAs($manager)->postJson("/api/v1/approvals/daily-activities/{$headerId}", [
            'action' => 'approve',
        ])->assertOk();

        $pullResponse = $this->actingAs($abk)->getJson('/api/v1/sync/daily-activities?'.http_build_query([
            'period_id' => $period->id,
        ]));

        $pullResponse->assertOk();

        $items = collect($pullResponse->json('data'));
        $approvedItem = $items->firstWhere('id', $headerId);

        expect($approvedItem)->not->toBeNull()
            ->and($approvedItem['business_status'])->toBe('APPROVED')
            ->and($approvedItem['approved_by'])->toBe($manager->id)
            ->and($approvedItem['feed_consumption_kg'])->toEqual(80);
    });
});

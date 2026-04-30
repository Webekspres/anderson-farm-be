<?php

use App\Models\Area;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\Farm;
use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\RhppDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function createHierarchyForManager(User $manager): ProductionPeriod
{
    $area  = Area::factory()->create(['manager_id' => $manager->id]);
    $farm  = Farm::factory()->create(['area_id' => $area->id]);
    $coop  = Coop::factory()->create(['farm_id' => $farm->id]);
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    return ProductionPeriod::factory()->create(['floor_id' => $floor->id]);
}

function createHierarchyForAbk(User $abk): ProductionPeriod
{
    $coop  = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    CoopUserAssignment::factory()->create([
        'user_id' => $abk->id,
        'coop_id' => $coop->id,
    ]);

    return ProductionPeriod::factory()->create(['floor_id' => $floor->id]);
}

describe('GET /api/v1/sync/rhpps', function () {

    it('Test 1 — Investor mendapat 403 Forbidden', function () {
        $investor = User::factory()->create(['role' => 'investor']);

        $response = $this->actingAs($investor)->getJson('/api/v1/sync/rhpps');

        $response->assertForbidden();
    });

    it('Test 2 — Hanya RHPP PUBLISHED yang dikembalikan (DRAFT diabaikan)', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $period1 = ProductionPeriod::factory()->create();
        $period2 = ProductionPeriod::factory()->create();

        Rhpp::factory()->create([
            'period_id' => $period1->id,
            'publish_status' => 'DRAFT',
        ]);

        $publishedRhpp = Rhpp::factory()->create([
            'period_id' => $period2->id,
            'publish_status' => 'PUBLISHED',
        ]);

        RhppDocument::factory()->create(['Rhpp_id' => $publishedRhpp->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/sync/rhpps');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rhpps');
        $response->assertJsonPath('data.rhpps.0.id', $publishedRhpp->id);
        $response->assertJsonCount(1, 'data.rhpps.0.documents');
    });

    it('Test 3 — ABK hanya melihat RHPP dari Coop yang ditugaskan', function () {
        $abk = User::factory()->create(['role' => 'abk']);
        
        // Assigned Coop
        $assignedPeriod = createHierarchyForAbk($abk);
        $rhppAssigned = Rhpp::factory()->create([
            'period_id' => $assignedPeriod->id,
            'publish_status' => 'PUBLISHED',
        ]);

        // Unassigned Coop
        $unassignedPeriod = ProductionPeriod::factory()->create();
        Rhpp::factory()->create([
            'period_id' => $unassignedPeriod->id,
            'publish_status' => 'PUBLISHED',
        ]);

        $response = $this->actingAs($abk)->getJson('/api/v1/sync/rhpps');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rhpps');
        $response->assertJsonPath('data.rhpps.0.id', $rhppAssigned->id);
    });

    it('Test 4 — Manager hanya melihat RHPP dari Area yang ditugaskan', function () {
        $manager = User::factory()->create(['role' => 'manager']);
        
        // Assigned Area
        $assignedPeriod = createHierarchyForManager($manager);
        $rhppAssigned = Rhpp::factory()->create([
            'period_id' => $assignedPeriod->id,
            'publish_status' => 'PUBLISHED',
        ]);

        // Unassigned Area
        $otherManager = User::factory()->create(['role' => 'manager']);
        $unassignedPeriod = createHierarchyForManager($otherManager);
        Rhpp::factory()->create([
            'period_id' => $unassignedPeriod->id,
            'publish_status' => 'PUBLISHED',
        ]);

        $response = $this->actingAs($manager)->getJson('/api/v1/sync/rhpps');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rhpps');
        $response->assertJsonPath('data.rhpps.0.id', $rhppAssigned->id);
    });

});

<?php

use App\Models\Area;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EducationArticle;
use App\Models\EquipmentType;
use App\Models\Farm;
use App\Models\FormConfig;
use App\Models\OvkItem;
use App\Models\PriceReference;
use App\Models\ProductionPeriod;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function seedGlobalMasterCatalogs(): void
{
    FormConfig::factory()->count(2)->create();
    EquipmentType::factory()->count(2)->create();
    OvkItem::factory()->count(2)->create();
    EducationArticle::factory()->count(2)->create();
    PriceReference::factory()->count(2)->create();
    ReportTemplate::factory()->count(2)->create();
}

function seedAssignedHierarchy(User $user): array
{
    $area = Area::factory()->create(['name' => 'Area Test']);
    $farm = Farm::factory()->create(['area_id' => $area->id, 'name' => 'Farm Test']);
    $coop = Coop::factory()->create(['farm_id' => $farm->id, 'name' => 'Coop Test']);

    CoopUserAssignment::factory()->create([
        'user_id' => $user->id,
        'coop_id' => $coop->id,
    ]);

    $floor = CoopFloor::factory()->create([
        'coop_id' => $coop->id,
    ]);

    $period = ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
    ]);

    return compact('area', 'farm', 'coop', 'floor', 'period');
}

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'abk', 'is_active' => true]);
    Sanctum::actingAs($this->user, ['*']);

    $hierarchy = seedAssignedHierarchy($this->user);
    $this->area = $hierarchy['area'];
    $this->farm = $hierarchy['farm'];
    $this->coop = $hierarchy['coop'];
    $this->floor = $hierarchy['floor'];
    $this->period = $hierarchy['period'];

    seedGlobalMasterCatalogs();
});

it('can fetch all master data for the assigned user (Happy Path)', function () {
    $response = $this->getJson('/api/v1/sync/master-data');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'current_server_timestamp',
            'data' => [
                'coop_user_assignments',
                'coops',
                'farms',
                'areas',
                'production_periods',
                'form_configs',
                'equipment_types',
                'ovk_items',
                'education_articles',
                'price_references',
                'report_templates',
            ],
        ]);

    $response->assertJsonCount(1, 'data.areas');
    $response->assertJsonCount(1, 'data.farms');
    $response->assertJsonCount(1, 'data.coops');
    $response->assertJsonCount(1, 'data.coop_user_assignments');
    $response->assertJsonCount(1, 'data.production_periods');

    $response->assertJsonCount(2, 'data.form_configs');
    $response->assertJsonCount(2, 'data.equipment_types');
    $response->assertJsonCount(2, 'data.ovk_items');
});

it('returns empty hierarchy for abk without coop assignment', function () {
    $unassigned = User::factory()->create(['role' => 'abk', 'is_active' => true]);
    Sanctum::actingAs($unassigned, ['*']);

    $response = $this->getJson('/api/v1/sync/master-data');

    $response->assertOk();
    $response->assertJsonCount(0, 'data.farms');
    $response->assertJsonCount(0, 'data.coops');
    $response->assertJsonCount(0, 'data.areas');
    $response->assertJsonCount(0, 'data.production_periods');
    $response->assertJsonCount(0, 'data.coop_user_assignments');
    $response->assertJsonCount(2, 'data.form_configs');
});

it('returns full hierarchy for admin without coop assignment', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    Sanctum::actingAs($admin, ['*']);

    // Extra unassigned farm to prove admin sees everything
    $extraArea = Area::factory()->create();
    Farm::factory()->create(['area_id' => $extraArea->id, 'name' => 'Unassigned Farm']);

    $response = $this->getJson('/api/v1/sync/master-data');

    $response->assertOk();
    expect(count($response->json('data.farms')))->toBeGreaterThanOrEqual(2);
    expect(count($response->json('data.coops')))->toBeGreaterThanOrEqual(1);
    expect(count($response->json('data.areas')))->toBeGreaterThanOrEqual(2);
});

it('returns full hierarchy for manager and finance roles', function (string $role) {
    $user = User::factory()->create(['role' => $role, 'is_active' => true]);
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/sync/master-data');

    $response->assertOk();
    $response->assertJsonCount(1, 'data.farms');
    $response->assertJsonCount(1, 'data.coops');
})->with(['manager', 'finance']);

it('keeps full hierarchy on delta sync while filtering global catalogs', function () {
    $lastSync = now()->addMinute()->toIso8601String();

    $this->travel(2)->minutes();

    $newFarm = Farm::factory()->create([
        'area_id' => $this->area->id,
        'name' => 'New Farm',
        'updated_at_server' => now(),
    ]);
    $newCoop = Coop::factory()->create([
        'farm_id' => $newFarm->id,
        'name' => 'New Coop',
        'updated_at_server' => now(),
    ]);
    CoopUserAssignment::factory()->create([
        'user_id' => $this->user->id,
        'coop_id' => $newCoop->id,
        'updated_at_server' => now(),
    ]);

    FormConfig::factory()->create([
        'updated_at_server' => now(),
    ]);

    $response = $this->getJson('/api/v1/sync/master-data?last_sync_timestamp='.urlencode($lastSync));

    $response->assertOk();

    // Hierarchy is always full for the user's scope (not delta-filtered).
    $response->assertJsonCount(1, 'data.areas');
    $response->assertJsonCount(2, 'data.farms');
    $response->assertJsonCount(2, 'data.coops');
    $response->assertJsonCount(2, 'data.coop_user_assignments');

    // Global catalogs remain delta-filtered.
    $response->assertJsonCount(1, 'data.form_configs');
    $response->assertJsonCount(0, 'data.equipment_types');
    $response->assertJsonCount(0, 'data.ovk_items');
});

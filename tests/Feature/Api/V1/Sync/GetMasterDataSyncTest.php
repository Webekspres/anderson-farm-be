<?php

use App\Models\Area;
use App\Models\Coop;
use App\Models\CoopUserAssignment;
use App\Models\EducationArticle;
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

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'abk', 'is_active' => true]);
    Sanctum::actingAs($this->user, ['*']);
    
    // Setup Hierarchical Data
    $this->area = Area::factory()->create(['name' => 'Area Test']);
    $this->farm = Farm::factory()->create(['area_id' => $this->area->id, 'name' => 'Farm Test']);
    $this->coop = Coop::factory()->create(['farm_id' => $this->farm->id, 'name' => 'Coop Test']);
    
    $this->assignment = CoopUserAssignment::factory()->create([
        'user_id' => $this->user->id,
        'coop_id' => $this->coop->id,
    ]);
    
    $this->period = ProductionPeriod::factory()->create([
        'coop_id' => $this->coop->id,
    ]);

    // Setup Global Data
    FormConfig::factory()->count(2)->create();
    OvkItem::factory()->count(2)->create();
    EducationArticle::factory()->count(2)->create();
    PriceReference::factory()->count(2)->create();
    ReportTemplate::factory()->count(2)->create();
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
                'ovk_items',
                'education_articles',
                'price_references',
                'report_templates',
            ],
        ]);

    // Verify hierarchical data count (only those assigned)
    $response->assertJsonCount(1, 'data.areas');
    $response->assertJsonCount(1, 'data.farms');
    $response->assertJsonCount(1, 'data.coops');
    $response->assertJsonCount(1, 'data.coop_user_assignments');
    $response->assertJsonCount(1, 'data.production_periods');
    
    // Verify global data count
    $response->assertJsonCount(2, 'data.form_configs');
    $response->assertJsonCount(2, 'data.ovk_items');
});

it('can fetch only new master data using last_sync_timestamp (Delta Sync)', function () {
    // Simulated Time Travel: Old records were created before this timestamp
    $lastSync = now()->addMinute()->toIso8601String();
    
    // Sleep a bit or artificially manipulate time to ensure 'updated_at_server' is later
    $this->travel(2)->minutes();

    // Create a new Farm & Coop, and assign the user
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
    
    // Create new global data
    FormConfig::factory()->create([
        'updated_at_server' => now(),
    ]);

    // Make the request with delta sync
    $response = $this->getJson('/api/v1/sync/master-data?last_sync_timestamp=' . urlencode($lastSync));

    $response->assertOk();

    // Assert that we only get the NEW items in the delta payload
    $response->assertJsonCount(0, 'data.areas'); // Area hasn't changed
    $response->assertJsonCount(1, 'data.farms'); // Only 1 new farm
    $response->assertJsonCount(1, 'data.coops'); // Only 1 new coop
    $response->assertJsonCount(1, 'data.coop_user_assignments'); // Only 1 new assignment
    
    $response->assertJsonCount(1, 'data.form_configs'); // Only 1 new form config
    $response->assertJsonCount(0, 'data.ovk_items'); // No new ovk items
});

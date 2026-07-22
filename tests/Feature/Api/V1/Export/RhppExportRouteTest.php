<?php

declare(strict_types=1);

use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('registers export rhpp route for authenticated non-abk users', function (): void {
    $manager = User::factory()->create(['role' => 'manager']);
    $period = ProductionPeriod::factory()->create();

    Sanctum::actingAs($manager);

    $response = $this->get('/api/v1/export/rhpp?'.http_build_query([
        'period_id' => $period->id,
        'format' => 'excel',
    ]));

    // Streamed export or JSON error are both valid; route must not be missing.
    expect($response->baseResponse->getStatusCode())->not->toBe(404);
});

it('denies abk from export rhpp', function (): void {
    $abk = User::factory()->create(['role' => 'abk']);
    $period = ProductionPeriod::factory()->create();

    Sanctum::actingAs($abk);

    $this->getJson('/api/v1/export/rhpp?'.http_build_query([
        'period_id' => $period->id,
        'format' => 'pdf',
    ]))->assertForbidden();
});

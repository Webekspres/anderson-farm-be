<?php

use App\Models\DailyActivityHeader;
use App\Models\HarvestEntry;
use App\Models\ProductionPeriod;
use App\Services\RhppCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('aggregates feed consumption from daily activity headers and calculates FCR', function () {
    $period = ProductionPeriod::factory()->create([
        'initial_stock' => 1000,
    ]);

    $headerOne = DailyActivityHeader::factory()->create([
        'period_id' => $period->id,
        'feed_consumption_kg' => 100,
    ]);

    $headerTwo = DailyActivityHeader::factory()->create([
        'period_id' => $period->id,
        'feed_consumption_kg' => 150,
    ]);

    HarvestEntry::factory()->create([
        'header_id' => $headerOne->id,
        'total_birds' => 100,
        'total_weight' => 200,
        'price_per_kg' => 20000,
        'total_revenue' => 4000000,
    ]);

    HarvestEntry::factory()->create([
        'header_id' => $headerTwo->id,
        'total_birds' => 50,
        'total_weight' => 100,
        'price_per_kg' => 20000,
        'total_revenue' => 2000000,
    ]);

    $metrics = app(RhppCalculationService::class)->calculateMetrics($period->fresh());

    expect($metrics['feed_consumption'])->toBe(250.0)
        ->and($metrics['total_harvested_weight'])->toBe(300.0)
        ->and($metrics['fcr'])->toBe(round(250 / 300, 4));
});

it('defaults feed consumption to zero when headers have no feed recorded', function () {
    $period = ProductionPeriod::factory()->create();

    DailyActivityHeader::factory()->create([
        'period_id' => $period->id,
        'feed_consumption_kg' => 0,
    ]);

    $metrics = app(RhppCalculationService::class)->calculateMetrics($period->fresh());

    expect($metrics['feed_consumption'])->toBe(0.0)
        ->and($metrics['fcr'])->toBe(0.0);
});

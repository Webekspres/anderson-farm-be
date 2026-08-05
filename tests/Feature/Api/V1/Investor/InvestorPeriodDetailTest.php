<?php

declare(strict_types=1);

use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns investor period detail with published rhpp', function (): void {
    $investor = User::factory()->create(['role' => 'investor']);
    $period = ProductionPeriod::factory()->create([
        'period_code' => 'P-DET-01',
        'status' => 'active',
    ]);

    PeriodInvestor::factory()->create([
        'period_id' => $period->id,
        'user_id' => $investor->id,
        'profit_share_percentage' => 25,
        'initial_investment' => 10000000,
        'final_dividend_amount' => 12000000,
        'is_paid' => true,
    ]);

    Rhpp::factory()->create([
        'period_id' => $period->id,
        'publish_status' => 'PUBLISHED',
        'total_income' => 50000000,
        'total_expense' => 30000000,
        'net_profit' => 20000000,
    ]);

    Sanctum::actingAs($investor);

    $this->getJson("/api/v1/investor/periods/{$period->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.period_code', 'P-DET-01')
        ->assertJsonPath('data.roi_percent', 20)
        ->assertJsonPath('data.rhpp.publish_status', 'PUBLISHED')
        ->assertJsonPath('data.rhpp.net_profit', 20000000)
        ->assertJsonPath('data.rhpp.total_income', 50000000);
});

it('hides draft rhpp from investor period detail', function (): void {
    $investor = User::factory()->create(['role' => 'investor']);
    $period = ProductionPeriod::factory()->create(['period_code' => 'P-DET-02']);

    PeriodInvestor::factory()->create([
        'period_id' => $period->id,
        'user_id' => $investor->id,
        'initial_investment' => 5000000,
        'final_dividend_amount' => 0,
    ]);

    Rhpp::factory()->create([
        'period_id' => $period->id,
        'publish_status' => 'DRAFT',
        'net_profit' => 999,
    ]);

    Sanctum::actingAs($investor);

    $this->getJson("/api/v1/investor/periods/{$period->id}")
        ->assertOk()
        ->assertJsonPath('data.rhpp', null);
});

it('forbids investor from unrelated period', function (): void {
    $investor = User::factory()->create(['role' => 'investor']);
    $other = User::factory()->create(['role' => 'investor']);
    $period = ProductionPeriod::factory()->create();

    PeriodInvestor::factory()->create([
        'period_id' => $period->id,
        'user_id' => $other->id,
    ]);

    Sanctum::actingAs($investor);

    $this->getJson("/api/v1/investor/periods/{$period->id}")
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

it('forbids non-investor roles from period detail', function (): void {
    $pic = User::factory()->create(['role' => 'pic']);
    $period = ProductionPeriod::factory()->create();

    Sanctum::actingAs($pic);

    $this->getJson("/api/v1/investor/periods/{$period->id}")
        ->assertForbidden();
});

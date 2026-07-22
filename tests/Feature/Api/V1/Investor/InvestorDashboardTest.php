<?php

declare(strict_types=1);

use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns investor dashboard for assigned investor', function (): void {
    $investor = User::factory()->create(['role' => 'investor']);
    $period = ProductionPeriod::factory()->create([
        'period_code' => 'P-TEST-01',
        'status' => 'active',
    ]);

    PeriodInvestor::factory()->create([
        'period_id' => $period->id,
        'user_id' => $investor->id,
        'profit_share_percentage' => 25,
        'initial_investment' => 10000000,
        'final_dividend_amount' => 12500000,
        'is_paid' => false,
    ]);

    Sanctum::actingAs($investor);

    $response = $this->getJson('/api/v1/investor/dashboard');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.summary.period_count', 1)
        ->assertJsonPath('data.periods.0.period_code', 'P-TEST-01');
});

it('forbids non-investor roles', function (): void {
    $abk = User::factory()->create(['role' => 'abk']);
    Sanctum::actingAs($abk);

    $this->getJson('/api/v1/investor/dashboard')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

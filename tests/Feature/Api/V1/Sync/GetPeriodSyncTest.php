<?php

declare(strict_types=1);

use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EmployeeSalary;
use App\Models\Farm;
use App\Models\PeriodDocument;
use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'abk']);
    Sanctum::actingAs($this->user);

    $this->farm = Farm::factory()->create();
    $this->coop = Coop::factory()->create(['farm_id' => $this->farm->id]);
    $this->floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);
    $this->period = ProductionPeriod::factory()->create(['floor_id' => $this->floor->id]);

    CoopUserAssignment::factory()->create([
        'user_id' => $this->user->id,
        'coop_id' => $this->coop->id,
    ]);
});

it('returns period detail with only the user salary', function () {
    $otherUser = User::factory()->create(['role' => 'abk']);

    EmployeeSalary::factory()->create([
        'period_id' => $this->period->id,
        'employee_id' => $this->user->id,
    ]);

    EmployeeSalary::factory()->create([
        'period_id' => $this->period->id,
        'employee_id' => $otherUser->id,
    ]);

    $contract = ContractAbk::factory()->create(['period_id' => $this->period->id]);
    ContractAcceptance::factory()->create(['contract_id' => $contract->id, 'user_id' => $this->user->id]);

    PeriodDocument::factory()->create([
        'period_id' => $this->period->id,
        'uploaded_by' => $this->user->id,
    ]);

    PeriodInvestor::factory()->create([
        'period_id' => $this->period->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->getJson('/api/v1/sync/periods?' . http_build_query([
        'period_id' => $this->period->id,
    ]));

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                ['id', 'floor_id', 'period_code', 'salaries', 'contracts', 'documents', 'investors'],
            ],
        ]);

    $response->assertJsonCount(1, 'data');
    $response->assertJsonCount(1, 'data.0.salaries');
    $response->assertJsonPath('data.0.salaries.0.user_id', $this->user->id);
    $response->assertJsonCount(0, 'data.0.investors');
});

it('does not expose other employees salaries', function () {
    $otherUser = User::factory()->create(['role' => 'abk']);

    EmployeeSalary::factory()->create([
        'period_id' => $this->period->id,
        'employee_id' => $this->user->id,
    ]);

    EmployeeSalary::factory()->create([
        'period_id' => $this->period->id,
        'employee_id' => $otherUser->id,
    ]);

    $response = $this->getJson('/api/v1/sync/periods?' . http_build_query([
        'period_id' => $this->period->id,
    ]));

    $response->assertOk();

    $salaryUserIds = collect($response->json('data.0.salaries'))->pluck('user_id')->all();
    expect($salaryUserIds)->toEqual([$this->user->id]);
});

it('returns 403 when user is not assigned to the coop', function () {
    $unassignedUser = User::factory()->create(['role' => 'abk']);
    Sanctum::actingAs($unassignedUser);

    $response = $this->getJson('/api/v1/sync/periods?' . http_build_query([
        'period_id' => $this->period->id,
    ]));

    $response->assertStatus(403);
});

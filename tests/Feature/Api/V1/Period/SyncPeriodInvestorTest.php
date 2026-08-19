<?php

use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('POST /api/v1/periods/{period_id}/investors', function () {
    describe('Authed user', function () {
        beforeEach(function () {
            $this->user = User::factory()->create();
            Sanctum::actingAs($this->user, ['*']);
        });

        it('successfully syncs 2 investors for a period', function () {
            $period = ProductionPeriod::factory()->create([
                'start_date' => now()->addDays(2),
                'status' => 'active',
            ]);
            $investor1 = User::factory()->create(['role' => 'investor']);
            $investor2 = User::factory()->create(['role' => 'investor']);
            $payload = [
                'investors' => [
                    [
                        'user_id' => $investor1->id,
                        'profit_share_percentage' => 60,
                        'initial_investment' => 1000000,
                    ],
                    [
                        'user_id' => $investor2->id,
                        'profit_share_percentage' => 40,
                        'initial_investment' => 2000000,
                    ],
                ],
            ];
            $response = postJson("/api/v1/periods/{$period->id}/investors", $payload);
            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'Investor periode berhasil disinkronisasi.',
                ]);
            $this->assertDatabaseCount('period_investors', 2);
        });

        it('successfully clears all investors (empty array)', function () {
            $period = ProductionPeriod::factory()->create([
                'start_date' => now()->addDays(2),
                'status' => 'active',
            ]);
            $investor = User::factory()->create(['role' => 'investor']);
            // Seed 1 investor
            $period->period_investors()->create([
                'user_id' => $investor->id,
                'profit_share_percentage' => 100,
                'created_at_client' => now(),
                'updated_at_client' => now(),
                'sync_status' => 'PENDING_SYNC',
            ]);
            $payload = ['investors' => []];
            $response = postJson("/api/v1/periods/{$period->id}/investors", $payload);
            $response->assertOk();
            $this->assertDatabaseCount('period_investors', 0);
        });

        it('fails with 422 if total profit_share_percentage > 100', function () {
            $period = ProductionPeriod::factory()->create([
                'start_date' => now()->addDays(2),
                'status' => 'active',
            ]);
            $investor1 = User::factory()->create(['role' => 'investor']);
            $investor2 = User::factory()->create(['role' => 'investor']);
            $payload = [
                'investors' => [
                    [
                        'user_id' => $investor1->id,
                        'profit_share_percentage' => 60,
                    ],
                    [
                        'user_id' => $investor2->id,
                        'profit_share_percentage' => 50,
                    ],
                ],
            ];
            $response = postJson("/api/v1/periods/{$period->id}/investors", $payload);
            $response->assertStatus(422)
                ->assertJsonValidationErrors('investors');
        });

        it('fails with 422 if user_id is not investor', function () {
            $period = ProductionPeriod::factory()->create([
                'start_date' => now()->addDays(2),
                'status' => 'active',
            ]);
            $user = User::factory()->create(['role' => 'pic']);
            $payload = [
                'investors' => [
                    [
                        'user_id' => $user->id,
                        'profit_share_percentage' => 100,
                    ],
                ],
            ];
            $response = postJson("/api/v1/periods/{$period->id}/investors", $payload);
            $response->assertStatus(422)
                ->assertJsonValidationErrors('investors');
        });

        it('successfully syncs investors for a draft period (setup phase)', function () {
            $period = ProductionPeriod::factory()->create([
                'start_date' => now(),
                'status' => 'draft',
            ]);
            $investor = User::factory()->create(['role' => 'investor']);
            $payload = [
                'investors' => [
                    [
                        'user_id' => $investor->id,
                        'profit_share_percentage' => 100,
                    ],
                ],
            ];
            $response = postJson("/api/v1/periods/{$period->id}/investors", $payload);
            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'Investor periode berhasil disinkronisasi.',
                ]);
            $this->assertDatabaseCount('period_investors', 1);
        });

        it('fails with 422 if period already started', function () {
            $period = ProductionPeriod::factory()->create([
                'start_date' => now()->subDay(),
                'status' => 'active',
            ]);
            $investor = User::factory()->create(['role' => 'investor']);
            $payload = [
                'investors' => [
                    [
                        'user_id' => $investor->id,
                        'profit_share_percentage' => 100,
                    ],
                ],
            ];
            $response = postJson("/api/v1/periods/{$period->id}/investors", $payload);
            $response->assertStatus(422)
                ->assertJsonValidationErrors('period_id');
        });
    });

    it('returns 401 if not authenticated', function () {
        $period = ProductionPeriod::factory()->create([
            'start_date' => now()->addDays(2),
            'status' => 'active',
        ]);
        $investor = User::factory()->create(['role' => 'investor']);
        $payload = [
            'investors' => [
                [
                    'user_id' => $investor->id,
                    'profit_share_percentage' => 100,
                ],
            ],
        ];
        // Tanpa Sanctum::actingAs
        $this->withHeaders(['Accept' => 'application/json']);
        $response = postJson("/api/v1/periods/{$period->id}/investors", $payload);
        $response->assertUnauthorized();
    });
});

describe('GET /api/v1/periods/{period_id}/investors', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('returns assigned investors for a period', function () {
        $period = ProductionPeriod::factory()->create([
            'start_date' => now()->addDays(2),
            'status' => 'active',
        ]);
        $investor = User::factory()->create([
            'role' => 'investor',
            'name' => 'Investor Satu',
        ]);

        PeriodInvestor::factory()->create([
            'period_id' => $period->id,
            'user_id' => $investor->id,
            'profit_share_percentage' => 55,
            'initial_investment' => 1500000,
        ]);

        getJson("/api/v1/periods/{$period->id}/investors")
            ->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Daftar investor periode berhasil diambil.',
            ])
            ->assertJsonPath('data.0.user_id', $investor->id)
            ->assertJsonPath('data.0.user_name', 'Investor Satu')
            ->assertJsonPath('data.0.profit_share_percentage', 55);
    });
});

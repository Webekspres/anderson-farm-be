<?php

use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\DailyChecklistLog;
use App\Models\DailyDynamicLog;
use App\Models\HarvestEntry;
use App\Models\OvkUsage;
use App\Models\PhotoEvidence;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAuthUser(): User
{
    return User::factory()->create();
}

function assignAuthUserToPeriodCoop(User $user, ProductionPeriod $period): void
{
    $period->loadMissing('floor');
    CoopUserAssignment::factory()->create([
        'user_id' => $user->id,
        'coop_id' => $period->floor->coop_id,
    ]);
}

function createPeriodWithHeaders(int $headerCount = 3, ?string $updatedAtServer = null): array
{
    $period = ProductionPeriod::factory()->create();

    $headers = DailyActivityHeader::factory()
        ->count($headerCount)
        ->create([
            'period_id' => $period->id,
            'updated_at_server' => $updatedAtServer ?? now(),
        ]);

    return [$period, $headers];
}

describe('GET /api/v1/sync/daily-activities', function () {

    it('returns all headers for a period on fresh sync (no last_sync_timestamp)', function () {
        $user = createAuthUser();
        [$period, $headers] = createPeriodWithHeaders(3, now()->subDays(1)->toIso8601String());

        // Buat child relations untuk header pertama agar kita bisa cek eager loading
        $firstHeader = $headers->first();
        DailyDynamicLog::factory()->create(['header_id' => $firstHeader->id]);
        HarvestEntry::factory()->create(['header_id' => $firstHeader->id]);
        OvkUsage::factory()->create(['header_id' => $firstHeader->id]);
        PhotoEvidence::factory()->create(['header_id' => $firstHeader->id]);
        DailyChecklistLog::factory()->create(['header_id' => $firstHeader->id]);

        assignAuthUserToPeriodCoop($user, $period);

        $response = $this->actingAs($user)->getJson('/api/v1/sync/daily-activities?'.http_build_query([
            'period_id' => $period->id,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'current_server_timestamp',
            'data' => [
                '*' => [
                    'id',
                    'period_id',
                    'date',
                    'mortality_count',
                    'business_status',
                    'dynamic_logs',
                    'harvests',
                    'ovk_usages',
                    'photos',
                    'checklist_logs',
                ],
            ],
        ]);
        $response->assertJsonCount(3, 'data');
        $response->assertJson(['success' => true]);

        // Pastikan child relations ter-eager load pada header pertama
        $headerData = collect($response->json('data'))->firstWhere('id', $firstHeader->id);
        expect($headerData['dynamic_logs'])->toHaveCount(1);
        expect($headerData['harvests'])->toHaveCount(1);
        expect($headerData['ovk_usages'])->toHaveCount(1);
        expect($headerData['photos'])->toHaveCount(1);
        expect($headerData['checklist_logs'])->toHaveCount(1);
    });

    it('returns only delta records when last_sync_timestamp is provided', function () {
        $user = createAuthUser();
        $period = ProductionPeriod::factory()->create();

        // 2 header lama (sebelum timestamp)
        DailyActivityHeader::factory()->count(2)->create([
            'period_id' => $period->id,
            'updated_at_server' => '2026-04-20T08:00:00Z',
        ]);

        // 1 header baru (setelah timestamp)
        $newHeader = DailyActivityHeader::factory()->create([
            'period_id' => $period->id,
            'updated_at_server' => '2026-04-22T12:00:00Z',
        ]);

        assignAuthUserToPeriodCoop($user, $period);

        $response = $this->actingAs($user)->getJson('/api/v1/sync/daily-activities?'.http_build_query([
            'period_id' => $period->id,
            'last_sync_timestamp' => '2026-04-21T00:00:00Z',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $newHeader->id);
    });

    it('includes soft-deleted records in sync results', function () {
        $user = createAuthUser();
        $period = ProductionPeriod::factory()->create();

        $deletedHeader = DailyActivityHeader::factory()->create([
            'period_id' => $period->id,
            'updated_at_server' => now(),
        ]);
        $deletedHeader->delete(); // soft delete

        assignAuthUserToPeriodCoop($user, $period);

        $response = $this->actingAs($user)->getJson('/api/v1/sync/daily-activities?'.http_build_query([
            'period_id' => $period->id,
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $deletedHeader->id);

        // Pastikan deleted_at tidak null sehingga mobile tahu record ini dihapus
        expect($response->json('data.0.deleted_at'))->not->toBeNull();
    });

    it('returns 422 when period_id is missing', function () {
        $user = createAuthUser();

        $response = $this->actingAs($user)->getJson('/api/v1/sync/daily-activities');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('period_id');
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->getJson('/api/v1/sync/daily-activities?'.http_build_query([
            'period_id' => fake()->uuid(),
        ]));

        $response->assertUnauthorized();
    });

    it('returns 403 when the user is not assigned to the period coop (authorization leak)', function () {
        $userA = createAuthUser();
        $userB = createAuthUser();

        $periodA = ProductionPeriod::factory()->create();
        $periodB = ProductionPeriod::factory()->create();

        assignAuthUserToPeriodCoop($userA, $periodA);

        expect($userB->id)->not->toBe($userA->id);

        $response = $this->actingAs($userA)->getJson('/api/v1/sync/daily-activities?'.http_build_query([
            'period_id' => $periodB->id,
        ]));

        $response->assertForbidden();
    });
});

<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\HarvestEntry;
use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-01-05 12:00:00');

    $this->coop = Coop::factory()->create();
    $this->floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);
    $this->period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floor->id,
        'period_code' => 'PERIOD-EVAL-01',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-05',
        'initial_stock' => 1000,
        'status' => 'completed',
    ]);

    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->outsider = User::factory()->create(['role' => 'pic']);
    $this->investor = User::factory()->create(['role' => 'investor']);

    CoopUserAssignment::factory()->create([
        'coop_id' => $this->coop->id,
        'user_id' => $this->manager->id,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('GET /api/v1/export/evaluations', function () {
    it('downloads excel with daily evaluation rows and grand total formulas', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $headerDay1 = DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-01',
            'age_days' => 1,
            'mortality_count' => 10,
            'cull_count' => 5,
            'feed_consumption_kg' => 100,
            'average_weight' => 0.5,
        ]);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-02',
            'age_days' => 2,
            'mortality_count' => 0,
            'cull_count' => 0,
            'feed_consumption_kg' => 120,
            'average_weight' => 0.55,
        ]);

        $headerDay3 = DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-03',
            'age_days' => 3,
            'mortality_count' => 2,
            'cull_count' => 1,
            'feed_consumption_kg' => 80,
            'average_weight' => 0.6,
        ]);

        HarvestEntry::factory()->create([
            'header_id' => $headerDay3->id,
            'total_birds' => 50,
            'total_weight' => 100,
            'price_per_kg' => 20000,
            'total_revenue' => 2000000,
        ]);

        $response = $this->get('/api/v1/export/evaluations?period_id='.$this->period->id);

        $response->assertOk();
        expect($response->headers->get('Content-Type'))
            ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        expect($response->headers->get('Content-Disposition'))
            ->toContain('EVALUATION_PERIOD-EVAL-01');

        $tempPath = tempnam(sys_get_temp_dir(), 'evaluation_export_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        $sheet = IOFactory::load($tempPath)->getActiveSheet();

        expect($sheet->getTitle())->toBe('Evaluasi Performa');
        expect($sheet->getCell('A1')->getValue())->toBe('Hari / Umur (Day)');
        expect($sheet->getCell('M1')->getValue())->toBe('FCR Berjalan');

        expect($sheet->getCell('A2')->getValue())->toBe(1);
        expect($sheet->getCell('B2')->getValue())->toBe('2026-01-01');
        expect($sheet->getCell('C2')->getValue())->toBe(1000);
        expect($sheet->getCell('D2')->getValue())->toBe(10);
        expect($sheet->getCell('E2')->getValue())->toBe(5);
        expect($sheet->getCell('J2')->getValue())->toEqual(100);

        expect($sheet->getCell('C3')->getValue())->toBe(985);
        expect($sheet->getCell('A6')->getValue())->toBe(5);
        expect($sheet->getCell('C7')->getValue())->toBe('GRAND TOTAL');
        expect($sheet->getCell('D7')->getValue())->toBe('=SUM(D2:D6)');
        expect($sheet->getCell('J7')->getValue())->toBe('=SUM(J2:J6)');

        @unlink($tempPath);
    });

    it('returns 422 when period_id is missing', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $this->getJson('/api/v1/export/evaluations')
            ->assertUnprocessable()
            ->assertJsonPath('errors.period_id.0', 'ID periode harus diisi.');
    });

    it('fills synthetic rows for missing daily logs and carries forward opening stock', function () {
        Sanctum::actingAs($this->manager, ['*']);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-01',
            'age_days' => 1,
            'mortality_count' => 10,
            'cull_count' => 5,
            'feed_consumption_kg' => 0,
            'average_weight' => null,
        ]);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-02',
            'age_days' => 2,
            'mortality_count' => 0,
            'cull_count' => 0,
            'feed_consumption_kg' => 0,
            'average_weight' => null,
        ]);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-04',
            'age_days' => 4,
            'mortality_count' => 1,
            'cull_count' => 0,
            'feed_consumption_kg' => 0,
            'average_weight' => null,
        ]);

        $response = $this->get('/api/v1/export/evaluations?period_id='.$this->period->id);
        $response->assertOk();

        $tempPath = tempnam(sys_get_temp_dir(), 'evaluation_gap_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());
        $sheet = IOFactory::load($tempPath)->getActiveSheet();

        expect($sheet->getCell('A5')->getValue())->toBe(4);
        expect($sheet->getCell('B4')->getValue())->toBe('2026-01-03');
        expect($sheet->getCell('D4')->getValue())->toEqual(0);
        expect($sheet->getCell('E4')->getValue())->toEqual(0);
        expect($sheet->getCell('J4')->getValue())->toEqual(0);
        expect($sheet->getCell('C5')->getValue())->toBe(985);

        @unlink($tempPath);
    });

    it('carry-forwards average weight when sampling is null', function () {
        Sanctum::actingAs($this->manager, ['*']);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-01',
            'age_days' => 1,
            'average_weight' => 0.8,
            'feed_consumption_kg' => 50,
        ]);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-02',
            'age_days' => 2,
            'average_weight' => null,
            'feed_consumption_kg' => 60,
        ]);

        $response = $this->get('/api/v1/export/evaluations?period_id='.$this->period->id);
        $response->assertOk();

        $tempPath = tempnam(sys_get_temp_dir(), 'evaluation_bw_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());
        $sheet = IOFactory::load($tempPath)->getActiveSheet();

        expect($sheet->getCell('L2')->getValue())->toEqual(0.8);
        expect($sheet->getCell('L3')->getValue())->toEqual(0.8);
        expect($sheet->getCell('M3')->getValue())->toBeGreaterThan(0);

        @unlink($tempPath);
    });

    it('returns zero running FCR when denominator is zero on early cycle days', function () {
        Sanctum::actingAs($this->manager, ['*']);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-01-01',
            'age_days' => 1,
            'average_weight' => null,
            'feed_consumption_kg' => 0,
        ]);

        $response = $this->get('/api/v1/export/evaluations?period_id='.$this->period->id);
        $response->assertOk();

        $tempPath = tempnam(sys_get_temp_dir(), 'evaluation_fcr_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());
        $sheet = IOFactory::load($tempPath)->getActiveSheet();

        expect($sheet->getCell('M2')->getValue())->toEqual(0);

        @unlink($tempPath);
    });

    it('downloads excel with header row only when period has no activity logs', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $response = $this->get('/api/v1/export/evaluations?period_id='.$this->period->id);
        $response->assertOk();

        $tempPath = tempnam(sys_get_temp_dir(), 'evaluation_empty_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());
        $sheet = IOFactory::load($tempPath)->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('Hari / Umur (Day)');
        expect($sheet->getCell('A2')->getValue())->toBeIn([null, '']);
        expect($sheet->getCell('C2')->getValue())->toBeIn([null, '']);

        @unlink($tempPath);
    });

    it('returns 403 when user has no access to the period', function () {
        Sanctum::actingAs($this->outsider, ['*']);

        $this->getJson('/api/v1/export/evaluations?period_id='.$this->period->id)
            ->assertForbidden();
    });

    it('returns 403 when investor is not assigned to the period', function () {
        Sanctum::actingAs($this->investor, ['*']);

        $this->getJson('/api/v1/export/evaluations?period_id='.$this->period->id)
            ->assertForbidden();
    });

    it('allows assigned investor to download evaluation export', function () {
        PeriodInvestor::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->investor->id,
        ]);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'age_days' => 1,
            'date' => '2026-01-01',
        ]);

        Sanctum::actingAs($this->investor, ['*']);

        $this->get('/api/v1/export/evaluations?period_id='.$this->period->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('allows admin to export without coop or investor assignment', function () {
        $admin = User::factory()->create(['role' => 'admin']);

        DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'age_days' => 1,
            'date' => '2026-01-01',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $this->get('/api/v1/export/evaluations?period_id='.$this->period->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('returns 404 when period does not exist', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $this->getJson('/api/v1/export/evaluations?period_id='.Str::uuid())
            ->assertNotFound();
    });

    it('returns 401 when guest attempts export', function () {
        $this->getJson('/api/v1/export/evaluations?period_id='.$this->period->id)
            ->assertUnauthorized();
    });
});

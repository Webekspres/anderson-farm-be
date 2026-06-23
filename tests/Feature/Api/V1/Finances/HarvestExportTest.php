<?php

use App\Models\Area;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\Farm;
use App\Models\HarvestEntry;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coop = Coop::factory()->create();
    $this->floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);
    $this->period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floor->id,
        'period_code' => 'PERIOD-HARVEST-01',
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
    ]);

    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->outsider = User::factory()->create(['role' => 'pic']);

    CoopUserAssignment::factory()->create([
        'coop_id' => $this->coop->id,
        'user_id' => $this->manager->id,
    ]);
});

describe('GET /api/v1/export/harvests', function () {
    it('downloads excel with harvest recap and grand total formulas for valid period', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $header = DailyActivityHeader::factory()->create([
            'period_id' => $this->period->id,
            'user_id' => $this->manager->id,
            'date' => '2026-03-15 08:00:00',
            'age_days' => 28,
        ]);

        HarvestEntry::factory()->create([
            'header_id' => $header->id,
            'rit_number' => 1,
            'buyer_name' => 'Bakul A',
            'delivery_order_no' => 'DO-001',
            'total_birds' => 100,
            'total_weight' => 250.0,
            'price_per_kg' => 20000,
            'total_revenue' => 5000000,
        ]);

        HarvestEntry::factory()->create([
            'header_id' => $header->id,
            'rit_number' => 2,
            'buyer_name' => 'Bakul B',
            'delivery_order_no' => 'DO-002',
            'total_birds' => 50,
            'total_weight' => 120.0,
            'price_per_kg' => 21000,
            'total_revenue' => 2520000,
        ]);

        $response = $this->get('/api/v1/export/harvests?period_id='.$this->period->id);

        $response->assertOk();
        expect($response->headers->get('Content-Type'))
            ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        expect($response->headers->get('Content-Disposition'))
            ->toContain('attachment')
            ->toContain('HARVEST_PERIOD-HARVEST-01');

        $tempPath = tempnam(sys_get_temp_dir(), 'harvest_export_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        $sheet = IOFactory::load($tempPath)->getActiveSheet();

        expect($sheet->getTitle())->toBe('Rekap Panen');
        expect($sheet->getCell('A1')->getValue())->toBe('Tanggal Panen');
        expect($sheet->getCell('B1')->getValue())->toBe('Umur Ayam (Hari)');
        expect($sheet->getCell('C1')->getValue())->toBe('Rit Ke-');
        expect($sheet->getCell('D1')->getValue())->toBe('Nama Pembeli / Bakul');
        expect($sheet->getCell('E1')->getValue())->toBe('No. Surat Jalan (DO)');
        expect($sheet->getCell('F1')->getValue())->toBe('Jumlah (Ekor)');
        expect($sheet->getCell('G1')->getValue())->toBe('Total Berat (Kg)');
        expect($sheet->getCell('H1')->getValue())->toBe('Rata-rata Bobot (Kg)');
        expect($sheet->getCell('I1')->getValue())->toBe('Harga per Kg (Rp)');
        expect($sheet->getCell('J1')->getValue())->toBe('Total Nilai Omzet (Rp)');

        expect($sheet->getStyle('A1')->getFill()->getStartColor()->getARGB())->toBe('FFC6EFCE');
        expect($sheet->getStyle('A1')->getFont()->getBold())->toBeTrue();

        expect($sheet->getCell('A2')->getValue())->toBe('2026-03-15');
        expect($sheet->getCell('B2')->getValue())->toBe(28);
        expect($sheet->getCell('C2')->getValue())->toBe(1);
        expect($sheet->getCell('D2')->getValue())->toBe('Bakul A');
        expect($sheet->getCell('E2')->getValue())->toBe('DO-001');
        expect($sheet->getCell('F2')->getValue())->toBe(100);
        expect($sheet->getCell('G2')->getValue())->toBe(250.0);
        expect($sheet->getCell('H2')->getValue())->toBe(2.5);
        expect($sheet->getCell('I2')->getValue())->toEqual(20000);
        expect($sheet->getCell('J2')->getValue())->toEqual(5000000);

        expect($sheet->getCell('C3')->getValue())->toBe(2);
        expect($sheet->getCell('D3')->getValue())->toBe('Bakul B');
        expect($sheet->getCell('H3')->getValue())->toBe(2.4);

        expect($sheet->getCell('E4')->getValue())->toBe('GRAND TOTAL');
        expect($sheet->getCell('F4')->getValue())->toBe('=SUM(F2:F3)');
        expect($sheet->getCell('G4')->getValue())->toBe('=SUM(G2:G3)');
        expect($sheet->getCell('J4')->getValue())->toBe('=SUM(J2:J3)');

        @unlink($tempPath);
    });

    it('returns 422 when period_id is missing', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $this->getJson('/api/v1/export/harvests')
            ->assertUnprocessable()
            ->assertJsonPath('errors.period_id.0', 'ID periode harus diisi.');
    });

    it('returns 404 when period does not exist', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $fakePeriodId = (string) Str::uuid();

        $this->getJson('/api/v1/export/harvests?period_id='.$fakePeriodId)
            ->assertNotFound();
    });

    it('returns 403 when user has no coop access to the period', function () {
        Sanctum::actingAs($this->outsider, ['*']);

        $this->getJson('/api/v1/export/harvests?period_id='.$this->period->id)
            ->assertForbidden();
    });

    it('allows admin to export without coop assignment', function () {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin, ['*']);

        $this->get('/api/v1/export/harvests?period_id='.$this->period->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('allows manager to export when coop is under their managed area without assignment', function () {
        $areaManager = User::factory()->create(['role' => 'manager']);
        $area = Area::factory()->create(['manager_id' => $areaManager->id]);
        $farm = Farm::factory()->create(['area_id' => $area->id]);
        $coop = Coop::factory()->create(['farm_id' => $farm->id]);
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $period = ProductionPeriod::factory()->create(['floor_id' => $floor->id]);

        Sanctum::actingAs($areaManager, ['*']);

        $this->get('/api/v1/export/harvests?period_id='.$period->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('returns 403 when manager has neither area ownership nor coop assignment', function () {
        $unassignedManager = User::factory()->create(['role' => 'manager']);

        Sanctum::actingAs($unassignedManager, ['*']);

        $this->getJson('/api/v1/export/harvests?period_id='.$this->period->id)
            ->assertForbidden();
    });

    it('returns 401 when guest attempts export', function () {
        $this->getJson('/api/v1/export/harvests?period_id='.$this->period->id)
            ->assertUnauthorized();
    });

    it('downloads excel with header row only when period has no harvest entries', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $response = $this->get('/api/v1/export/harvests?period_id='.$this->period->id);

        $response->assertOk();

        $tempPath = tempnam(sys_get_temp_dir(), 'harvest_export_empty_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        $sheet = IOFactory::load($tempPath)->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('Tanggal Panen');
        expect($sheet->getCell('A2')->getValue())->toBeIn([null, '']);
        expect($sheet->getCell('E2')->getValue())->toBeIn([null, '']);

        @unlink($tempPath);
    });

    it('returns 422 when period_id is not a valid uuid', function () {
        Sanctum::actingAs($this->manager, ['*']);

        $this->getJson('/api/v1/export/harvests?period_id=not-a-uuid')
            ->assertUnprocessable()
            ->assertJsonPath('errors.period_id.0', 'ID periode harus UUID yang valid.');
    });
});

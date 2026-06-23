<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EmployeeSalary;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

function createMultiFloorSalaryScenario(): array
{
    $coop = Coop::factory()->create(['name' => 'Kandang A']);
    $floor1 = CoopFloor::factory()->create(['coop_id' => $coop->id, 'name' => 'Lantai 1']);
    $floor2 = CoopFloor::factory()->create(['coop_id' => $coop->id, 'name' => 'Lantai 2']);
    $floor3 = CoopFloor::factory()->create(['coop_id' => $coop->id, 'name' => 'Lantai 3']);

    $period1 = ProductionPeriod::factory()->create([
        'floor_id' => $floor1->id,
        'period_code' => 'PERIOD-F1',
        'status' => 'active',
    ]);
    $period2 = ProductionPeriod::factory()->create([
        'floor_id' => $floor2->id,
        'period_code' => 'PERIOD-F2',
        'status' => 'active',
    ]);
    $period3 = ProductionPeriod::factory()->create([
        'floor_id' => $floor3->id,
        'period_code' => 'PERIOD-F3',
        'status' => 'active',
    ]);

    $abk = User::factory()->create([
        'role' => 'abk',
        'username' => 'abk_utama',
        'name' => 'Budi ABK',
    ]);

    CoopUserAssignment::factory()->create([
        'coop_id' => $coop->id,
        'user_id' => $abk->id,
    ]);

    EmployeeSalary::factory()->create([
        'period_id' => $period1->id,
        'employee_id' => $abk->id,
        'salary_amount' => 2500000,
        'payment_status' => 'draft',
    ]);

    return compact('coop', 'floor1', 'floor2', 'floor3', 'period1', 'period2', 'period3', 'abk');
}

describe('GET /api/v1/export/template-salary', function () {
    it('allows finance to download excel with multi-floor rows and correct columns', function () {
        $scenario = createMultiFloorSalaryScenario();
        $finance = User::factory()->create(['role' => 'finance']);

        Sanctum::actingAs($finance, ['*']);

        $response = $this->get('/api/v1/export/template-salary');

        $response->assertOk();
        expect($response->headers->get('Content-Type'))
            ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        expect($response->headers->get('Content-Disposition'))
            ->toContain('TEMPLATE_GAJI_ABK');

        $tempPath = tempnam(sys_get_temp_dir(), 'salary_template_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('Period Code');
        expect($sheet->getCell('H1')->getValue())->toBe('Total Gaji');

        $dataRows = [];
        for ($row = 2; $row <= 4; $row++) {
            $dataRows[] = [
                'period_code' => $sheet->getCell("A{$row}")->getValue(),
                'username' => $sheet->getCell("B{$row}")->getValue(),
                'coop_id' => $sheet->getCell("C{$row}")->getValue(),
                'floor_id' => $sheet->getCell("D{$row}")->getValue(),
                'coop_name' => $sheet->getCell("E{$row}")->getValue(),
                'floor_name' => $sheet->getCell("F{$row}")->getValue(),
                'employee_name' => $sheet->getCell("G{$row}")->getValue(),
                'total_gaji' => (float) $sheet->getCell("H{$row}")->getCalculatedValue(),
            ];
        }

        expect($dataRows)->toHaveCount(3);
        expect(collect($dataRows)->pluck('period_code')->sort()->values()->all())
            ->toBe(['PERIOD-F1', 'PERIOD-F2', 'PERIOD-F3']);
        expect(collect($dataRows)->every(fn (array $r) => $r['username'] === 'abk_utama'))->toBeTrue();
        expect(collect($dataRows)->every(fn (array $r) => $r['coop_id'] === $scenario['coop']->id))->toBeTrue();
        expect(collect($dataRows)->every(fn (array $r) => $r['employee_name'] === 'Budi ABK'))->toBeTrue();

        $salaryByPeriod = collect($dataRows)->keyBy('period_code');
        expect($salaryByPeriod['PERIOD-F1']['total_gaji'])->toBe(2500000.0);
        expect($salaryByPeriod['PERIOD-F2']['total_gaji'])->toBe(0.0);
        expect($salaryByPeriod['PERIOD-F3']['total_gaji'])->toBe(0.0);

        @unlink($tempPath);
        $spreadsheet->disconnectWorksheets();
    });

    it('allows admin to download without coop assignment', function () {
        createMultiFloorSalaryScenario();
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin, ['*']);

        $this->get('/api/v1/export/template-salary')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('returns header only when active period has no abk assignments', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'status' => 'active',
        ]);

        $finance = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($finance, ['*']);

        $response = $this->get('/api/v1/export/template-salary');
        $response->assertOk();

        $tempPath = tempnam(sys_get_temp_dir(), 'salary_empty_').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        $sheet = IOFactory::load($tempPath)->getActiveSheet();
        expect($sheet->getCell('A1')->getValue())->toBe('Period Code');
        expect($sheet->getCell('A2')->getValue())->toBeIn([null, '']);

        @unlink($tempPath);
    });

    it('excludes completed periods from template', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $abk = User::factory()->create(['role' => 'abk', 'username' => 'abk_only_active']);

        CoopUserAssignment::factory()->create([
            'coop_id' => $coop->id,
            'user_id' => $abk->id,
        ]);

        ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'period_code' => 'PERIOD-ACTIVE',
            'status' => 'active',
        ]);

        ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'period_code' => 'PERIOD-DONE',
            'status' => 'completed',
        ]);

        $finance = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($finance, ['*']);

        $tempPath = tempnam(sys_get_temp_dir(), 'salary_filter_').'.xlsx';
        file_put_contents($tempPath, $this->get('/api/v1/export/template-salary')->streamedContent());

        $sheet = IOFactory::load($tempPath)->getActiveSheet();
        expect($sheet->getCell('A2')->getValue())->toBe('PERIOD-ACTIVE');
        expect($sheet->getCell('A3')->getValue())->toBeIn([null, '']);

        @unlink($tempPath);
    });

    it('returns 401 for guest', function () {
        $this->get('/api/v1/export/template-salary')->assertUnauthorized();
    });

    it('returns 403 for abk role', function () {
        createMultiFloorSalaryScenario();
        $abk = User::factory()->create(['role' => 'abk']);

        Sanctum::actingAs($abk, ['*']);

        $this->getJson('/api/v1/export/template-salary')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    });

    it('returns 403 for manager role', function () {
        createMultiFloorSalaryScenario();
        $manager = User::factory()->create(['role' => 'manager']);

        Sanctum::actingAs($manager, ['*']);

        $this->getJson('/api/v1/export/template-salary')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    });

    it('returns 403 for pic role', function () {
        createMultiFloorSalaryScenario();
        $pic = User::factory()->create(['role' => 'pic']);

        Sanctum::actingAs($pic, ['*']);

        $this->getJson('/api/v1/export/template-salary')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    });
});

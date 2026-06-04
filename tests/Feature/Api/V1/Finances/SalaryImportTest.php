<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EmployeeSalary;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

/**
 * @param  list<array{period_code: string, username: string, total_gaji: float|int|string|null}>  $rows
 */
function buildSalaryImportExcel(array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $headers = [
        'Period Code',
        'Employee Username',
        'Coop ID',
        'Floor ID',
        'Coop Name',
        'Floor Name',
        'Employee Name',
        'Total Gaji',
    ];

    foreach ($headers as $index => $label) {
        $sheet->setCellValue(chr(65 + $index).'1', $label);
    }

    $row = 2;

    foreach ($rows as $data) {
        $sheet->setCellValue("A{$row}", $data['period_code']);
        $sheet->setCellValue("B{$row}", $data['username']);
        $sheet->setCellValue("H{$row}", $data['total_gaji']);
        $row++;
    }

    $path = tempnam(sys_get_temp_dir(), 'salary_import_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

function salaryImportUploadedFile(string $path): UploadedFile
{
    return new UploadedFile(
        $path,
        'salary_import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}

function createSalaryImportCategory(): TransactionCategory
{
    return TransactionCategory::factory()->create([
        'name' => 'Gaji Pegawai',
        'type' => 'EXPENSE',
        'is_active' => true,
    ]);
}

function createSalaryImportScenario(): array
{
    createSalaryImportCategory();

    $coop = Coop::factory()->create();
    $floor1 = CoopFloor::factory()->create(['coop_id' => $coop->id]);
    $floor2 = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    $period1 = ProductionPeriod::factory()->create([
        'floor_id' => $floor1->id,
        'period_code' => 'PERIOD-IMP-1',
        'status' => 'active',
    ]);
    $period2 = ProductionPeriod::factory()->create([
        'floor_id' => $floor2->id,
        'period_code' => 'PERIOD-IMP-2',
        'status' => 'active',
    ]);

    $abk1 = User::factory()->create(['role' => 'abk', 'username' => 'abk_satu']);
    $abk2 = User::factory()->create(['role' => 'abk', 'username' => 'abk_dua']);

    CoopUserAssignment::factory()->create(['coop_id' => $coop->id, 'user_id' => $abk1->id]);
    CoopUserAssignment::factory()->create(['coop_id' => $coop->id, 'user_id' => $abk2->id]);

    return compact('period1', 'period2', 'abk1', 'abk2');
}

describe('POST /api/v1/import/salary', function () {
    it('imports valid rows and creates salaries with paid status and transactions', function () {
        createSalaryImportScenario();
        $finance = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($finance, ['*']);

        $path = buildSalaryImportExcel([
            ['period_code' => 'PERIOD-IMP-1', 'username' => 'abk_satu', 'total_gaji' => 1500000],
            ['period_code' => 'PERIOD-IMP-2', 'username' => 'abk_dua', 'total_gaji' => 2000000],
        ]);

        $response = $this->post('/api/v1/import/salary', [
            'file' => salaryImportUploadedFile($path),
        ]);

        @unlink($path);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);

        expect(EmployeeSalary::count())->toBe(2);
        expect(EmployeeSalary::where('payment_status', 'paid')->count())->toBe(2);
        expect(Transaction::whereNotNull('salary_id')->count())->toBe(2);

        $salary = EmployeeSalary::whereHas('employee', fn ($q) => $q->where('username', 'abk_satu'))->first();
        expect($salary)->not->toBeNull();
        expect($salary->salary_amount)->toBe(1500000.0);

        $transaction = Transaction::where('salary_id', $salary->id)->first();
        expect($transaction->amount)->toBe(1500000.0);
        expect($transaction->expense_scope)->toBe('FLOOR_SPECIFIC');
        expect($transaction->business_status)->toBe('APPROVED');
    });

    it('returns 422 with all row errors and rolls back on bulk validation failure', function () {
        createSalaryImportScenario();
        $finance = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($finance, ['*']);

        $path = buildSalaryImportExcel([
            ['period_code' => 'TIDAK-ADA', 'username' => 'abk_satu', 'total_gaji' => 1000000],
            ['period_code' => 'PERIOD-IMP-1', 'username' => 'tidak_ada_user', 'total_gaji' => 1000000],
        ]);

        $response = $this->postJson('/api/v1/import/salary', [
            'file' => salaryImportUploadedFile($path),
        ]);

        @unlink($path);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonCount(2, 'data.errors');

        expect(EmployeeSalary::count())->toBe(0);
        expect(Transaction::whereNotNull('salary_id')->count())->toBe(0);
    });

    it('skips zero amount rows when no existing salary and imports other rows', function () {
        createSalaryImportScenario();
        $finance = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($finance, ['*']);

        $path = buildSalaryImportExcel([
            ['period_code' => 'PERIOD-IMP-1', 'username' => 'abk_satu', 'total_gaji' => 0],
            ['period_code' => 'PERIOD-IMP-2', 'username' => 'abk_dua', 'total_gaji' => 500000],
        ]);

        $response = $this->post('/api/v1/import/salary', [
            'file' => salaryImportUploadedFile($path),
        ]);

        @unlink($path);

        $response->assertOk();
        expect(EmployeeSalary::count())->toBe(1);
        expect(EmployeeSalary::first()->salary_amount)->toBe(500000.0);
    });

    it('allows zero amount to update existing salary and transaction', function () {
        $scenario = createSalaryImportScenario();
        $finance = User::factory()->create(['role' => 'finance']);

        $existing = EmployeeSalary::factory()->create([
            'period_id' => $scenario['period1']->id,
            'employee_id' => $scenario['abk1']->id,
            'salary_amount' => 3000000,
            'payment_status' => 'draft',
        ]);

        Transaction::factory()->create([
            'salary_id' => $existing->id,
            'period_id' => $scenario['period1']->id,
            'amount' => 3000000,
        ]);

        Sanctum::actingAs($finance, ['*']);

        $path = buildSalaryImportExcel([
            ['period_code' => 'PERIOD-IMP-1', 'username' => 'abk_satu', 'total_gaji' => 0],
        ]);

        $response = $this->post('/api/v1/import/salary', [
            'file' => salaryImportUploadedFile($path),
        ]);

        @unlink($path);

        $response->assertOk();

        $existing->refresh();
        expect($existing->salary_amount)->toBe(0.0);
        expect($existing->payment_status)->toBe('paid');

        $transaction = Transaction::where('salary_id', $existing->id)->first();
        expect($transaction->amount)->toBe(0.0);
    });

    it('returns 401 for guest', function () {
        $path = buildSalaryImportExcel([
            ['period_code' => 'PERIOD-IMP-1', 'username' => 'abk_satu', 'total_gaji' => 1000],
        ]);

        $this->post('/api/v1/import/salary', [
            'file' => salaryImportUploadedFile($path),
        ])->assertUnauthorized();

        @unlink($path);
    });

    it('returns 403 for abk role', function () {
        createSalaryImportScenario();
        $abk = User::factory()->create(['role' => 'abk']);
        Sanctum::actingAs($abk, ['*']);

        $path = buildSalaryImportExcel([
            ['period_code' => 'PERIOD-IMP-1', 'username' => 'abk_satu', 'total_gaji' => 1000],
        ]);

        $this->postJson('/api/v1/import/salary', [
            'file' => salaryImportUploadedFile($path),
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        @unlink($path);
    });

    it('returns 422 when file is missing', function () {
        createSalaryImportCategory();
        $finance = User::factory()->create(['role' => 'finance']);
        Sanctum::actingAs($finance, ['*']);

        $this->postJson('/api/v1/import/salary', [])
            ->assertUnprocessable();
    });
});

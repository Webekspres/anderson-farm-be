<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coop = Coop::factory()->create();
    $this->floor = CoopFloor::factory()->create([
        'coop_id' => $this->coop->id,
        'name' => 'Lantai 1',
    ]);
    $this->period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floor->id,
        'period_code' => 'PERIOD-BOP-01',
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
    ]);

    $this->manager = User::factory()->create(['role' => 'manager']);

    CoopUserAssignment::factory()->create([
        'coop_id' => $this->coop->id,
        'user_id' => $this->manager->id,
    ]);

    $this->expenseCategory = TransactionCategory::factory()->create([
        'name' => 'Biaya Pakan',
        'type' => 'EXPENSE',
    ]);

    $this->incomeCategory = TransactionCategory::factory()->create([
        'name' => 'Penjualan',
        'type' => 'INCOME',
    ]);
});

it('downloads excel with detail and summary bop sheets', function () {
    Sanctum::actingAs($this->manager, ['*']);

    Transaction::factory()->create([
        'period_id' => $this->period->id,
        'category_id' => $this->expenseCategory->id,
        'date' => '2026-02-15 10:00:00',
        'amount' => 1500000,
        'description' => 'Beli pakan',
        'expense_scope' => 'FLOOR_SPECIFIC',
    ]);

    Transaction::factory()->create([
        'period_id' => $this->period->id,
        'category_id' => $this->expenseCategory->id,
        'coop_id' => $this->coop->id,
        'date' => '2026-02-16 10:00:00',
        'amount' => 500000,
        'description' => 'Listrik gedung',
        'expense_scope' => 'COOP_SHARED',
    ]);

    Transaction::factory()->create([
        'period_id' => $this->period->id,
        'category_id' => $this->incomeCategory->id,
        'amount' => 9999999,
    ]);

    $response = $this->get('/api/v1/export/bop-details?period_id='.$this->period->id);

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))
        ->toContain('BOP_DETAILS_PERIOD-BOP-01');

    $tempPath = tempnam(sys_get_temp_dir(), 'bop_export_').'.xlsx';
    file_put_contents($tempPath, $response->streamedContent());

    $spreadsheet = IOFactory::load($tempPath);
    $detailSheet = $spreadsheet->getSheetByName('Detail BOP');
    $summarySheet = $spreadsheet->getSheetByName('Summary BOP');

    expect($detailSheet)->not->toBeNull();
    expect($summarySheet)->not->toBeNull();

    expect($detailSheet->getCell('B2')->getValue())->toBe('PERIOD-BOP-01');
    expect($detailSheet->getCell('B3')->getValue())->toBe('2026-01-01');
    expect($detailSheet->getCell('B4')->getValue())->toBe('2026-06-30');
    expect($detailSheet->getCell('A5')->getValue())->toBeIn([null, '']);
    expect($detailSheet->getCell('A6')->getValue())->toBe('No');

    expect($detailSheet->getCell('A7')->getValue())->toBe(1);
    expect($detailSheet->getCell('F7')->getValue())->toBe('Lantai 1');
    expect($detailSheet->getCell('G7')->getValue())->toBe(1500000.0);

    expect($detailSheet->getCell('F8')->getValue())->toBe('Gedung Utama');
    expect($detailSheet->getCell('G8')->getValue())->toBe(500000.0);

    expect($detailSheet->getCell('F9')->getValue())->toBe('TOTAL');
    expect($detailSheet->getCell('G9')->getValue())->toBe(2000000.0);

    expect($summarySheet->getCell('A4')->getValue())->toBe('Biaya Pakan');
    expect($summarySheet->getCell('B4')->getValue())->toBe(2000000.0);
    expect($summarySheet->getCell('C4')->getValue())->toBe(100.0);

    @unlink($tempPath);
    $spreadsheet->disconnectWorksheets();
});

it('shows dash for end date when period is still open', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floor->id,
        'period_code' => 'PERIOD-OPEN',
        'start_date' => '2026-02-01',
        'end_date' => null,
    ]);

    $response = $this->get('/api/v1/export/bop-details?period_id='.$period->id);
    $response->assertOk();

    $tempPath = tempnam(sys_get_temp_dir(), 'bop_export_').'.xlsx';
    file_put_contents($tempPath, $response->streamedContent());

    $detailSheet = IOFactory::load($tempPath)->getSheetByName('Detail BOP');

    expect($detailSheet->getCell('B4')->getValue())->toBe('-');
    expect($detailSheet->getCell('A6')->getValue())->toBe('No');

    @unlink($tempPath);
});

it('returns 404 when period does not exist', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $this->getJson('/api/v1/export/bop-details?period_id='.Str::uuid())
        ->assertNotFound();
});

it('returns 403 when user has no access', function () {
    $outsider = User::factory()->create(['role' => 'pic']);

    Sanctum::actingAs($outsider, ['*']);

    $this->getJson('/api/v1/export/bop-details?period_id='.$this->period->id)
        ->assertForbidden();
});

it('allows admin to export without coop assignment', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($admin, ['*']);

    $this->get('/api/v1/export/bop-details?period_id='.$this->period->id)
        ->assertOk();
});

it('returns 401 for guest', function () {
    $this->getJson('/api/v1/export/bop-details?period_id='.$this->period->id)
        ->assertUnauthorized();
});

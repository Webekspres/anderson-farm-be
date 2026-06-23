<?php

use App\Models\Area;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\Farm;
use App\Models\OvkItem;
use App\Models\OvkUsage;
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
        'period_code' => 'PERIOD-TEST-01',
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

it('downloads excel with detail and summary sheets for valid period', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $ovkItem = OvkItem::factory()->create([
        'name' => 'Vaksin ND',
        'type' => 'VAKSIN',
        'unit' => 'ml',
    ]);

    $header = DailyActivityHeader::factory()->create([
        'period_id' => $this->period->id,
        'user_id' => $this->manager->id,
        'date' => '2026-02-10 08:00:00',
        'age_days' => 12,
    ]);

    OvkUsage::factory()->create([
        'header_id' => $header->id,
        'ovk_item_id' => $ovkItem->id,
        'quantity' => 2.5,
        'notes' => 'Dosis pagi',
    ]);

    $response = $this->get('/api/v1/export/ovk-usages?period_id='.$this->period->id);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain('OVK_USAGES_PERIOD-TEST-01');

    $tempPath = tempnam(sys_get_temp_dir(), 'ovk_export_').'.xlsx';
    file_put_contents($tempPath, $response->streamedContent());

    $spreadsheet = IOFactory::load($tempPath);
    $detailSheet = $spreadsheet->getSheetByName('Detail');
    $summarySheet = $spreadsheet->getSheetByName('Summary');

    expect($detailSheet)->not->toBeNull();
    expect($summarySheet)->not->toBeNull();
    expect($detailSheet->getCell('B2')->getValue())->toBe('PERIOD-TEST-01');
    expect($detailSheet->getCell('B3')->getValue())->toBe('2026-01-01');
    expect($detailSheet->getCell('B4')->getValue())->toBe('2026-06-30');
    expect($detailSheet->getCell('A5')->getValue())->toBeIn([null, '']);
    expect($detailSheet->getCell('A6')->getValue())->toBe('Tanggal');
    expect($detailSheet->getCell('A7')->getValue())->toBe('2026-02-10');
    expect($detailSheet->getCell('B7')->getValue())->toBe(12);
    expect($detailSheet->getCell('C7')->getValue())->toBe('Vaksin ND');
    expect($detailSheet->getCell('D7')->getValue())->toBe('VAKSIN');
    expect($detailSheet->getCell('E7')->getValue())->toBe(2.5);
    expect($detailSheet->getCell('F7')->getValue())->toBe('ml');
    expect($detailSheet->getCell('G7')->getValue())->toBe('Dosis pagi');

    expect($detailSheet->getStyle('A6')->getFill()->getStartColor()->getARGB())
        ->toBe('FFC6EFCE');
    expect($detailSheet->getStyle('A6')->getFont()->getBold())->toBeTrue();

    expect($summarySheet->getCell('A3')->getValue())->toBe('Nama Barang');
    expect($summarySheet->getCell('A4')->getValue())->toBe('Vaksin ND');
    expect($summarySheet->getCell('D4')->getValue())->toBe(2.5);
    expect($summarySheet->getCell('E4')->getValue())->toBe(1);

    @unlink($tempPath);
    $spreadsheet->disconnectWorksheets();
});

it('returns 404 when period does not exist', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $fakePeriodId = (string) Str::uuid();

    $this->getJson('/api/v1/export/ovk-usages?period_id='.$fakePeriodId)
        ->assertNotFound();
});

it('returns 403 when user has no coop access to the period', function () {
    Sanctum::actingAs($this->outsider, ['*']);

    $this->getJson('/api/v1/export/ovk-usages?period_id='.$this->period->id)
        ->assertForbidden();
});

it('allows admin to export without coop assignment', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($admin, ['*']);

    $this->get('/api/v1/export/ovk-usages?period_id='.$this->period->id)
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

    $this->get('/api/v1/export/ovk-usages?period_id='.$period->id)
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('returns 403 when manager has neither area ownership nor coop assignment', function () {
    $unassignedManager = User::factory()->create(['role' => 'manager']);

    Sanctum::actingAs($unassignedManager, ['*']);

    $this->getJson('/api/v1/export/ovk-usages?period_id='.$this->period->id)
        ->assertForbidden();
});

it('returns 401 when guest attempts export', function () {
    $this->getJson('/api/v1/export/ovk-usages?period_id='.$this->period->id)
        ->assertUnauthorized();
});

it('omits end date row in excel when period has no end date', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $period = ProductionPeriod::factory()->create([
        'floor_id' => $this->floor->id,
        'period_code' => 'PERIOD-OPEN',
        'start_date' => '2026-02-01',
        'end_date' => null,
    ]);

    $response = $this->get('/api/v1/export/ovk-usages?period_id='.$period->id);
    $response->assertOk();

    $tempPath = tempnam(sys_get_temp_dir(), 'ovk_export_').'.xlsx';
    file_put_contents($tempPath, $response->streamedContent());

    $detailSheet = IOFactory::load($tempPath)->getSheetByName('Detail');

    expect($detailSheet->getCell('B2')->getValue())->toBe('PERIOD-OPEN');
    expect($detailSheet->getCell('B3')->getValue())->toBe('2026-02-01');
    expect($detailSheet->getCell('A4')->getValue())->toBeIn([null, '']);
    expect($detailSheet->getCell('A5')->getValue())->toBe('Tanggal');

    @unlink($tempPath);
});

it('returns 422 when period_id is not a valid uuid', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $this->getJson('/api/v1/export/ovk-usages?period_id=not-a-uuid')
        ->assertUnprocessable()
        ->assertJsonPath('errors.period_id.0', 'ID periode harus UUID yang valid.');
});

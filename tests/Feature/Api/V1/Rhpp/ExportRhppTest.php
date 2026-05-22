<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create coop with floor and period
    $this->coop = Coop::factory()->create();
    $this->floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);
    $this->period = ProductionPeriod::factory()->create(['floor_id' => $this->floor->id]);

    // Create users with different roles
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->pic = User::factory()->create(['role' => 'pic']);
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->abk = User::factory()->create(['role' => 'abk']);
    $this->investor = User::factory()->create(['role' => 'investor']);
});

it('successfully streams an excel file for authorized manager', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->getJson('/api/v1/export/rhpp?period_id='.$this->period->id.'&format=excel');

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment');
});

it('successfully streams an excel file for authorized pic', function () {
    Sanctum::actingAs($this->pic, ['*']);

    $response = $this->getJson('/api/v1/export/rhpp?period_id='.$this->period->id.'&format=excel');

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('successfully streams a pdf file for authorized investor', function () {
    Sanctum::actingAs($this->investor, ['*']);

    $response = $this->getJson('/api/v1/export/rhpp?period_id='.$this->period->id.'&format=pdf');

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))
        ->toContain('application/pdf');
});

it('returns 403 forbidden if an abk attempts to export data', function () {
    Sanctum::actingAs($this->abk, ['*']);

    $response = $this->getJson('/api/v1/export/rhpp?period_id='.$this->period->id.'&format=excel');

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Hanya manager, PIC, admin, dan finance yang dapat mengexport RHPP.');
});

it('returns 422 unprocessable entity when format is invalid', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->getJson('/api/v1/export/rhpp?period_id='.$this->period->id.'&format=invalid-format');

    $response->assertStatus(422)
        ->assertJsonPath('errors.format.0', 'Format harus berupa "pdf" atau "excel".');
});

it('returns 422 when period_id is invalid uuid', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->getJson('/api/v1/export/rhpp?period_id=not-a-uuid&format=excel');

    $response->assertStatus(422)
        ->assertJsonPath('errors.period_id.0', 'ID periode harus UUID yang valid.');
});

it('returns 404 when period does not exist', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $fakePeriodId = (string) Str::uuid();
    $response = $this->getJson('/api/v1/export/rhpp?period_id='.$fakePeriodId.'&format=excel');

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Periode tidak ditemukan.');
});

it('returns 401 unauthorized when guest attempts to access', function () {
    $response = $this->getJson('/api/v1/export/rhpp?period_id='.$this->period->id.'&format=excel');

    $response->assertStatus(401);
});

it('returns 401 unauthorized with custom accept headers and no redirection', function () {
    $response = $this->get('/api/v1/export/rhpp?period_id='.$this->period->id.'&format=excel', [
        'Accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
            'data' => null,
        ]);
});

it('returns 422 validation error with custom accept headers and no redirection when validation fails', function () {
    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->get('/api/v1/export/rhpp?period_id='.$this->period->id.'&file_type=pdf', [
        'Accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Format export harus diisi (pdf atau excel).',
            'data' => null,
        ])
        ->assertJsonStructure(['errors']);
});

<?php
// tests/Feature/Api/V1/ReportTemplate/ReportTemplateTest.php

use App\Models\User;
use App\Models\ReportTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

const ENDPOINTREPORTTEMPLATE = '/api/v1/report-templates';


beforeEach(function () {
    // No global user, use local in each test
});

it('lists templates with page and cursor pagination', function () {
    $user = User::factory()->create();
    actingAs($user);
    ReportTemplate::factory()->count(20)->create();
    // Page
    $page = getJson(ENDPOINTREPORTTEMPLATE);
    $page->assertOk()->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
    // Cursor
    $cursor = getJson(ENDPOINTREPORTTEMPLATE . '?limit=5');
    $cursor->assertOk()->assertJsonStructure(['data' => ['items', 'next_cursor', 'prev_cursor', 'has_next', 'has_prev']]);
});

it('filters templates by report_type', function () {
    $user = User::factory()->create();
    actingAs($user);
    ReportTemplate::factory()->create(['report_type' => 'DAILY']);
    ReportTemplate::factory()->create(['report_type' => 'MONTHLY']);
    $res = getJson(ENDPOINTREPORTTEMPLATE . '?report_type=DAILY');
    $res->assertOk()->assertJsonCount(1, 'data.items');
});

it('returns 401 if not authenticated', function () {
    \Illuminate\Support\Facades\Auth::logout();
    getJson(ENDPOINTREPORTTEMPLATE)->assertUnauthorized();
});

it('creates a new template', function () {
    $user = User::factory()->create();
    actingAs($user);
    $payload = [
        'name' => 'Laporan Harian',
        'report_type' => 'DAILY',
        'content_placeholder' => 'Isi laporan harian',
        'created_at_client' => now()->toIso8601String(),
        'updated_at_client' => now()->toIso8601String(),
        'sync_status' => 'PENDING_SYNC',
    ];
    $res = postJson(ENDPOINTREPORTTEMPLATE, $payload);
    $res->assertCreated()->assertJsonPath('data.name', 'Laporan Harian');
});

it('fails to create with duplicate name', function () {
    $user = User::factory()->create();
    actingAs($user);
    ReportTemplate::factory()->create(['name' => 'Duplikat']);
    $payload = [
        'name' => 'Duplikat',
        'report_type' => 'DAILY',
        'content_placeholder' => 'Isi',
        'created_at_client' => now()->toIso8601String(),
        'updated_at_client' => now()->toIso8601String(),
        'sync_status' => 'PENDING_SYNC',
    ];
    $res = postJson(ENDPOINTREPORTTEMPLATE, $payload);
    $res->assertStatus(422)->assertJsonValidationErrors(['name']);
});

it('fails to create with empty content_placeholder', function () {
    $user = User::factory()->create();
    actingAs($user);
    $payload = [
        'name' => 'Laporan Kosong',
        'report_type' => 'DAILY',
        'content_placeholder' => '',
        'created_at_client' => now()->toIso8601String(),
        'updated_at_client' => now()->toIso8601String(),
        'sync_status' => 'PENDING_SYNC',
    ];
    $res = postJson(ENDPOINTREPORTTEMPLATE, $payload);
    $res->assertStatus(422)->assertJsonValidationErrors(['content_placeholder']);
});

it('updates only content_placeholder', function () {
    $user = User::factory()->create();
    actingAs($user);
    $template = ReportTemplate::factory()->create(['content_placeholder' => 'Lama']);
    $res = patchJson(ENDPOINTREPORTTEMPLATE . '/' . $template->id, ['content_placeholder' => 'Baru']);
    $res->assertOk()->assertJsonPath('data.content_placeholder', 'Baru');
});

it('updates without changing name (unique ignore works)', function () {
    $user = User::factory()->create();
    actingAs($user);
    $template = ReportTemplate::factory()->create(['name' => 'Unik']);
    $res = patchJson(ENDPOINTREPORTTEMPLATE . '/' . $template->id, ['name' => 'Unik']);
    $res->assertOk()->assertJsonPath('data.name', 'Unik');
});

it('returns 404 for invalid UUID on update', function () {
    $user = User::factory()->create();
    actingAs($user);
    patchJson(ENDPOINTREPORTTEMPLATE . '/invalid-uuid', ['name' => 'X'])->assertNotFound();
});

it('soft deletes a template', function () {
    $user = User::factory()->create();
    actingAs($user);
    $template = ReportTemplate::factory()->create();
    $res = deleteJson(ENDPOINTREPORTTEMPLATE . '/' . $template->id);
    $res->assertOk()->assertJsonPath('data', null);
    \Pest\Laravel\assertSoftDeleted('report_templates', ['id' => $template->id]);
});

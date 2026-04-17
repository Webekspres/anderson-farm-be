<?php
// tests/Feature/Api/V1/Area/AreaTest.php

declare(strict_types=1);

use App\Models\User;
use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\assertSoftDeleted;

const ENDPOINT = '/api/v1/areas';
uses(RefreshDatabase::class);


beforeEach(function () {
    // Setup sebelum setiap test jika diperlukan
});


it('lists areas with default page pagination', function () {
    $user = User::factory()->create();
    actingAs($user);
    Area::factory()->count(20)->create();
    $response = getJson(ENDPOINT);
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['items', 'total', 'per_page', 'current_page', 'last_page']
        ]);
});

it('lists areas with cursor pagination', function () {
    $user = User::factory()->create();
    actingAs($user);
    Area::factory()->count(20)->create();
    $first = getJson(ENDPOINT . '?limit=5');
    $first->assertOk()->assertJsonStructure(['data' => ['items', 'next_cursor', 'prev_cursor', 'has_next', 'has_prev']]);
    $nextCursor = $first->json('data.next_cursor');
    if ($nextCursor) {
        $next = getJson(ENDPOINT . '?limit=5&cursor=' . $nextCursor);
        $next->assertOk();
    }
});

it('successfully creates an area', function () {
    $user = User::factory()->create();
    actingAs($user);
    $payload = [
        'name' => 'Area Baru',
        'type' => 'KANDANG',
        'size_m2' => 1000,
        'manager_id' => $user->id,
    ];
    $response = postJson(ENDPOINT, $payload);
    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Area Baru');
});

it('fails to create area with invalid type', function () {
    $user = User::factory()->create();
    actingAs($user);
    $payload = [
        'name' => 'Area Salah',
        'type' => 'INVALID',
    ];
    $response = postJson(ENDPOINT, $payload);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('updates only the name of an area', function () {
    $user = User::factory()->create();
    actingAs($user);
    $area = Area::factory()->create();
    $response = patchJson(ENDPOINT . '/' . $area->id, [
        'name' => 'Nama Baru',
    ]);
    $response->assertOk()
        ->assertJsonPath('data.name', 'Nama Baru');
});

it('soft deletes an area', function () {
    $user = User::factory()->create();
    actingAs($user);
    $area = Area::factory()->create();
    $response = deleteJson(ENDPOINT . '/' . $area->id);
    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);
    assertSoftDeleted('areas', ['id' => $area->id]);
});

it('returns 401 if not authenticated', function () {
    $area = Area::factory()->create();
    // GET
    getJson(ENDPOINT)->assertUnauthorized();
    // POST
    postJson(ENDPOINT, [])->assertUnauthorized();
    // PATCH
    patchJson(ENDPOINT . '/' . $area->id, [])->assertUnauthorized();
    // DELETE
    deleteJson(ENDPOINT . '/' . $area->id)->assertUnauthorized();
});

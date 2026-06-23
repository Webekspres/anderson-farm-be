<?php

use App\Models\Farm;
use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

it('can list farms', function () {
    $ENDPOINT = '/api/v1/farms';
    Farm::factory()->count(3)->create();
    $response = $this->getJson($ENDPOINT);
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['items'],
        ])
        ->assertJson(['success' => true]);
});

it('can create a farm', function () {
    $ENDPOINT = '/api/v1/farms';
    $area = Area::factory()->create();
    $payload = [
        'area_id' => $area->id,
        'name' => 'Farm Alpha',
        'address' => 'Jl. Kebun No. 1',
        'type' => 'broiler',
        'is_active' => true,
        'sync_status' => 'PENDING_SYNC',
    ];
    $response = $this->postJson($ENDPOINT, $payload);
    $response->assertCreated()
        ->assertJsonPath('data.name', 'Farm Alpha')
        ->assertJson(['success' => true]);
    $this->assertDatabaseHas('farms', ['name' => 'Farm Alpha']);
});

it('can update a farm', function () {
    $ENDPOINT = '/api/v1/farms';
    $farm = Farm::factory()->create(['name' => 'Old Name', 'type' => 'broiler']);
    $payload = ['name' => 'New Name'];
    $response = $this->patchJson($ENDPOINT . '/' . $farm->id, $payload);
    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJson(['success' => true]);
    $this->assertDatabaseHas('farms', ['id' => $farm->id, 'name' => 'New Name']);
});

it('can soft delete a farm', function () {
    $ENDPOINT = '/api/v1/farms';
    $farm = Farm::factory()->create();
    $response = $this->deleteJson($ENDPOINT . '/' . $farm->id);
    $response->assertOk()
        ->assertJson(['success' => true]);
    $this->assertSoftDeleted('farms', ['id' => $farm->id]);
});

it('validates required fields on create', function () {
    $ENDPOINT = '/api/v1/farms';
    $response = $this->postJson($ENDPOINT, []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['area_id', 'name', 'address']);
});

<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

const COOP_FLOOR_ENDPOINT = '/api/v1/coop-floors';

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->coop = Coop::factory()->create();
});

it('allows admin to create a new coop floor', function () {
    Sanctum::actingAs($this->admin);

    $response = $this->postJson(COOP_FLOOR_ENDPOINT, [
        'coop_id' => $this->coop->id,
        'name' => 'Lantai 1',
        'capacity' => 12000,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Lantai 1')
        ->assertJsonPath('data.capacity', 12000)
        ->assertJsonPath('data.coop.uuid', $this->coop->id);

    $this->assertDatabaseHas('coop_floors', [
        'coop_id' => $this->coop->id,
        'name' => 'Lantai 1',
        'capacity' => 12000,
    ]);

    expect(CoopFloor::query()->where('coop_id', $this->coop->id)->value('server_id'))->not->toBeNull();
});

it('allows admin to update coop floor name', function () {
    Sanctum::actingAs($this->admin);

    $floor = CoopFloor::factory()->create([
        'coop_id' => $this->coop->id,
        'name' => 'Lantai Lama',
    ]);

    $response = $this->patchJson(COOP_FLOOR_ENDPOINT.'/'.$floor->id, [
        'name' => 'Lantai Baru',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Lantai Baru');

    $this->assertDatabaseHas('coop_floors', [
        'id' => $floor->id,
        'name' => 'Lantai Baru',
    ]);
});

it('prevents admin from deleting floor tied to active production period', function () {
    Sanctum::actingAs($this->admin);

    $floor = CoopFloor::factory()->create(['coop_id' => $this->coop->id]);

    ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'status' => 'active',
    ]);

    $response = $this->deleteJson(COOP_FLOOR_ENDPOINT.'/'.$floor->id);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false);

    $this->assertDatabaseHas('coop_floors', [
        'id' => $floor->id,
        'deleted_at' => null,
    ]);
});

it('rejects unauthenticated access', function () {
    $response = $this->getJson(COOP_FLOOR_ENDPOINT);

    $response->assertUnauthorized();
});

it('forbids non-admin from creating coop floor', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    Sanctum::actingAs($manager);

    $response = $this->postJson(COOP_FLOOR_ENDPOINT, [
        'coop_id' => $this->coop->id,
        'name' => 'Lantai 1',
        'capacity' => 5000,
    ]);

    $response->assertForbidden();
});

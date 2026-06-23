<?php

use App\Models\User;
use App\Models\Coop;
use App\Models\CoopUserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('successfully bulk assigns users to coop', function () {
    $user = User::factory()->create();
    actingAs($user);
    $coop = Coop::factory()->create();
    $users = User::factory()->count(2)->create();
    $payload = [
        'assignments' => $users->map(fn($u) => [
            'user_id' => $u->id,
            'role_in_coop' => 'ABK',
        ])->toArray(),
    ];
    $response = postJson("/api/v1/coops/{$coop->id}/user-assignments", $payload);
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Pekerja berhasil ditugaskan ke kandang',
            'data' => null,
        ]);
    assertDatabaseCount('coop_user_assignments', 2);
    foreach ($users as $user) {
        assertDatabaseHas('coop_user_assignments', [
            'user_id' => $user->id,
            'coop_id' => $coop->id,
        ]);
    }
});

it('successfully clears assignments when empty array sent', function () {
    $user = User::factory()->create();
    actingAs($user);
    $coop = Coop::factory()->create();
    $users = User::factory()->count(2)->create();
    foreach ($users as $user) {
        CoopUserAssignment::factory()->create([
            'user_id' => $user->id,
            'coop_id' => $coop->id,
        ]);
    }
    assertDatabaseCount('coop_user_assignments', 2);
    $payload = ['assignments' => []];
    $response = postJson("/api/v1/coops/{$coop->id}/user-assignments", $payload);
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Pekerja berhasil ditugaskan ke kandang',
            'data' => null,
        ]);
    expect(CoopUserAssignment::count())->toBe(0);
});

it('returns 404 if coop not found', function () {
    $user = User::factory()->create();
    actingAs($user);
    $payload = ['assignments' => []];
    $response = postJson("/api/v1/coops/non-existent-id/user-assignments", $payload);
    $response->assertStatus(404);
});

it('returns 422 if user_id not exists', function () {
    $user = User::factory()->create();
    actingAs($user);
    $coop = Coop::factory()->create();
    $payload = [
        'assignments' => [
            ['user_id' => 'not-exist-id', 'role_in_coop' => 'ABK'],
        ],
    ];
    $response = postJson("/api/v1/coops/{$coop->id}/user-assignments", $payload);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['assignments.0.user_id']);
});

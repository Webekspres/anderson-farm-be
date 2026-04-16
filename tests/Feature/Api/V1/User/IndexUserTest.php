<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Super']);

    // Buat 15 user dummy tambahan
    User::factory()->count(15)->create();
});

it('ditolak jika mengakses tanpa token (401)', function () {
    $response = $this->getJson('/api/v1/users');
    $response->assertStatus(401);
});

it('menggunakan page pagination secara default dan sesuai kontrak OpenAPI', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $response = $this->getJson('/api/v1/users?per_page=5');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
                'users' => [
                    '*' => ['id', 'username', 'name', 'role', 'email', 'phone', 'is_active']
                ]
            ]
        ]);

    // Total = 16 (15 dummy + 1 admin)
    expect($response->json('data.total'))->toBe(16)
        ->and($response->json('data.per_page'))->toBe(5)
        ->and($response->json('data.users'))->toHaveCount(5);
});

it('berpindah ke cursor pagination jika parameter cursor/limit diberikan', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $response = $this->getJson('/api/v1/users?limit=5');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'next_cursor',
                'prev_cursor',
                'has_next',
                'has_prev',
                'users'
            ]
        ]);

    // Memastikan atribut 'total' tidak ada di mode cursor
    expect($response->json('data.total'))->toBeNull()
        ->and($response->json('data.has_next'))->toBeTrue()
        ->and($response->json('data.next_cursor'))->not->toBeNull();
});

it('berhasil memfilter daftar user berdasarkan pencarian (search)', function () {
    Sanctum::actingAs($this->admin, ['*']);

    User::factory()->create([
        'name'     => 'Joko Peternak',
        'username' => 'joko_abk',
    ]);

    $response = $this->getJson('/api/v1/users?search=Joko');

    $response->assertStatus(200);

    expect($response->json('data.users'))->toHaveCount(1)
        ->and($response->json('data.users.0.name'))->toBe('Joko Peternak');
});

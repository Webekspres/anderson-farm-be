<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('berhasil membuat user baru', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $payload = [
        'username' => 'newuser',
        'password' => 'PasswordBaru123',
        'name'     => 'User Baru',
        'email'    => 'newuser@example.com',
        'phone'    => '08123456789',
        'role'     => 'pic',
    ];

    $response = $this->postJson('/api/v1/users', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'data'    => [
                'username' => 'newuser',
                'name'     => 'User Baru',
                'email'    => 'newuser@example.com',
                'phone'    => '08123456789',
                'role'     => 'pic',
                'is_active' => true,
            ]
        ]);

    $user = User::where('username', 'newuser')->first();
    expect($user)->not->toBeNull();
    expect(Hash::check('PasswordBaru123', $user->password_hash))->toBeTrue();
});

it('gagal karena username sudah dipakai', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $user = User::factory()->create(['username' => 'duplikat']);

    $payload = [
        'username' => 'duplikat',
        'password' => 'PasswordBaru123',
        'name'     => 'User Baru',
        'role'     => 'pic',
    ];

    $response = $this->postJson('/api/v1/users', $payload);
    $response->assertStatus(422);
});

it('ditolak jika tidak bawa token sanctum', function () {
    $payload = [
        'username' => 'notoken',
        'password' => 'PasswordBaru123',
        'name'     => 'User Baru',
        'role'     => 'pic',
    ];

    $response = $this->postJson('/api/v1/users', $payload);
    $response->assertStatus(401);
});

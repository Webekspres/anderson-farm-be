<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user1 = User::factory()->create(['username' => 'user1', 'email' => 'user1@mail.com']);
    $this->user2 = User::factory()->create(['username' => 'user2', 'email' => 'user2@mail.com']);
});

it('berhasil update sebagian data user', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $payload = ['name' => 'Nama Baru', 'role' => 'manager'];
    $response = $this->patchJson('/api/v1/users/' . $this->user1->server_id, $payload);
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data user berhasil diperbarui.',
            'data'    => [
                'id'   => $this->user1->server_id,
                'name' => 'Nama Baru',
                'role' => 'manager',
            ]
        ]);
    $this->assertDatabaseHas('users', [
        'server_id' => $this->user1->server_id,
        'name'      => 'Nama Baru',
        'role'      => 'manager',
    ]);
});

it('berhasil update password user', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $payload = ['password' => 'passwordBaru123'];
    $response = $this->patchJson('/api/v1/users/' . $this->user1->server_id, $payload);
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data user berhasil diperbarui.'
        ]);
    $this->assertTrue(Hash::check('passwordBaru123', $this->user1->fresh()->password_hash));
});

it('gagal update username/email yang sudah ada (422)', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $payload = ['username' => 'user2', 'email' => 'user2@mail.com'];
    $response = $this->patchJson('/api/v1/users/' . $this->user1->server_id, $payload);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username', 'email']);
});

it('gagal update user yang tidak ditemukan (404)', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $response = $this->patchJson('/api/v1/users/999999', ['name' => 'X']);
    $response->assertStatus(404);
});

it('gagal update tanpa token (401)', function () {
    $response = $this->patchJson('/api/v1/users/' . $this->user1->server_id, ['name' => 'X']);
    $response->assertStatus(401);
});

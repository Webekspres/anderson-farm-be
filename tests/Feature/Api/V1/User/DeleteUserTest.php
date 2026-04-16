<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create();
});

it('berhasil soft delete user', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $response = $this->deleteJson('/api/v1/users/' . $this->user->server_id);
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data user berhasil dihapus.',
            'data'    => null,
        ]);
    $this->assertSoftDeleted('users', ['server_id' => $this->user->server_id]);
});

it('gagal delete user yang tidak ditemukan (404)', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $response = $this->deleteJson('/api/v1/users/999999');
    $response->assertStatus(404);
});

it('gagal delete tanpa token (401)', function () {
    $response = $this->deleteJson('/api/v1/users/' . $this->user->server_id);
    $response->assertStatus(401);
});

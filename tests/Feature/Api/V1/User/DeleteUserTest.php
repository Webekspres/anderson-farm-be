<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create();
});

it('berhasil soft delete user', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $response = $this->deleteJson('/api/v1/users/'.$this->user->id);
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data user berhasil dihapus.',
            'data' => null,
        ]);
    $this->assertSoftDeleted('users', ['id' => $this->user->id]);
});

it('gagal delete user yang tidak ditemukan (404)', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $response = $this->deleteJson('/api/v1/users/550e8400-e29b-41d4-a716-446655440999');
    $response->assertStatus(404);
});

it('gagal delete tanpa token (401)', function () {
    $response = $this->deleteJson('/api/v1/users/'.$this->user->id);
    $response->assertStatus(401);
});

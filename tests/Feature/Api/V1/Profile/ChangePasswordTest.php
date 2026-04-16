<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'password_hash' => Hash::make('oldpassword123'),
    ]);
});

it('berhasil ubah password', function () {
    Sanctum::actingAs($this->user, ['*']);
    $payload = [
        'current_password' => 'oldpassword123',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'newpassword456',
    ];
    $response = $this->postJson('/api/v1/profile/change-password', $payload);
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Password berhasil diubah.',
            'data'    => null,
        ]);
    $this->assertTrue(Hash::check('newpassword456', $this->user->fresh()->password_hash));
});

it('gagal karena current_password salah', function () {
    Sanctum::actingAs($this->user, ['*']);
    $payload = [
        'current_password' => 'salahpassword',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'newpassword456',
    ];
    $response = $this->postJson('/api/v1/profile/change-password', $payload);
    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Password saat ini tidak cocok.',
            'data'    => null,
        ]);
});

it('gagal karena konfirmasi password tidak cocok', function () {
    Sanctum::actingAs($this->user, ['*']);
    $payload = [
        'current_password' => 'oldpassword123',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'beda',
    ];
    $response = $this->postJson('/api/v1/profile/change-password', $payload);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['new_password']);
});

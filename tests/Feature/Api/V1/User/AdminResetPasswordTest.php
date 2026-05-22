<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create an admin user
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin_super',
        'name' => 'Admin Utama',
    ]);

    // Create an ABK user (target for password reset)
    $this->abk = User::factory()->create([
        'role' => 'abk',
        'username' => 'budi_abk',
        'name' => 'Budi Santoso',
    ]);

    // Create other role users for authorization testing
    $this->manager = User::factory()->create([
        'role' => 'manager',
        'username' => 'manager_budi',
        'name' => 'Manager Budi',
    ]);

    $this->pic = User::factory()->create([
        'role' => 'pic',
        'username' => 'pic_Ahmad',
        'name' => 'PIC Ahmad',
    ]);
});

it('berhasil mereset password user oleh admin (200)', function () {
    // Authenticate as admin
    Sanctum::actingAs($this->admin, ['*']);

    // Store the original password hash to verify it changes
    $originalPasswordHash = $this->abk->password_hash;

    // The new password to set
    $newPassword = 'NewSecurePassword123';

    // Send POST request to reset password
    $response = $this->postJson(
        "/api/v1/users/{$this->abk->id}/reset-password",
        ['new_password' => $newPassword]
    );

    // Assert response status and format
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);

    // Refresh the user from DB to get updated password
    $this->abk->refresh();

    // Assert the password was actually changed in the database
    expect($this->abk->password_hash)->not->toBe($originalPasswordHash)
        ->and(Hash::check($newPassword, $this->abk->password_hash))->toBeTrue();
});

it('menolak reset password jika bukan admin (403)', function () {
    // Test with manager role
    Sanctum::actingAs($this->manager, ['*']);

    $response = $this->postJson(
        "/api/v1/users/{$this->abk->id}/reset-password",
        ['new_password' => 'NewSecurePassword123']
    );

    // Assert 403 Forbidden response
    $response->assertStatus(403)
        ->assertJsonPath('success', false);

    // Test with PIC role
    Sanctum::actingAs($this->pic, ['*']);

    $response = $this->postJson(
        "/api/v1/users/{$this->abk->id}/reset-password",
        ['new_password' => 'NewSecurePassword123']
    );

    // Assert 403 Forbidden response
    $response->assertStatus(403)
        ->assertJsonPath('success', false);

    // Test with ABK role (non-admin)
    Sanctum::actingAs($this->abk, ['*']);

    $response = $this->postJson(
        "/api/v1/users/{$this->manager->id}/reset-password",
        ['new_password' => 'NewSecurePassword123']
    );

    // Assert 403 Forbidden response
    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('menolak jika password terlalu pendek (422)', function () {
    // Authenticate as admin
    Sanctum::actingAs($this->admin, ['*']);

    // Test with password that is too short (less than 8 characters)
    $response = $this->postJson(
        "/api/v1/users/{$this->abk->id}/reset-password",
        ['new_password' => '123']  // Only 3 characters, needs min 8
    );

    // Assert validation error response
    $response->assertStatus(422)
        ->assertJsonPath('errors.new_password.0', 'Password baru minimal 8 karakter.');
});

it('menolak jika password tidak dikirim (422)', function () {
    // Authenticate as admin
    Sanctum::actingAs($this->admin, ['*']);

    // Send request without new_password field
    $response = $this->postJson(
        "/api/v1/users/{$this->abk->id}/reset-password",
        []  // Missing new_password
    );

    // Assert validation error response
    $response->assertStatus(422)
        ->assertJsonPath('errors.new_password.0', 'Password baru harus diisi.');
});

it('menolak jika user tidak ada (404)', function () {
    // Authenticate as admin
    Sanctum::actingAs($this->admin, ['*']);

    // Use non-existent user ID
    $invalidUserId = '550e8400-e29b-41d4-a716-446655440999';

    $response = $this->postJson(
        "/api/v1/users/{$invalidUserId}/reset-password",
        ['new_password' => 'NewSecurePassword123']
    );

    // Assert 404 Not Found
    $response->assertStatus(404);
});

it('menolak jika tidak ada token autentikasi (401)', function () {
    // Send request WITHOUT authentication token
    $response = $this->postJson(
        "/api/v1/users/{$this->abk->id}/reset-password",
        ['new_password' => 'NewSecurePassword123']
    );

    // Assert 401 Unauthorized
    $response->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated.');
});

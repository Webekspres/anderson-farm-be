<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Siapkan 1 user aktif sebelum setiap test dijalankan
    $this->user = User::factory()->create([
        'username'      => 'manager_hebat',
        'password_hash' => Hash::make('Rahasia123!'),
        'device_id'     => null, // Belum terikat device apapun
        'is_active'     => true,
    ]);
});

it('berhasil login dengan kredensial yang benar dan mengikat device', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username'  => 'manager_hebat',
        'password'  => 'Rahasia123!',
        'device_id' => 'device-baru-001',
    ]);

    // Pastikan response HTTP 200 dan format JSON sesuai
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'token',
            'data' => ['id', 'username', 'name', 'role', 'device_id']
        ]);

    // Pastikan token benar-benar dikembalikan
    expect($response->json('token'))->not->toBeNull();

    // Cek Database: Pastikan device_id sudah tersimpan (Device Binding)
    $this->assertDatabaseHas('users', [
        'username'  => 'manager_hebat',
        'device_id' => 'device-baru-001',
    ]);
});

it('gagal login jika password salah', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username'  => 'manager_hebat',
        'password'  => 'PasswordNgarang99',
        'device_id' => 'device-baru-001',
    ]);

    $response->assertStatus(401)
        ->assertJson(['success' => false]);
});

it('ditolak jika login dari device yang berbeda (Device Binding Strict)', function () {
    // Ubah data user menjadi sudah terikat dengan device lama
    $this->user->update(['device_id' => 'device-lama-999']);

    $response = $this->postJson('/api/v1/auth/login', [
        'username'  => 'manager_hebat',
        'password'  => 'Rahasia123!',
        'device_id' => 'device-penyusup-111',
    ]);

    // Harus ditolak (401 Unauthorized) karena beda HP
    $response->assertStatus(401);
});

it('gagal login jika status user tidak aktif', function () {
    // Nonaktifkan user
    $this->user->update(['is_active' => false]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username'  => 'manager_hebat',
        'password'  => 'Rahasia123!',
        'device_id' => 'device-baru-001',
    ]);

    // Biasanya akun nonaktif mengembalikan 403 Forbidden
    $response->assertStatus(403);
});

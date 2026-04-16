<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Siapkan 1 user
    $this->user = User::factory()->create([
        'username'  => 'manager_farm',
        'role'      => 'manager',
    ]);
});

it('berhasil logout, token terhapus dari database', function () {
    // 1. Buat token sungguhan di database untuk user ini
    $token = $this->user->createToken('auth_token')->plainTextToken;

    // Pastikan token benar-benar masuk ke tabel 'personal_access_tokens'
    $this->assertDatabaseCount('personal_access_tokens', 1);

    // 2. Lakukan request POST ke /logout dengan membawa token di Header
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/v1/auth/logout');

    // 3. Validasi Response
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Berhasil logout.'
        ]);

    // 4. Validasi Database (Pastikan token sudah menguap/terhapus)
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('ditolak (401) jika mencoba logout tanpa membawa token', function () {
    // Lakukan request POST tanpa menyematkan Header Authorization
    $response = $this->postJson('/api/v1/auth/logout');

    // Harus dicegat oleh middleware auth:sanctum
    $response->assertStatus(401);
});

/* * Opsional (Tergantung Kebijakan Anderson Farm): 
 * Jika kebijakanmu adalah "Logout = Melepas Device Binding", 
 * kamu bisa menambahkan test untuk memastikan $user->device_id menjadi null.
 * Namun standarnya, logout HANYA menghapus token, bukan melepas device.
 */

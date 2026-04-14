<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Siapkan 1 user
    $this->user = User::factory()->create([
        'username'  => 'pic_kandang_1',
        'role'      => 'pic',
    ]);
});

it('berhasil mengambil data profil jika membawa token yang valid', function () {
    // Sanctum::actingAs() adalah cara Pest/Laravel mensimulasikan login (tanpa perlu hit endpoint login sungguhan)
    Sanctum::actingAs($this->user, ['*']);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'username' => 'pic_kandang_1',
                'role'     => 'pic',
            ]
        ]);
});

it('ditolak (401) jika mengakses tanpa token', function () {
    // Kita langsung tembak endpoint TANPA Sanctum::actingAs()
    $response = $this->getJson('/api/v1/auth/me');

    // Harus ditendang oleh middleware auth:sanctum
    $response->assertStatus(401);
});

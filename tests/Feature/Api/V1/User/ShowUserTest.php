<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'username' => 'budi_abk1',
        'name'     => 'Budi Santoso',
        'role'     => 'abk',
        'email'    => 'budi@example.com',
        'phone_number' => '08123456789',
        'is_active' => true,
    ]);
});

it('berhasil menampilkan detail user dengan token yang valid (200)', function () {
    Sanctum::actingAs($this->user, ['*']);

    $response = $this->getJson("/api/v1/users/{$this->user->server_id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'username',
                'name',
                'role',
                'email',
                'phone',
                'is_active',
            ]
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Detail user berhasil diambil.')
        ->assertJsonPath('data.id', $this->user->server_id)
        ->assertJsonPath('data.username', 'budi_abk1')
        ->assertJsonPath('data.name', 'Budi Santoso')
        ->assertJsonPath('data.role', 'abk')
        ->assertJsonPath('data.email', 'budi@example.com')
        ->assertJsonPath('data.phone', '08123456789')
        ->assertJsonPath('data.is_active', true);
});

it('ditolak jika mengakses tanpa token (401)', function () {
    $response = $this->getJson("/api/v1/users/{$this->user->server_id}");

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated.');
});

it('mengembalikan 404 jika user tidak ditemukan', function () {
    Sanctum::actingAs($this->user, ['*']);

    $invalidUserId = '550e8400-e29b-41d4-a716-446655440999';
    $response = $this->getJson("/api/v1/users/{$invalidUserId}");

    $response->assertStatus(404);
});

it('mengikuti format response standar (success, message, data)', function () {
    Sanctum::actingAs($this->user, ['*']);

    $response = $this->getJson("/api/v1/users/{$this->user->server_id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

    // Verifikasi struktur dan tipe data
    expect($response->json('success'))->toBeTrue()
        ->and($response->json('message'))->toBeString()
        ->and($response->json('data'))->toBeArray();
});

it('tidak mengekspos data sensitif seperti password', function () {
    Sanctum::actingAs($this->user, ['*']);

    $response = $this->getJson("/api/v1/users/{$this->user->server_id}");

    $responseJson = json_encode($response->json('data'));

    expect($responseJson)->not->toContain('password')
        ->and($responseJson)->not->toContain('password_hash');
});

it('memetakan field database ke field API dengan benar', function () {
    Sanctum::actingAs($this->user, ['*']);

    $response = $this->getJson("/api/v1/users/{$this->user->server_id}");

    $data = $response->json('data');

    // phone_number di DB → phone di API
    expect($data)->toHaveKey('phone')
        ->and($data)->not->toHaveKey('phone_number')
        ->and($data['phone'])->toBe($this->user->phone_number);

    // server_id di DB → id di API
    expect($data)->toHaveKey('id')
        ->and($data)->not->toHaveKey('server_id')
        ->and($data['id'])->toBe($this->user->server_id);
});

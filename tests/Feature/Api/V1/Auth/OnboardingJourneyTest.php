<?php

declare(strict_types=1);

use App\Models\Coop;
use App\Models\EducationArticle;
use App\Models\EquipmentType;
use App\Models\Farm;
use App\Models\OvkItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->farm = Farm::factory()->create();
    $this->coop = Coop::factory()->create(['farm_id' => $this->farm->id]);

    OvkItem::factory()->count(2)->create();
    EquipmentType::factory()->count(2)->create();
    EducationArticle::factory()->count(2)->create();
});

it('successfully completes the new worker onboarding journey from account creation to initial mobile sync', function (): void {
    $workerUsername = '081299887766';
    $workerPassword = 'password123';

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 1 — Admin creates worker & assigns to coop (online / web)
    // ─────────────────────────────────────────────────────────────────────────
    Sanctum::actingAs($this->admin, ['*']);

    $createWorkerResponse = $this->postJson('/api/v1/users', [
        'username' => $workerUsername,
        'name' => 'Budi Anak Kandang',
        'phone' => $workerUsername,
        'password' => $workerPassword,
        'role' => 'abk',
    ]);

    $createWorkerResponse->assertCreated()
        ->assertJsonPath('success', true);

    // Respons memuat `data.id` sebagai server_id; assignment membutuhkan UUID primary key `users.id`.
    $capturedUserIdFromApi = $createWorkerResponse->json('data.id');
    expect($capturedUserIdFromApi)->not->toBeNull();

    $newWorkerUuid = User::query()->where('server_id', $capturedUserIdFromApi)->value('id');
    expect($newWorkerUuid)->not->toBeNull()->toBeString();

    $assignmentResponse = $this->postJson("/api/v1/coops/{$this->coop->id}/user-assignments", [
        'assignments' => [
            [
                'user_id' => $newWorkerUuid,
                'role_in_coop' => 'abk',
                'assigned_at' => '2026-05-05T00:00:00Z',
            ],
        ],
    ]);

    $assignmentResponse->assertOk()
        ->assertJsonPath('success', true);

    // Guard Sanctum berbasis RequestGuard menyimpan user yang sudah ter-resolve di satu instance tes HTTP.
    // Phase 2–4 memakai Bearer pekerja; reset cache agar token baru yang dipakai.
    Auth::guard('sanctum')->forgetUser();
    Auth::guard('web')->forgetUser();

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 2 — Worker first login & device binding (guest; tanpa actingAs)
    // ─────────────────────────────────────────────────────────────────────────
    // LoginRequest memvalidasi `username` (bukan `phone`); nilai sama dengan nomor untuk skenario ini.
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'username' => $workerUsername,
        'password' => $workerPassword,
        'device_id' => 'DEV-SAMSUNG-A54',
        'device_name' => 'Samsung Galaxy A54',
    ]);

    $loginResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'token',
            'data' => ['id', 'username', 'name', 'role', 'device_id'],
        ]);

    // API mengembalikan `token` sebagai access token aplikasi mobile.
    $accessToken = $loginResponse->json('token');
    expect($accessToken)->not->toBeNull()->not->toBeEmpty();

    $workerAuthHeader = ['Authorization' => 'Bearer '.$accessToken];

    $this->assertDatabaseHas('users', [
        'id' => $newWorkerUuid,
        'phone_number' => $workerUsername,
        'device_id' => 'DEV-SAMSUNG-A54',
    ]);

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 3 — FCM token (Bearer hanya per permintaan; hindari withHeader default)
    // ─────────────────────────────────────────────────────────────────────────
    $this->postJson('/api/v1/auth/fcm-token', [
        'fcm_token' => 'FCM-TOKEN-MOCK-999',
    ], $workerAuthHeader)
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'message' => 'FCM token berhasil diperbarui.',
            'data' => null,
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $newWorkerUuid,
        'fcm_token' => 'FCM-TOKEN-MOCK-999',
    ]);

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 4 — Initial pull sync untuk basis data offline
    // ─────────────────────────────────────────────────────────────────────────
    $this->getJson('/api/v1/sync/master-data', $workerAuthHeader)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.ovk_items')
        ->assertJsonCount(2, 'data.equipment_types');

    $this->getJson('/api/v1/sync/education', $workerAuthHeader)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.education_articles');
});

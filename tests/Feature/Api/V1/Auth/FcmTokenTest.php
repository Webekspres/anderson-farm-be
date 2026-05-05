<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/v1/auth/fcm-token', function () {

    // ── Test 1: Happy Path ─────────────────────────────────────

    it('updates the fcm_token for the authenticated user', function () {
        $user = User::factory()->create();
        $token = 'fCmT0k3n_ExAmPlE_123456789abcdefghijklmnopqrstuvwxyz';

        $response = $this->actingAs($user)->postJson('/api/v1/auth/fcm-token', [
            'fcm_token' => $token,
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'message' => 'FCM token berhasil diperbarui.',
            'data' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fcm_token' => $token,
        ]);
    });

    // ── Test 2: Validation ────────────────────────────────────

    it('returns 422 when fcm_token is missing from the payload', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/auth/fcm-token', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['fcm_token']);
    });

    // ── Test 3: Security — unauthenticated guest ───────────────

    it('returns 401 when the request has no authentication token', function () {
        $response = $this->postJson('/api/v1/auth/fcm-token', [
            'fcm_token' => 'some-token',
        ]);

        $response->assertUnauthorized();
    });
});

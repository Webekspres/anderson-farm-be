<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('POST /api/v1/auth/forgot-password', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create([
            'username' => 'worker_one',
            'password_hash' => Hash::make('oldpassword123'),
            'is_active' => true,
        ]);
    });

    it('mengirim otp untuk user yang valid', function (): void {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'username' => 'worker_one',
            'via' => 'email',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        expect(Cache::get("password_reset_otp:{$this->user->id}"))->not->toBeNull();
    });

    it('mengembalikan 404 jika user tidak ditemukan', function (): void {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'username' => 'tidak_ada',
            'via' => 'email',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    });

    it('bisa reset password dengan otp setelah forgot-password', function (): void {
        $forgot = $this->postJson('/api/v1/auth/forgot-password', [
            'username' => 'worker_one',
            'via' => 'wa',
        ]);

        $forgot->assertOk();
        $otp = Cache::get("password_reset_otp:{$this->user->id}");

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'otp',
            'username' => 'worker_one',
            'otp' => $otp,
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        expect(Hash::check('newpassword456', $this->user->fresh()->password_hash))->toBeTrue();
        expect(Cache::get("password_reset_otp:{$this->user->id}"))->toBeNull();
    });
});

<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('POST /api/v1/auth/reset-password — method old_password', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create([
            'username' => 'worker_one',
            'password_hash' => Hash::make('oldpassword123'),
            'is_active' => true,
        ]);
    });

    it('berhasil ubah password sendiri', function (): void {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'old_password',
            'username' => 'worker_one',
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password berhasil direset. Silakan login dengan password baru.',
                'data' => null,
            ]);

        expect(Hash::check('newpassword456', $this->user->fresh()->password_hash))->toBeTrue();
    });

    it('gagal karena current_password salah', function (): void {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'old_password',
            'username' => 'worker_one',
            'current_password' => 'salahpassword',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Username/email atau password salah.',
                'data' => null,
            ]);
    });

    it('gagal karena user tidak ditemukan', function (): void {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'old_password',
            'username' => 'tidak_ada',
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Username/email atau password salah.');
    });

    it('gagal karena akun tidak aktif', function (): void {
        $this->user->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'old_password',
            'username' => 'worker_one',
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Akun Anda dinonaktifkan. Hubungi Admin.');
    });

    it('gagal karena user soft-deleted', function (): void {
        $this->user->delete();

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'old_password',
            'username' => 'worker_one',
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertUnauthorized();
    });

    it('gagal karena konfirmasi password tidak cocok', function (): void {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'old_password',
            'username' => 'worker_one',
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'beda',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password']);
    });

    it('revoke semua token setelah reset berhasil', function (): void {
        $this->user->createToken('auth_token');

        expect($this->user->tokens()->count())->toBe(1);

        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'old_password',
            'username' => 'worker_one',
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ])->assertOk();

        expect($this->user->fresh()->tokens()->count())->toBe(0);
    });
});

describe('POST /api/v1/auth/reset-password — method admin_reset', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_super',
        ]);

        $this->abk = User::factory()->create([
            'role' => 'abk',
            'username' => 'budi_abk',
            'password_hash' => Hash::make('AbkPassword123'),
        ]);

        $this->manager = User::factory()->create([
            'role' => 'manager',
            'username' => 'manager_budi',
        ]);
    });

    it('berhasil mereset password user oleh admin', function (): void {
        Sanctum::actingAs($this->admin, ['*']);

        $originalPasswordHash = $this->abk->password_hash;
        $newPassword = 'NewSecurePassword123';

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'admin_reset',
            'user_id' => $this->abk->id,
            'new_password' => $newPassword,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);

        $this->abk->refresh();

        expect($this->abk->password_hash)->not->toBe($originalPasswordHash)
            ->and(Hash::check($newPassword, $this->abk->password_hash))->toBeTrue();
    });

    it('menolak reset password jika bukan admin', function (): void {
        Sanctum::actingAs($this->manager, ['*']);

        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'admin_reset',
            'user_id' => $this->abk->id,
            'new_password' => 'NewSecurePassword123',
        ])->assertForbidden()
            ->assertJsonPath('success', false);
    });

    it('menolak jika password terlalu pendek', function (): void {
        Sanctum::actingAs($this->admin, ['*']);

        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'admin_reset',
            'user_id' => $this->abk->id,
            'new_password' => '123',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.new_password.0', 'Password baru minimal 8 karakter.');
    });

    it('menolak jika password tidak dikirim', function (): void {
        Sanctum::actingAs($this->admin, ['*']);

        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'admin_reset',
            'user_id' => $this->abk->id,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.new_password.0', 'Password baru harus diisi.');
    });

    it('menolak jika user target tidak ada', function (): void {
        Sanctum::actingAs($this->admin, ['*']);

        $invalidUserId = '550e8400-e29b-41d4-a716-446655440999';

        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'admin_reset',
            'user_id' => $invalidUserId,
            'new_password' => 'NewSecurePassword123',
        ])->assertUnauthorized();
    });

    it('menolak jika target soft-deleted', function (): void {
        Sanctum::actingAs($this->admin, ['*']);

        $this->abk->delete();

        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'admin_reset',
            'user_id' => $this->abk->id,
            'new_password' => 'NewSecurePassword123',
        ])->assertUnauthorized();
    });

    it('menolak jika tidak ada token autentikasi', function (): void {
        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'admin_reset',
            'user_id' => $this->abk->id,
            'new_password' => 'NewSecurePassword123',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    });
});

describe('POST /api/v1/auth/reset-password — method otp', function (): void {
    it('memvalidasi field otp yang wajib', function (): void {
        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'otp',
        ])->assertUnprocessable();
    });

    it('menolak otp yang salah', function (): void {
        $user = User::factory()->create([
            'username' => 'otp_user',
            'password_hash' => Hash::make('oldpassword123'),
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'method' => 'otp',
            'username' => $user->username,
            'otp' => '000000',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'OTP tidak valid atau sudah kedaluwarsa.');
    });
});

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserPasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserPasswordService $passwordService,
    ) {}

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        // 1. Cari user berdasarkan username atau email
        $user = User::where(function ($query) use ($validated) {
            $query->where('username', $validated['username'])
                ->orWhere('email', $validated['username']);
        })->first();

        // 2. Verifikasi keberadaan user dan kecocokan password_hash
        if (! $user || ! Hash::check($validated['password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Username/email atau password salah.',
                'errors' => (object) [], // Object kosong sesuai kontrak OpenAPI
            ], 401);
        }

        // 3. Pengecekan status aktif user
        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi Admin.',
                'errors' => (object) [],
            ], 403);
        }

        // 4. Logika Device Binding (Kunci ke 1 HP)
        if ($user->device_id !== null && $user->device_id !== $validated['device_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini sudah terikat dengan perangkat lain. Hubungi Admin untuk reset device.',
                'errors' => (object) [],
            ], 401);
        }

        // 5. Jika device_id masih kosong (Login pertama kali), ikat sekarang!
        if ($user->device_id === null) {
            $user->update([
                'device_id' => $validated['device_id'],
                'device_bound_at' => now(),
            ]);
        }

        // 6. Generate Token Sanctum
        // (Opsional) Hapus token lama agar user ter-logout otomatis dari sesi sebelumnya
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // 7. Kembalikan Response Sukses
        return response()->json([
            'success' => true,
            'token' => $token,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'role' => $user->role,
                'device_id' => $user->device_id,
            ],
        ], 200);
    }

    /**
     * Ambil data user yang sedang login (cek sesi aktif)
     *
     * @return JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // Jika ingin menambah relasi/hak akses lain, eager load di sini
        return (new UserResource($user))->additional([
            'success' => true,
            'message' => 'Sesi aktif. Data user berhasil diambil.',
        ]);
    }

    /**
     * Logout user yang sedang login (revoke token)
     *
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Hapus semua token milik user (revoke all tokens)
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout.',
            'errors' => [],
        ], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->findUserByIdentifier($validated['username']);

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
                'errors' => (object) [],
            ], 404);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi Admin.',
                'errors' => (object) [],
            ], 403);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->passwordResetCacheKey($user->id), $otp, now()->addMinutes(15));

        $channel = $validated['via'] === 'wa' ? 'WhatsApp' : 'email';

        // Delivery channel (email/WA gateway) belum dikonfigurasi di environment ini.
        // OTP disimpan di cache untuk dipakai method=otp pada reset-password.
        return response()->json([
            'success' => true,
            'message' => "OTP telah dikirim ke {$channel} Anda.",
            'data' => config('app.debug')
                ? ['otp' => $otp, 'expires_in_minutes' => 15]
                : (object) [],
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $method = $validated['method'];

        if ($method === 'otp') {
            return $this->resetPasswordWithOtp($validated);
        }

        if ($method === 'admin_reset') {
            return $this->resetPasswordByAdmin($request, $validated);
        }

        return $this->resetPasswordWithOldPassword($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resetPasswordWithOtp(array $validated): JsonResponse
    {
        $user = $this->findUserByIdentifier($validated['username']);

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi Admin.',
                'data' => null,
            ], 403);
        }

        $cachedOtp = Cache::get($this->passwordResetCacheKey($user->id));

        if ($cachedOtp === null || ! hash_equals((string) $cachedOtp, (string) $validated['otp'])) {
            return response()->json([
                'success' => false,
                'message' => 'OTP tidak valid atau sudah kedaluwarsa.',
                'data' => null,
            ], 422);
        }

        $this->passwordService->forceUpdatePassword($user, $validated['new_password']);
        $user->tokens()->delete();
        Cache::forget($this->passwordResetCacheKey($user->id));

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            'data' => null,
        ], 200);
    }

    private function passwordResetCacheKey(string $userId): string
    {
        return "password_reset_otp:{$userId}";
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resetPasswordWithOldPassword(array $validated): JsonResponse
    {
        $user = $this->findUserByIdentifier($validated['username']);

        if (! $user || ! Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Username/email atau password salah.',
                'data' => null,
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi Admin.',
                'data' => null,
            ], 403);
        }

        $this->passwordService->forceUpdatePassword($user, $validated['new_password']);
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            'data' => null,
        ], 200);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resetPasswordByAdmin(Request $request, array $validated): JsonResponse
    {
        $authUser = $this->resolveSanctumUser($request);

        if ($authUser === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        if ($authUser->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only administrators can reset user passwords.',
                'data' => null,
            ], 403);
        }

        $target = User::query()->find($validated['user_id']);

        if ($target === null) {
            return response()->json([
                'success' => false,
                'message' => 'Username/email atau password salah.',
                'data' => null,
            ], 401);
        }

        if (! $target->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi Admin.',
                'data' => null,
            ], 403);
        }

        $this->passwordService->forceUpdatePassword($target, $validated['new_password']);
        $target->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => "Password untuk user '{$target->username}' berhasil direset.",
            'data' => null,
        ], 200);
    }

    private function findUserByIdentifier(string $identifier): ?User
    {
        return User::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('username', $identifier)
                    ->orWhere('email', $identifier);
            })
            ->first();
    }

    private function resolveSanctumUser(Request $request): ?User
    {
        $user = $request->user('sanctum');

        if ($user instanceof User) {
            return $user;
        }

        $bearerToken = $request->bearerToken();

        if ($bearerToken === null) {
            return null;
        }

        $token = PersonalAccessToken::findToken($bearerToken);

        $tokenable = $token?->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }
}

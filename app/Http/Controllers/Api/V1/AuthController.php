<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        // 1. Cari user berdasarkan username
        $user = User::where('username', $validated['username'])->first();

        // 2. Verifikasi keberadaan user dan kecocokan password_hash
        if (!$user || !Hash::check($validated['password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah.',
                'errors'  => (object)[] // Object kosong sesuai kontrak OpenAPI
            ], 401);
        }

        // 3. Pengecekan status aktif user
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi Admin.',
                'errors'  => (object)[]
            ], 403);
        }

        // 4. Logika Device Binding (Kunci ke 1 HP)
        if ($user->device_id !== null && $user->device_id !== $validated['device_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini sudah terikat dengan perangkat lain. Hubungi Admin untuk reset device.',
                'errors'  => (object)[]
            ], 401);
        }

        // 5. Jika device_id masih kosong (Login pertama kali), ikat sekarang!
        if ($user->device_id === null) {
            $user->update([
                'device_id'       => $validated['device_id'],
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
            'token'   => $token,
            'data'    => [
                'id'        => $user->id,
                'username'  => $user->username,
                'name'      => $user->name,
                'role'      => $user->role,
                'device_id' => $user->device_id,
            ]
        ], 200);
    }
}

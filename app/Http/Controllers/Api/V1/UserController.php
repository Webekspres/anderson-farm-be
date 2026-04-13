<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\IndexUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(IndexUserRequest $request)
    {
        // Ambil data query yang sudah divalidasi
        $validated = $request->validated();

        $query = User::query();

        // Fitur Pencarian (Search)
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhere('role', 'LIKE', "%{$search}%");
            });
        }

        // Tentukan jumlah data per halaman (default 15)
        $perPage = $validated['per_page'] ?? 15;

        // Eksekusi Paginasi
        $users = $query->latest('server_id')->paginate($perPage);

        // Kembalikan Response terstruktur menggunakan Resource Collection
        return UserResource::collection($users)->additional([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil.'
        ]);
    }
}

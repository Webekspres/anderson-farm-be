<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\IndexUserRequest;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    public function index(IndexUserRequest $request)
    {
        $validated = $request->validated();

        $query = User::query();

        // Filter: search (name, username, email, phone_number)
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone_number', 'LIKE', "%{$search}%");
            });
        }

        // Filter: role
        if (!empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        // Filter: is_active
        if (!is_null($validated['is_active'] ?? null)) {
            $query->where('is_active', $validated['is_active']);
        }

        // Order by server_id desc (wajib untuk cursor pagination)
        $query->orderBy('server_id', 'desc');

        // Paginasi Dinamis
        $isCursor = $request->has('cursor') || $request->has('limit');
        $perPage  = $isCursor
            ? ($validated['limit'] ?? 10)
            : ($validated['per_page'] ?? 10);

        $paginator = $isCursor
            ? $query->cursorPaginate($perPage)
            : $query->paginate($perPage);

        // Meta flat
        $meta = $isCursor ? [
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'prev_cursor' => $paginator->previousCursor()?->encode(),
            'has_next'    => $paginator->hasMorePages(),
            'has_prev'    => $paginator->previousCursor() !== null,
        ] : [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Daftar user berhasil diambil.',
            'data'    => array_merge([
                'users' => UserResource::collection($paginator)
            ], $meta)
        ], 200);
    }

    public function show($id)
    {
        // Cari user berdasarkan server_id (UUID)
        $user = User::where('server_id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Detail user berhasil diambil.',
            'data'    => new UserResource($user),
        ], 200);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'username'      => $validated['username'],
            'password_hash' => Hash::make($validated['password']),
            'name'          => $validated['name'],
            'email'         => $validated['email'] ?? null,
            'phone_number'  => $validated['phone'] ?? null,
            'role'          => $validated['role'],
            'is_active'     => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'data'    => new UserResource($user),
        ], 201);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::where('server_id', $id)->firstOrFail();
        $validated = $request->validated();
        $updateData = [];

        if (array_key_exists('username', $validated)) {
            $updateData['username'] = $validated['username'];
        }
        if (array_key_exists('password', $validated)) {
            $updateData['password_hash'] = Hash::make($validated['password']);
        }
        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('email', $validated)) {
            $updateData['email'] = $validated['email'];
        }
        if (array_key_exists('phone', $validated)) {
            $updateData['phone_number'] = $validated['phone'];
        }
        if (array_key_exists('role', $validated)) {
            $updateData['role'] = $validated['role'];
        }
        if (array_key_exists('is_active', $validated)) {
            $updateData['is_active'] = $validated['is_active'];
        }
        if (array_key_exists('device_id', $validated)) {
            $updateData['device_id'] = $validated['device_id'];
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diperbarui.',
            'data'    => new UserResource($user->fresh()),
        ], 200);
    }

    public function destroy($server_id)
    {
        $user = \App\Models\User::where('server_id', $server_id)->firstOrFail();
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil dihapus.',
            'data'    => null,
        ], 200);
    }
}

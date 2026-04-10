<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(StoreUserRequest $request, CreateNewUserAction $creator): JsonResponse
    {
        // 1. Data sudah PASTI valid dan user PASTI punya akses berkat StoreUserRequest
        $validatedData = $request->validated();

        // 2. Lempar data ke Action Class untuk dikerjakan
        $user = $creator->execute($validatedData);

        // 3. Kembalikan response JSON yang sudah diformat oleh UserResource
        return response()->json([
            'message' => 'User berhasil dibuat',
            'data'    => new UserResource($user)
        ], 201);
    }
}

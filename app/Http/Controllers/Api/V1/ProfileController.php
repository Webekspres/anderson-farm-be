<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini tidak cocok.',
                'data'    => null,
            ], 422);
        }
        $user->password_hash = Hash::make($request->new_password);
        $user->save();
        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
            'data'    => null,
        ], 200);
    }
}

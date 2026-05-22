<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminResetPasswordRequest;
use App\Models\User;
use App\Services\UserPasswordService;
use Illuminate\Http\JsonResponse;

class UserPasswordController extends Controller
{
    /**
     * Inject UserPasswordService for password operations
     */
    protected UserPasswordService $passwordService;

    public function __construct(UserPasswordService $passwordService)
    {
        $this->passwordService = $passwordService;
    }

    /**
     * Admin-forced password reset for another user.
     *
     * This endpoint allows only admin users to reset passwords for any user.
     * The authenticated user must have the 'admin' role.
     *
     * @param  string  $id  The id (UUID) of the user whose password is being reset
     * @param  AdminResetPasswordRequest  $request  Validated request with new_password
     * @return JsonResponse Standard response with success message
     */
    public function resetByAdmin($id, AdminResetPasswordRequest $request): JsonResponse
    {
        // Get the authenticated user from the request
        $authenticatedUser = auth()->user();

        // Check if the authenticated user is an admin
        if ($authenticatedUser->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only administrators can reset user passwords.',
                'data' => null,
            ], 403);
        }

        // Fetch the target user by id
        $user = User::where('id', $id)->firstOrFail();

        // Extract and update password using the service
        $newPassword = $request->validated('new_password');
        $this->passwordService->forceUpdatePassword($user, $newPassword);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => "Password untuk user '{$user->username}' berhasil direset.",
            'data' => null,
        ], 200);
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserPasswordService
{
    /**
     * Force update a user's password.
     * 
     * This method handles the core password hashing and persistence logic.
     * It is designed to be reused across multiple password reset scenarios:
     * - Admin-forced reset (direct update)
     * - User OTP-based reset (future)
     * - Other password reset flows (future)
     *
     * @param User $user The user whose password to update
     * @param string $newUnhashedPassword The plain-text new password (will be hashed)
     * @return void
     */
    public function forceUpdatePassword(User $user, string $newUnhashedPassword): void
    {
        // Hash the new password and update the user
        $user->update([
            'password_hash' => Hash::make($newUnhashedPassword),
        ]);
    }
}

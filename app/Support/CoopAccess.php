<?php

namespace App\Support;

use App\Models\CoopUserAssignment;
use App\Models\User;

class CoopAccess
{
    /**
     * Roles that may access any coop without an assignment (aligned with master sync).
     *
     * @var list<string>
     */
    public const FULL_ACCESS_ROLES = ['admin', 'manager', 'finance'];

    public static function receivesFullAccess(User $user): bool
    {
        return in_array((string) $user->role, self::FULL_ACCESS_ROLES, true);
    }

    public static function canAccessCoop(User $user, ?string $coopId): bool
    {
        if ($coopId === null || $coopId === '') {
            return false;
        }

        if (self::receivesFullAccess($user)) {
            return true;
        }

        return CoopUserAssignment::query()
            ->where('user_id', $user->id)
            ->where('coop_id', $coopId)
            ->exists();
    }
}

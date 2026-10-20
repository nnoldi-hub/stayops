<?php

namespace App\Support;

use App\Models\User;

class RoleRedirector
{
    /**
     * Returns the post-login path for a user based on their role priority:
     * SuperAdmin → /admin, Host → /host, Provider → /provider, default → /dashboard.
     */
    public static function pathFor(User $user): string
    {
        return match (true) {
            $user->hasRole('SuperAdmin') => '/admin',
            $user->hasRole('Host') => '/host',
            $user->hasRole('Provider') => '/provider',
            default => '/dashboard',
        };
    }
}

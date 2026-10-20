<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces users with elevated privileges (SuperAdmin, Host) to enable
 * two-factor authentication before accessing protected resources.
 *
 * If 2FA is not confirmed, the user is redirected to /two-factor-setup.
 */
class EnsureTwoFactorAuthenticated
{
    /**
     * Roles required to have 2FA enabled.
     *
     * @var array<int, string>
     */
    public const REQUIRED_FOR_ROLES = ['SuperAdmin', 'Host'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // While a SuperAdmin is impersonating, the 2FA requirement of the target
        // account must not force the admin through the target's 2FA setup flow.
        if (session()->has(ImpersonationService::SESSION_KEY)) {
            return $next($request);
        }

        $needs2fa = collect(self::REQUIRED_FOR_ROLES)
            ->contains(fn (string $role) => $user->hasRole($role));

        if ($needs2fa && ! $user->hasEnabledTwoFactorAuthentication()) {
            // Avoid redirect loop when the user is already on the setup page.
            if (! $request->is('two-factor-setup') && ! $request->is('two-factor-setup/*')) {
                return redirect('/two-factor-setup');
            }
        }

        return $next($request);
    }
}

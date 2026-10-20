<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blochează accesul conturilor suspendate: utilizatorul este deconectat
 * și redirectat către autentificare cu un mesaj explicit.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->isSuspended()
            && ! session()->has(ImpersonationService::SESSION_KEY)) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $message = 'Contul tău a fost suspendat.';

            if (filled($user->suspended_reason)) {
                $message .= ' Motiv: '.$user->suspended_reason;
            }

            return redirect()->route('login')->withErrors([
                'email' => $message,
            ]);
        }

        return $next($request);
    }
}

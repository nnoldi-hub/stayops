<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces hosts to hold an active platform subscription before they can
 * use the host area. Unsubscribed hosts are redirected to the billing page.
 */
class EnsureHostSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->hasActiveHostSubscription()) {
            return $next($request);
        }

        return redirect()
            ->route('host.subscription')
            ->with('status', 'Ai nevoie de un abonament activ pentru a accesa aceasta sectiune.');
    }
}

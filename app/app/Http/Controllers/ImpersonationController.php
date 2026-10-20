<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use App\Support\RoleRedirector;
use Illuminate\Http\RedirectResponse;

class ImpersonationController
{
    public function start(User $user, ImpersonationService $service): RedirectResponse
    {
        $service->start($user, request()->input('reason', 'Suport tehnic'));

        return redirect()->route('impersonation.landing');
    }

    public function stop(ImpersonationService $service): RedirectResponse
    {
        $admin = $service->stop();

        return redirect()->to(RoleRedirector::pathFor($admin));
    }

    public function landing(ImpersonationService $service): RedirectResponse
    {
        abort_unless($service->state() !== null, 403);

        /** @var User $user */
        $user = auth()->user();

        return redirect()->to(RoleRedirector::pathFor($user));
    }
}

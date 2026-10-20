<?php

namespace App\Services;

use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public const SESSION_KEY = 'impersonation';

    /**
     * Switch the current session to the target user and log the impersonation.
     */
    public function start(User $target, string $reason): ImpersonationLog
    {
        /** @var User|null $admin */
        $admin = Auth::user();

        abort_unless($admin instanceof User && $admin->can('users.impersonate'), 403);
        abort_if($admin->is($target), 403);
        abort_if($target->hasRole('SuperAdmin'), 403);
        abort_if(session()->has(self::SESSION_KEY), 403);

        $log = ImpersonationLog::create([
            'admin_id' => $admin->id,
            'target_id' => $target->id,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'started_at' => now(),
        ]);

        Auth::login($target);
        session()->regenerate();
        session()->put(self::SESSION_KEY, [
            'admin_id' => $admin->id,
            'log_id' => $log->id,
        ]);

        return $log;
    }

    /**
     * End the impersonation and restore the original admin session.
     */
    public function stop(): User
    {
        $state = session(self::SESSION_KEY);

        abort_unless(is_array($state) && isset($state['admin_id'], $state['log_id']), 403);

        /** @var User|null $admin */
        $admin = User::find($state['admin_id']);

        abort_unless($admin instanceof User, 403);

        ImpersonationLog::query()->whereKey($state['log_id'])->update(['ended_at' => now()]);

        Auth::login($admin);
        session()->regenerate();
        session()->forget(self::SESSION_KEY);

        return $admin;
    }

    /**
     * @return array{admin_id: int, log_id: int}|null
     */
    public function state(): ?array
    {
        $state = session(self::SESSION_KEY);

        return is_array($state) && isset($state['admin_id'], $state['log_id']) ? $state : null;
    }
}

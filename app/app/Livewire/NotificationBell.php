<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    /**
     * Deschide o notificare: marchează citită și redirecționează (URL intern).
     */
    public function markRead(string $notificationId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $notification = $user->notifications()->whereKey($notificationId)->first();

        if (! $notification) {
            return;
        }

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if (! is_string($url) || $url === '' || str_starts_with($url, '//')) {
            $url = '/dashboard';
        } elseif (! str_starts_with($url, '/')) {
            // Permite doar URL-uri absolute de aceeași origine.
            $origin = rtrim(url('/'), '/');

            if ($origin === '' || ! str_starts_with($url, $origin.'/')) {
                $url = '/dashboard';
            }
        }

        $this->redirect($url);
    }

    public function markAllRead(): void
    {
        Auth::user()?->unreadNotifications()->get()->markAsRead();

        $this->unreadCount = 0;
    }

    public function render(): View
    {
        $user = Auth::user();

        $notifications = $user
            ? $user->notifications()->latest()->take(8)->get()
            : collect();

        $this->unreadCount = $user ? $user->unreadNotifications()->count() : 0;

        return view('livewire.notification-bell', [
            'notifications' => $notifications,
        ]);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Cashier\Subscription;

class LiveActivityWidget extends Widget
{
    protected static ?int $sort = -55;

    protected int|string|array $columnSpan = 6;

    protected ?string $pollingInterval = '60s';

    protected string $view = 'filament.widgets.live-activity';

    /**
     * @return array{events: list<array{label: string, subject: string, icon: string, at: Carbon}>}
     */
    protected function getViewData(): array
    {
        $events = [];

        $hosts = User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'Host'))
            ->latest('created_at')
            ->limit(4)
            ->get(['id', 'name', 'created_at']);

        foreach ($hosts as $host) {
            $events[] = [
                'label' => 'Gazdă nouă înregistrată',
                'subject' => $host->name,
                'icon' => 'heroicon-o-home-modern',
                'at' => $host->created_at,
            ];
        }

        $bookings = Booking::query()
            ->latest('created_at')
            ->limit(4)
            ->get(['id', 'reference', 'created_at']);

        foreach ($bookings as $booking) {
            $events[] = [
                'label' => 'Rezervare creată',
                'subject' => '#'.$booking->reference,
                'icon' => 'heroicon-o-calendar-days',
                'at' => $booking->created_at,
            ];
        }

        $tasks = Task::query()
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit(4)
            ->get(['id', 'title', 'completed_at']);

        foreach ($tasks as $task) {
            $events[] = [
                'label' => 'Task finalizat',
                'subject' => $task->title,
                'icon' => 'heroicon-o-check-badge',
                'at' => $task->completed_at,
            ];
        }

        $subscriptions = $this->recentSubscriptions();

        foreach ($subscriptions as $subscription) {
            $events[] = [
                'label' => 'Abonament activat',
                'subject' => (string) ($subscription->user_name ?? 'Gazdă'),
                'icon' => 'heroicon-o-credit-card',
                'at' => $subscription->created_at,
            ];
        }

        usort($events, fn (array $a, array $b): int => $b['at']->getTimestamp() <=> $a['at']->getTimestamp());

        return ['events' => array_slice($events, 0, 8)];
    }

    /**
     * @return Collection<int, Subscription>
     */
    private function recentSubscriptions(): Collection
    {
        return Subscription::query()
            ->where('type', 'host')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->select('subscriptions.*', 'users.name as user_name')
            ->orderByDesc('subscriptions.created_at')
            ->limit(4)
            ->get();
    }
}

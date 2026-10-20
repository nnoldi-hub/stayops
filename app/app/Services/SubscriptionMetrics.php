<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Cashier\Subscription;

/**
 * MRR / ARR / churn metrics for the host platform subscription (type "host").
 */
class SubscriptionMetrics
{
    /**
     * Subscriptions that are currently paying: active/trialing, or cancelled
     * but still inside the paid-for grace period.
     *
     * @return Builder<Subscription>
     */
    public function activeQuery(): Builder
    {
        return Subscription::query()
            ->where('type', 'host')
            ->where(fn (Builder $query): Builder => $query
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->orWhere('ends_at', '>', now()));
    }

    public function activeCount(): int
    {
        return $this->activeQuery()->count();
    }

    /**
     * Monthly recurring revenue, yearly plans normalised to a monthly value.
     */
    public function mrr(): float
    {
        $plans = Plan::query()
            ->whereNotNull('stripe_price_id')
            ->get()
            ->keyBy('stripe_price_id');

        return (float) $this->activeQuery()
            ->pluck('stripe_price')
            ->reduce(function (float $carry, mixed $price) use ($plans): float {
                $plan = $plans->get($price);

                if (! $plan instanceof Plan) {
                    return $carry;
                }

                $amount = (float) $plan->price;

                return $carry + ($plan->interval === 'year' ? $amount / 12 : $amount);
            }, 0.0);
    }

    /**
     * Annual recurring revenue projection.
     */
    public function arr(): float
    {
        return $this->mrr() * 12;
    }

    /**
     * Average revenue per paying account (ARPU), monthly.
     */
    public function arpu(): float
    {
        $active = $this->activeCount();

        return $active > 0 ? round($this->mrr() / $active, 2) : 0.0;
    }

    /**
     * @return array{canceled: int, rate: float}
     */
    public function churn(int $days = 30): array
    {
        $canceled = Subscription::query()
            ->where('type', 'host')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now()->subDays($days), now()])
            ->count();

        $base = $this->activeCount() + $canceled;
        $rate = $base > 0 ? round($canceled / $base * 100, 1) : 0.0;

        return ['canceled' => $canceled, 'rate' => $rate];
    }

    /**
     * Historical MRR for the last N months, one value per month (oldest first).
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    public function mrrSeries(int $months = 12): array
    {
        $plans = Plan::query()
            ->whereNotNull('stripe_price_id')
            ->get()
            ->keyBy('stripe_price_id');

        $subscriptions = Subscription::query()
            ->where('type', 'host')
            ->get(['created_at', 'ends_at', 'stripe_price']);

        $labels = [];
        $values = [];

        foreach (range($months - 1, 0) as $offset) {
            $monthStart = now()->subMonthsNoOverflow($offset)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $labels[] = $monthStart->translatedFormat('M');
            $values[] = (float) round($subscriptions
                ->filter(fn (Subscription $subscription): bool => $subscription->created_at <= $monthEnd)
                ->filter(fn (Subscription $subscription): bool => $subscription->ends_at === null
                    || $subscription->ends_at > $monthEnd)
                ->sum(function (Subscription $subscription) use ($plans): float {
                    $plan = $plans->get($subscription->stripe_price);

                    if (! $plan instanceof Plan) {
                        return 0.0;
                    }

                    $amount = (float) $plan->price;

                    return $plan->interval === 'year' ? $amount / 12 : $amount;
                }), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}

<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Health score global al platformei StayOps (0–100).
 *
 * Pornește de la o bază de 100 și scade puncte proporțional cu
 * incidențele detectate: dispute deschise, sarcini restante,
 * rezervări afectate și (dacă e configurat) rata de erori aplicație.
 */
class PlatformHealthService
{
    /**
     * @return array{score: int, status: string, color: string, label: string, factors: list<array{label: string, value: int, points: int, max: int, url: string|null}>}
     */
    public function report(): array
    {
        $openDisputes = Dispute::query()
            ->whereIn('status', ['open', 'investigating', 'awaiting_response', 'escalated'])
            ->count();

        $overdueTasks = Task::query()
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->count();

        $affectedBookings = Booking::query()
            ->where('status', 'disputed')
            ->orWhereIn('id', Dispute::query()
                ->whereNotNull('booking_id')
                ->whereIn('status', ['open', 'investigating', 'awaiting_response', 'escalated'])
                ->select('booking_id'))
            ->count();

        $errorRate = $this->errorRate();

        $factors = [
            [
                'label' => 'Dispute deschise',
                'value' => $openDisputes,
                'points' => min(30, $openDisputes * 5),
                'max' => 30,
                'url' => '/admin/disputes',
            ],
            [
                'label' => 'Sarcini restante',
                'value' => $overdueTasks,
                'points' => min(25, $overdueTasks * 2),
                'max' => 25,
                'url' => '/admin/tasks',
            ],
            [
                'label' => 'Rezervări afectate',
                'value' => $affectedBookings,
                'points' => min(25, $affectedBookings * 5),
                'max' => 25,
                'url' => '/admin/bookings',
            ],
            [
                'label' => 'Erori aplicație (24h)',
                'value' => $errorRate,
                'points' => min(20, (int) round($errorRate)),
                'max' => 20,
                'url' => null,
            ],
        ];

        $penalty = (int) array_sum(array_column($factors, 'points'));

        $score = max(0, 100 - $penalty);

        return [
            'score' => $score,
            'status' => $this->statusFor($score),
            'color' => $this->colorFor($score),
            'label' => $this->labelFor($score),
            'factors' => $factors,
        ];
    }

    public function score(): int
    {
        return $this->report()['score'];
    }

    /**
     * Numărul de erori de aplicație înregistrate în ultimele 24h.
     *
     * Fără un tabel dedicat de loguri se raportează 0 (sănătos);
     * se poate conecta ulterior la un log driver/monitorizare.
     */
    public function errorRate(): int
    {
        return (int) Cache::remember(
            'platform_health.error_rate',
            now()->addMinutes(5),
            fn (): int => $this->countApplicationErrors(),
        );
    }

    protected function countApplicationErrors(): int
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return 0;
        }

        $yesterday = now()->subDay();

        $handle = @fopen($path, 'r');

        if ($handle === false) {
            return 0;
        }

        $count = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                if (! str_contains($line, '.ERROR')) {
                    continue;
                }

                if (preg_match('/\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})/', $line, $matches) === 1) {
                    try {
                        if (Carbon::parse($matches[1])->greaterThanOrEqualTo($yesterday)) {
                            $count++;
                        }
                    } catch (\Throwable) {
                        // format neașteptat — ignorăm linia
                    }
                }
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    private function statusFor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'healthy',
            $score >= 70 => 'warning',
            default => 'critical',
        };
    }

    private function colorFor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'success',
            $score >= 70 => 'warning',
            default => 'danger',
        };
    }

    private function labelFor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Sănătos',
            $score >= 70 => 'Atenție',
            default => 'Critic',
        };
    }
}

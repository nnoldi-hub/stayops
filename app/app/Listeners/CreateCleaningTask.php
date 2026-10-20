<?php

namespace App\Listeners;

use App\Events\BookingCheckedOut;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Task;
use Illuminate\Support\Carbon;

class CreateCleaningTask
{
    public function handle(BookingCheckedOut $event): void
    {
        $booking = $event->booking;

        if (! $booking->relationLoaded('property')) {
            $booking->loadMissing('property');
        }

        $property = $booking->property;

        if ($property === null) {
            return;
        }

        $exists = Task::query()
            ->where('booking_id', $booking->id)
            ->where('type', 'cleaning')
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            return;
        }

        $provider = $this->resolveProvider($property->preferred_provider_id, $property->city);

        Task::create([
            'property_id' => $property->id,
            'booking_id' => $booking->id,
            'type' => 'cleaning',
            'title' => 'Curățenie post check-out — '.$property->title,
            'description' => sprintf(
                'Check-out %s, rezervare %s. Oaspeți: %d.',
                $booking->check_out?->format('d.m.Y') ?? '—',
                $booking->reference,
                $booking->guests_count ?? 0,
            ),
            'provider_id' => $provider?->id,
            'status' => $provider !== null ? 'assigned' : 'open',
            'priority' => 'normal',
            'scheduled_at' => $this->scheduledAt($booking, $property),
            'currency' => $property->currency,
        ]);
    }

    private function resolveProvider(?int $preferredProviderId, ?string $city): ?Provider
    {
        if ($preferredProviderId !== null) {
            $preferred = Provider::query()
                ->whereKey($preferredProviderId)
                ->where('is_active', true)
                ->where('kyc_status', 'approved')
                ->first();

            if ($preferred !== null) {
                return $preferred;
            }
        }

        if ($city === null || $city === '') {
            return null;
        }

        return Provider::query()
            ->where('is_active', true)
            ->where('kyc_status', 'approved')
            ->whereJsonContains('service_types', 'cleaning')
            ->get()
            ->first(function (Provider $provider) use ($city): bool {
                /** @var array<int, string> $coverage */
                $coverage = $provider->coverage_cities ?? [];

                if ($coverage === []) {
                    return true;
                }

                return collect($coverage)->contains(
                    fn ($covered): bool => strcasecmp(trim((string) $covered), trim($city)) === 0
                );
            });
    }

    private function scheduledAt(Booking $booking, Property $property): Carbon
    {
        $checkOut = $booking->check_out;
        /** @var string $time */
        $time = $property->check_out_time ?? '11:00:00';

        if ($checkOut === null) {
            return Carbon::now();
        }

        return Carbon::parse($checkOut->format('Y-m-d').' '.$time);
    }
}

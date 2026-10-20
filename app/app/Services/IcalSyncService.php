<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\IcalEvent;
use App\Models\IcalFeed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IcalSyncService
{
    /**
     * Fetch and parse the .ics feed, upsert IcalEvents, create Bookings for new events.
     * Returns the number of newly created bookings.
     */
    public function sync(IcalFeed $feed): int
    {
        if (! $feed->is_active || ! $feed->url) {
            return 0;
        }

        try {
            $response = Http::timeout(15)->get($feed->url);
            $response->throw();

            $events = $this->parse($response->body());
            $synced = 0;

            foreach ($events as $event) {
                $icalEvent = IcalEvent::updateOrCreate(
                    ['feed_id' => $feed->id, 'uid' => $event['uid']],
                    [
                        'starts_on' => $event['starts_on'],
                        'ends_on' => $event['ends_on'],
                        'summary' => $event['summary'] ?? null,
                        'description' => $event['description'] ?? null,
                        'raw' => $event,
                    ]
                );

                if ($icalEvent->wasRecentlyCreated && $icalEvent->booking_id === null) {
                    $checkIn = Carbon::parse($event['starts_on']);
                    $checkOut = Carbon::parse($event['ends_on']);
                    $nights = max(1, (int) $checkIn->diffInDays($checkOut));

                    $booking = Booking::create([
                        'reference' => 'ICAL-'.strtoupper(Str::random(8)),
                        'property_id' => $feed->property_id,
                        'guest_id' => null,
                        'guest_name' => $event['summary'] ?? 'iCal Import',
                        'guest_email' => 'ical@noreply.com',
                        'guest_phone' => '',
                        'check_in' => $event['starts_on'],
                        'check_out' => $event['ends_on'],
                        'nights' => $nights,
                        'guests_count' => 1,
                        'adults' => 1,
                        'children' => 0,
                        'nightly_rate' => 0,
                        'subtotal' => 0,
                        'cleaning_fee' => 0,
                        'platform_fee' => 0,
                        'host_payout' => 0,
                        'total_price' => 0,
                        'currency' => 'RON',
                        'status' => 'confirmed',
                        'source' => 'ical',
                        'external_id' => $event['uid'],
                        'payment_status' => 'paid',
                    ]);

                    $icalEvent->update(['booking_id' => $booking->id]);
                    $synced++;
                }
            }

            $feed->update([
                'last_sync_at' => now(),
                'events_count' => count($events),
                'last_error' => null,
            ]);

            return $synced;
        } catch (\Throwable $e) {
            $feed->update(['last_error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Parse an iCalendar string and return an array of event arrays.
     * Each event has: uid, starts_on, ends_on, summary?, description?
     */
    public function parse(string $ics): array
    {
        $events = [];

        // Unfold continuation lines (RFC 5545 §3.1)
        $rawLines = preg_split('/\r?\n/', $ics);
        $lines = [];
        foreach ($rawLines as $line) {
            if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t")) {
                $last = count($lines) - 1;
                if ($last >= 0) {
                    $lines[$last] .= ltrim($line);
                }
            } else {
                $lines[] = $line;
            }
        }

        $inEvent = false;
        $current = [];

        foreach ($lines as $line) {
            $line = rtrim($line);

            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $current = [];

                continue;
            }

            if ($line === 'END:VEVENT') {
                $inEvent = false;
                if (! empty($current['uid']) && ! empty($current['starts_on']) && ! empty($current['ends_on'])) {
                    $events[] = $current;
                }

                continue;
            }

            if (! $inEvent) {
                continue;
            }

            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }

            $prop = strtoupper(substr($line, 0, $colon));
            $value = substr($line, $colon + 1);

            // Strip parameters from property name (e.g. DTSTART;VALUE=DATE → DTSTART)
            if (($semi = strpos($prop, ';')) !== false) {
                $prop = substr($prop, 0, $semi);
            }

            switch ($prop) {
                case 'UID':
                    $current['uid'] = $value;
                    break;
                case 'DTSTART':
                    $current['starts_on'] = $this->parseDate($value);
                    break;
                case 'DTEND':
                    $current['ends_on'] = $this->parseDate($value);
                    break;
                case 'SUMMARY':
                    $current['summary'] = $this->unescape($value);
                    break;
                case 'DESCRIPTION':
                    $current['description'] = $this->unescape($value);
                    break;
            }
        }

        return $events;
    }

    private function parseDate(string $value): string
    {
        // Remove time component (T...) to extract the date portion only
        $date = preg_replace('/T.*$/', '', $value);
        if (preg_match('/^\d{8}$/', $date)) {
            return Carbon::createFromFormat('Ymd', $date)->format('Y-m-d');
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    private function unescape(string $value): string
    {
        return str_replace(
            ['\\,', '\\;', '\\n', '\\N', '\\\\'],
            [',',   ';',   "\n",  "\n",  '\\'],
            $value
        );
    }
}

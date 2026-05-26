<x-mail::message>
# Rezervarea ta este confirmată

Bună {{ $booking->guest_name }},

Rezervarea ta cu codul **{{ $booking->reference }}** a fost confirmată.

**Detalii:**
- Proprietate: {{ $booking->property->title ?? '—' }}
- Check-in: {{ $booking->check_in?->format('d M Y') }}
- Check-out: {{ $booking->check_out?->format('d M Y') }}
- Nopți: {{ $booking->nights }}
- Oaspeți: {{ $booking->guests_count }}
- Total: {{ number_format((float) $booking->total_price, 2) }} {{ $booking->currency }}

Mulțumim,<br>
Echipa {{ config('app.name') }}
</x-mail::message>

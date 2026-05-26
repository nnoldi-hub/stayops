<x-mail::message>
# Rezervarea ta a fost anulată

Bună {{ $booking->guest_name }},

Rezervarea ta cu codul **{{ $booking->reference }}** a fost anulată.

@if ($reason)
**Motiv:** {{ $reason }}
@endif

**Detalii:**
- Proprietate: {{ $booking->property->title ?? '—' }}
- Check-in: {{ $booking->check_in?->format('d M Y') }}
- Check-out: {{ $booking->check_out?->format('d M Y') }}

Dacă ai întrebări despre rambursare, te rugăm să ne contactezi.

Echipa {{ config('app.name') }}
</x-mail::message>

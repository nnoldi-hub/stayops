<?php

use App\Models\Booking;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Dashboard')] class extends Component
{
    /**
     * Rezervările viitoare ale oaspetelui (nefinalizate).
     *
     * @return Collection<int, Booking>
     */
    #[Computed]
    public function upcomingBookings(): Collection
    {
        $user = auth()->user();

        if ($user === null) {
            return collect();
        }

        return Booking::query()
            ->where('guest_id', $user->getKey())
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->with('property')
            ->orderBy('check_in')
            ->limit(5)
            ->get();
    }

    /**
     * Rezervările încheiate ale oaspetelui (istoric).
     *
     * @return Collection<int, Booking>
     */
    #[Computed]
    public function pastBookings(): Collection
    {
        $user = auth()->user();

        if ($user === null) {
            return collect();
        }

        return Booking::query()
            ->where('guest_id', $user->getKey())
            ->whereIn('status', ['checked_out', 'cancelled', 'refunded', 'no_show'])
            ->with('property')
            ->orderByDesc('check_in')
            ->limit(3)
            ->get();
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'completion' => $user?->profileCompletion() ?? 0,
            'unreadMessages' => $user?->unreadMessagesCount() ?? 0,
            'panelPath' => $user !== null ? \App\Support\RoleRedirector::pathFor($user) : '/dashboard',
        ];
    }
}; ?>

<div>
    <div class="mx-auto max-w-5xl space-y-6">
        {{-- Salut --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="font-display text-2xl font-bold text-slate-900">
                        Bun venit, {{ auth()->user()->name }} 👋
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Aici vezi rezervările tale și starea contului.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('messages.index') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Mesaje
                        @if($unreadMessages > 0)
                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-orange px-1.5 text-[11px] font-bold text-white">
                                {{ $unreadMessages > 9 ? '9+' : $unreadMessages }}
                            </span>
                        @endif
                    </a>
                    @if($panelPath !== '/dashboard')
                        <a href="{{ $panelPath }}"
                           class="inline-flex items-center rounded-lg bg-brand-navy px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Panoul meu
                        </a>
                    @endif
                </div>
            </div>

            @if($completion < 100)
                <div class="mt-5 flex flex-wrap items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                    <span class="font-display text-lg font-extrabold text-brand-orange">{{ $completion }}%</span>
                    <span class="text-sm text-slate-600">profil completat —</span>
                    <a href="{{ route('profile.edit') }}" class="text-sm font-semibold text-brand-orange hover:text-brand-orange-600">
                        Completează-ți profilul →
                    </a>
                </div>
            @endif
        </div>

        {{-- Rezervări viitoare --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
            <h2 class="font-display text-lg font-bold text-slate-900">Rezervările mele</h2>

            @forelse($this->upcomingBookings as $booking)
                <a href="{{ route('bookings.show', $booking) }}"
                   class="mt-4 flex items-center justify-between gap-4 rounded-xl border border-slate-100 px-4 py-3 hover:bg-slate-50"
                   data-testid="dashboard-booking-{{ $booking->reference }}">
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-slate-800">{{ $booking->property?->title }}</div>
                        <div class="text-sm text-slate-500">
                            {{ $booking->check_in->translatedFormat('d M') }} — {{ $booking->check_out->translatedFormat('d M Y') }}
                            · {{ $booking->nights }} {{ $booking->nights === 1 ? 'noapte' : 'nopți' }}
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold
                        @if ($booking->status === 'confirmed') bg-green-100 text-green-700
                        @elseif ($booking->status === 'checked_in') bg-blue-100 text-blue-700
                        @else bg-amber-100 text-amber-700 @endif">
                        {{ match ($booking->status) {
                            'pending' => 'În așteptare',
                            'confirmed' => 'Confirmată',
                            'checked_in' => 'Cazat',
                            default => $booking->status,
                        } }}
                    </span>
                </a>
            @empty
                <div class="mt-4 rounded-xl bg-slate-50 px-4 py-8 text-center" data-testid="dashboard-empty-bookings">
                    <p class="text-sm text-slate-500">Nu ai rezervări active.</p>
                    <a href="{{ route('properties.index') }}" class="mt-2 inline-block text-sm font-semibold text-brand-orange hover:text-brand-orange-600">
                        Găsește o proprietate →
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Istoric --}}
        @if($this->pastBookings->isNotEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
                <h2 class="font-display text-lg font-bold text-slate-900">Istoric recent</h2>

                @foreach($this->pastBookings as $booking)
                    <a href="{{ route('bookings.show', $booking) }}"
                       class="mt-4 flex items-center justify-between gap-4 rounded-xl border border-slate-100 px-4 py-3 hover:bg-slate-50">
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-slate-700">{{ $booking->property?->title }}</div>
                            <div class="text-sm text-slate-400">
                                {{ $booking->check_in->translatedFormat('d M Y') }}
                            </div>
                        </div>
                        <span class="shrink-0 text-xs font-medium uppercase tracking-wide text-slate-400">
                            {{ match ($booking->status) {
                                'checked_out' => 'Finalizată',
                                'cancelled' => 'Anulată',
                                'refunded' => 'Rambursată',
                                'no_show' => 'Neprezentare',
                                default => $booking->status,
                            } }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

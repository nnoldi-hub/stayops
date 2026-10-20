<?php

use App\Models\Plan;
use App\Services\HostSubscriptionService;
use Laravel\Cashier\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Abonament gazda')] class extends Component {
    public ?string $errorMessage = null;

    public ?string $infoMessage = null;

    public function mount(): void
    {
        $this->infoMessage = match (request()->query('checkout')) {
            'success' => 'Plata a fost inițiata. Abonamentul se activeaza dupa confirmarea Stripe.',
            'cancelled' => 'Plata a fost anulata. Poti relua oricand.',
            default => null,
        };
    }

    #[Computed]
    public function subscription(): ?Subscription
    {
        return auth()->user()->activeHostSubscription();
    }

    #[Computed]
    public function activePlan(): ?Plan
    {
        return auth()->user()->activeHostPlan();
    }

    public function with(): array
    {
        return [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get(),
        ];
    }

    public function subscribe(int $planId): void
    {
        $plan = Plan::query()
            ->where('is_active', true)
            ->findOrFail($planId);

        try {
            $url = app(HostSubscriptionService::class)->startCheckout(
                auth()->user(),
                $plan,
                route('host.subscription', ['checkout' => 'success']),
                route('host.subscription', ['checkout' => 'cancelled']),
            );
        } catch (\Throwable $e) {
            $this->errorMessage = 'Eroare Stripe: '.$e->getMessage();
            $this->infoMessage = null;

            return;
        }

        $this->redirect($url);
    }

    public function cancel(): void
    {
        try {
            $cancelled = app(HostSubscriptionService::class)->cancel(auth()->user());
        } catch (\Throwable $e) {
            $this->errorMessage = 'Eroare Stripe: '.$e->getMessage();

            return;
        }

        unset($this->subscription, $this->activePlan);

        $this->errorMessage = null;
        $this->infoMessage = $cancelled
            ? 'Abonamentul va fi anulat la finalul perioadei curente.'
            : 'Nu ai un abonament activ de anulat.';
    }
}; ?>

<div>
    <div class="max-w-5xl mx-auto bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-semibold text-gray-900 mb-1">Abonament platforma</h1>
        <p class="text-sm text-gray-600 mb-6">
            Pentru a administra proprietati si rezervari ai nevoie de un abonament activ.
        </p>

        @if($errorMessage)
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded" data-testid="subscription-error">
                {{ $errorMessage }}
            </div>
        @endif

        @if($infoMessage)
            <div class="mb-4 p-3 bg-blue-100 text-blue-800 rounded" data-testid="subscription-info">
                {{ $infoMessage }}
            </div>
        @endif

        <div class="border rounded p-4 mb-8">
            <div class="flex items-center gap-3 mb-2">
                <span class="font-medium">Status abonament:</span>
                @if($this->subscription)
                    <span class="px-2 py-0.5 rounded text-sm font-semibold bg-green-100 text-green-800"
                          data-testid="subscription-status">
                        {{ $this->activePlan?->name_ro ?? 'Activ' }}
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded text-sm font-semibold bg-gray-100 text-gray-600"
                          data-testid="subscription-status">
                        Inactiv
                    </span>
                @endif
            </div>

            @if($this->subscription)
                <div class="text-xs text-gray-500 mb-3">
                    Pret: {{ $this->subscription->stripe_price }}
                    @if($this->activePlan)
                        &middot; {{ number_format((float) $this->activePlan->price, 2) }} {{ $this->activePlan->currency }}
                        / {{ $this->activePlan->interval === 'year' ? 'an' : 'luna' }}
                    @endif
                    @if($this->subscription->ends_at)
                        &middot; Se incheie la {{ $this->subscription->ends_at->format('d.m.Y') }}
                    @endif
                </div>

                @if(! $this->subscription->canceled())
                    <button type="button" wire:click="cancel"
                            data-testid="cancel-subscription"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-sm">
                        Anuleaza abonamentul
                    </button>
                @endif
            @else
                <p class="text-sm text-gray-600">
                    Alege un plan pentru a debloca panoul de gazda.
                </p>
            @endif
        </div>

        <h2 class="text-lg font-semibold text-gray-900 mb-4">Planuri disponibile</h2>

        @if($plans->isEmpty())
            <p class="text-sm text-gray-600" data-testid="no-plans">Nu exista planuri disponibile momentan.</p>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($plans as $plan)
                    <div class="border rounded p-4 flex flex-col" data-testid="plan-{{ $plan->id }}">
                        <h3 class="font-semibold text-gray-900">{{ $plan->name_ro }}</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            {{ number_format((float) $plan->price, 2) }} {{ $plan->currency }}
                            <span class="text-sm font-normal text-gray-500">
                                / {{ $plan->interval === 'year' ? 'an' : 'luna' }}
                            </span>
                        </p>
                        @if($plan->description_ro)
                            <p class="text-sm text-gray-600 mt-2">{{ $plan->description_ro }}</p>
                        @endif
                        @if($plan->max_properties)
                            <p class="text-xs text-gray-500 mt-2">Pana la {{ $plan->max_properties }} proprietati</p>
                        @endif
                        @if((float) $plan->platform_fee_pct > 0)
                            <p class="text-xs text-gray-500">Comision platforma: {{ $plan->platform_fee_pct }}%</p>
                        @endif

                        <button type="button" wire:click="subscribe({{ $plan->id }})"
                                data-testid="subscribe-plan-{{ $plan->id }}"
                                class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                            {{ $this->subscription ? 'Schimba planul' : 'Aboneaza-te' }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

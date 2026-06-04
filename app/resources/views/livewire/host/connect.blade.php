<?php

use App\Models\Host;
use App\Services\StripeConnectService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Stripe Connect — Onboarding')] class extends Component {

    public ?string $errorMessage = null;

    #[Computed]
    public function host(): Host
    {
        return auth()->user()->host;
    }

    public function startOnboarding(): void
    {
        $host = $this->host;
        $service = app(StripeConnectService::class);

        try {
            if (! $host->stripe_account_id) {
                $host = $service->createAccount($host);
            }

            $url = $service->createOnboardingLink(
                $host,
                route('host.connect.return'),
                route('host.connect.refresh'),
            );

            $this->redirect($url);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Eroare Stripe: ' . $e->getMessage();
        }
    }

    public function refreshStatus(): void
    {
        try {
            app(StripeConnectService::class)->refreshAccountStatus($this->host);
            unset($this->host);
            $this->errorMessage = null;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Eroare Stripe: ' . $e->getMessage();
        }
    }

}; ?>

<div>
    <h2 class="text-xl font-semibold mb-4">Stripe Connect — Onboarding</h2>

    @if($errorMessage)
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ $errorMessage }}</div>
    @endif

    @php $status = $this->host->stripe_account_status ?? 'none'; @endphp

    <div class="border rounded p-4 mb-6">
        <div class="flex items-center gap-3 mb-2">
            <span class="font-medium">Status cont Stripe:</span>
            <span class="px-2 py-0.5 rounded text-sm font-semibold
                @if($status === 'active') bg-green-100 text-green-800
                @elseif($status === 'pending') bg-yellow-100 text-yellow-800
                @elseif($status === 'restricted') bg-orange-100 text-orange-800
                @else bg-gray-100 text-gray-600
                @endif">
                {{ ucfirst($status) }}
            </span>
        </div>

        @if($this->host->stripe_account_id)
            <div class="text-xs text-gray-500 mb-3">
                ID cont: {{ $this->host->stripe_account_id }}
                @if($this->host->onboarded_at)
                    · Activat: {{ $this->host->onboarded_at->format('d.m.Y') }}
                @endif
            </div>
        @endif

        <div class="flex gap-3">
            @if($status !== 'active')
                <button wire:click="startOnboarding"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                    {{ $status === 'none' ? 'Înregistrează-te pe Stripe' : 'Continuă înregistrarea' }}
                </button>
            @endif

            @if($this->host->stripe_account_id)
                <button wire:click="refreshStatus"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-sm">
                    Actualizează status
                </button>
            @endif
        </div>
    </div>

    @if($status === 'active')
        <div class="p-4 bg-green-50 border border-green-200 rounded text-green-800 text-sm">
            Contul tău Stripe este activ. Poți primi plăți pentru rezervările de pe platformă.
        </div>
    @elseif($status === 'pending')
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm">
            Înregistrarea pe Stripe este în curs. Completează toate informațiile necesare pentru a activa plățile.
        </div>
    @elseif($status === 'restricted')
        <div class="p-4 bg-orange-50 border border-orange-200 rounded text-orange-800 text-sm">
            Contul tău Stripe are restricții. Continuă înregistrarea pentru a le rezolva.
        </div>
    @endif
</div>

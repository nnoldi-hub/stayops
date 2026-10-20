@auth
    @if (session()->has(\App\Services\ImpersonationService::SESSION_KEY))
        <div class="bg-amber-400 text-amber-950 text-sm font-semibold" data-testid="impersonation-banner">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-4">
                <span>
                    Impersonare activă:
                    <span data-testid="impersonation-target">{{ auth()->user()->name }}</span>
                    &lt;{{ auth()->user()->email }}&gt;
                </span>
                <form method="POST" action="{{ route('impersonation.stop') }}" data-testid="impersonation-stop-form">
                    @csrf
                    <button type="submit"
                            class="bg-amber-950 text-amber-50 px-3 py-1 rounded hover:bg-amber-800">
                        Ieși din impersonare
                    </button>
                </form>
            </div>
        </div>
    @endif
@endauth

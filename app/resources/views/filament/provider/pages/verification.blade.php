<x-filament-panels::page>
    @php
        $provider = $this->provider();
        $status = $provider?->kyc_status ?? 'pending';
        $label = match ($status) {
            'approved' => 'Aprobat',
            'rejected' => 'Respins',
            'submitted' => 'Trimis spre verificare',
            default => 'În așteptare',
        };
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <div class="flex items-center gap-3">
                <span class="font-medium">Status verificare:</span>
                <x-filament::badge :color="match ($status) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'submitted' => 'warning',
                    default => 'gray',
                }" data-testid="provider-kyc-status">
                    {{ $label }}
                </x-filament::badge>
            </div>

            <p class="mt-3 text-sm text-gray-500">
                Încarcă certificatul de înregistrare (CUI) sau un act de identitate pentru a putea primi
                sarcini pe platformă. Documentele sunt verificate de echipa StayOps.
            </p>
        </x-filament::section>

        <x-filament::section heading="Documente încărcate">
            @if (! empty($provider?->kyc_documents))
                <ul class="text-sm list-disc list-inside" data-testid="provider-kyc-documents">
                    @foreach ($provider->kyc_documents as $document)
                        <li>
                            <a class="underline" href="{{ \Illuminate\Support\Facades\Storage::url($document) }}" target="_blank">
                                {{ basename($document) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-500">Nu ai încărcat niciun document.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>

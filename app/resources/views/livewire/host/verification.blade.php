<?php

use App\Models\Host;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.public')] #[Title('Verificare KYC')] class extends Component {
    use WithFileUploads;

    /** @var array<int, \Illuminate\Http\UploadedFile> */
    public array $documents = [];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function host(): Host
    {
        return auth()->user()->host;
    }

    public function submit(): void
    {
        $host = $this->host;

        if ($host->kyc_status === 'approved') {
            $this->errorMessage = 'Contul tău este deja verificat.';
            $this->successMessage = null;

            return;
        }

        $this->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $paths = [];

        foreach ($this->documents as $file) {
            $paths[] = $file->store('kyc/hosts/'.$host->id, 'public');
        }

        $host->update([
            'kyc_documents' => array_values(array_merge($host->kyc_documents ?? [], $paths)),
            'kyc_status' => 'submitted',
        ]);

        unset($this->host);
        $this->documents = [];
        $this->errorMessage = null;
        $this->successMessage = 'Documentele au fost trimise spre verificare.';
    }

    #[Computed]
    public function statusLabel(): string
    {
        return match ($this->host->kyc_status) {
            'approved' => 'Aprobat',
            'rejected' => 'Respins',
            'submitted' => 'Trimis spre verificare',
            default => 'În așteptare',
        };
    }
}; ?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Verificare KYC</h1>
        <a href="{{ route('host.dashboard') }}"
           class="text-sm text-gray-600 hover:underline"
           data-testid="back-to-dashboard">&larr;napoi la dashboard</a>
    </div>

    @if ($successMessage)
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded" data-testid="kyc-success">{{ $successMessage }}</div>
    @endif

    @if ($errorMessage)
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded" data-testid="kyc-error">{{ $errorMessage }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="font-medium">Status:</span>
            @php($status = $this->host->kyc_status)
            <span data-testid="kyc-status"
                  class="px-2 py-0.5 rounded text-sm font-semibold
                    @if ($status === 'approved') bg-green-100 text-green-800
                    @elseif ($status === 'rejected') bg-red-100 text-red-800
                    @elseif ($status === 'submitted') bg-yellow-100 text-yellow-800
                    @else bg-gray-100 text-gray-600
                    @endif">
                {{ $this->statusLabel }}
            </span>
        </div>

        <p class="text-sm text-gray-600 mb-4">
            Încarcă un act de identitate sau certificatul de înregistrare (CUI) pentru ca
            proprietățile tale să devină publice pe platformă. Documentele sunt verificate de echipa noastră.
        </p>

        @if (! empty($this->host->kyc_documents))
            <div class="mb-4">
                <div class="text-sm font-medium text-gray-700 mb-2">Documente deja încărcate:</div>
                <ul class="text-sm text-gray-600 list-disc list-inside" data-testid="kyc-documents">
                    @foreach ($this->host->kyc_documents as $document)
                        <li>
                            <a class="underline" href="{{ \Illuminate\Support\Facades\Storage::url($document) }}" target="_blank">
                                {{ basename($document) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($status !== 'approved')
            <form wire:submit="submit" class="space-y-4">
                <div>
                    <input type="file" wire:model="documents" multiple accept=".jpg,.jpeg,.png,.pdf"
                           data-testid="kyc-documents-input" />
                    <div class="text-xs text-gray-500 mt-1">JPG, PNG sau PDF &ndash; maximum 5 MB per fișier.</div>
                    @error('documents') <div class="text-sm text-red-600" data-testid="kyc-documents-error">{{ $message }}</div> @enderror
                    @error('documents.*') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div wire:loading wire:target="documents" class="text-sm text-gray-500">Se încarcă fișierele...</div>

                <button type="submit"
                        class="bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded hover:bg-indigo-700 disabled:opacity-50"
                        wire:loading.attr="disabled"
                        wire:target="documents, submit"
                        data-testid="kyc-submit">
                    Trimite spre verificare
                </button>
            </form>
        @endif
    </div>
</div>

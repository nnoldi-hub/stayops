<?php

use App\Models\Host;
use App\Models\Property;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.public')] #[Title('Host — Dashboard')] class extends Component {
    use WithPagination;

    public function delete(int $propertyId): void
    {
        $property = Property::query()
            ->whereHas('host', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('id', $propertyId)
            ->firstOrFail();

        $property->delete();
        $this->resetPage();
    }

    #[Computed]
    public function host(): ?Host
    {
        return Host::query()->where('user_id', auth()->id())->first();
    }

    public function with(): array
    {
        $host = $this->host;
        $properties = $host
            ? Property::query()->where('host_id', $host->id)->orderByDesc('id')->paginate(10)
            : Property::query()->whereRaw('1=0')->paginate(10);

        return ['properties' => $properties];
    }
}; ?>

<div>
    <div class="max-w-5xl mx-auto bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Proprietățile mele</h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('host.subscription') }}"
                   data-testid="host-subscription-link"
                   class="text-sm font-semibold px-3 py-1.5 rounded border border-gray-300 text-gray-700 bg-gray-50">
                    Abonament
                </a>
                @php($kycStatus = $this->host?->kyc_status ?? 'pending')
                <a href="{{ route('host.verification') }}"
                   data-testid="kyc-verification-link"
                   class="text-sm font-semibold px-3 py-1.5 rounded border
                        @if ($kycStatus === 'approved') border-green-300 text-green-800 bg-green-50
                        @elseif ($kycStatus === 'rejected') border-red-300 text-red-800 bg-red-50
                        @else border-yellow-300 text-yellow-800 bg-yellow-50
                        @endif">
                    KYC: {{ match ($kycStatus) { 'approved' => 'Aprobat', 'rejected' => 'Respins', 'submitted' => 'În verificare', default => 'Necompletat' } }}
                </a>
                <a href="{{ route('host.properties.create') }}"
               class="bg-indigo-600 text-white text-sm font-semibold px-3 py-1.5 rounded hover:bg-indigo-700"
               data-testid="create-property-link">+ Adaugă proprietate</a>
            </div>
        </div>

        @if ($properties->isEmpty())
            <p class="mt-6 text-sm text-gray-600" data-testid="empty-state">Nu ai încă nicio proprietate înregistrată.</p>
        @else
            <table class="mt-6 w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Titlu</th>
                        <th class="py-2">Oraș</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Preț / noapte</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($properties as $p)
                        <tr class="border-b" data-testid="property-row-{{ $p->id }}">
                            <td class="py-2 font-medium text-gray-900">{{ $p->title }}</td>
                            <td class="py-2">{{ $p->city }}</td>
                            <td class="py-2"><span class="inline-block text-xs uppercase tracking-wide bg-gray-100 text-gray-700 rounded px-2 py-1">{{ $p->status }}</span></td>
                            <td class="py-2">{{ number_format((float) $p->base_price, 2) }} {{ $p->currency }}</td>
                            <td class="py-2 text-right flex gap-3 justify-end">
                                <a href="{{ route('host.properties.edit', $p) }}" class="text-indigo-600 hover:underline" data-testid="edit-property-{{ $p->id }}">Editează</a>
                                <button type="button"
                                        wire:click="delete({{ $p->id }})"
                                        wire:confirm="Sigur ștergi proprietatea?"
                                        class="text-red-600 hover:underline"
                                        data-testid="delete-property-{{ $p->id }}">Șterge</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">{{ $properties->links() }}</div>
        @endif
    </div>
</div>

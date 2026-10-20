<div class="relative" wire:poll.30s x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        @click="open = !open"
        class="relative rounded-full p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500"
        data-testid="notification-bell"
        aria-label="Notificări"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if($unreadCount > 0)
            <span
                class="absolute -top-0.5 -right-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-bold leading-none text-white"
                data-testid="notification-badge"
            >
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 z-50 mt-2 w-80 rounded-xl border border-slate-200 bg-white shadow-lg"
        data-testid="notification-dropdown"
    >
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <span class="text-sm font-semibold text-slate-800">Notificări</span>
            @if($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllRead"
                    class="text-xs font-medium text-orange-600 hover:text-orange-700"
                >
                    Marchează toate citite
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <button
                    type="button"
                    wire:click="markRead('{{ $notification->id }}')"
                    class="block w-full border-b border-slate-50 px-4 py-3 text-left hover:bg-slate-50 {{ $notification->read_at === null ? 'bg-orange-50/50' : '' }}"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-sm font-medium text-slate-800">
                            {{ $notification->data['title'] ?? 'Notificare' }}
                        </span>
                        @if($notification->read_at === null)
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-orange-500"></span>
                        @endif
                    </div>
                    @if(! empty($notification->data['body']))
                        <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">
                            {{ $notification->data['body'] }}
                        </p>
                    @endif
                    <span class="mt-1 block text-[11px] text-slate-400">
                        {{ $notification->created_at->diffForHumans() }}
                    </span>
                </button>
            @empty
                <p class="px-4 py-6 text-center text-sm text-slate-400">Nicio notificare încă.</p>
            @endforelse
        </div>
    </div>
</div>

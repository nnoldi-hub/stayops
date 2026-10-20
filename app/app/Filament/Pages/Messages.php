<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class Messages extends Page
{
    protected static ?string $title = 'Mesaje';

    protected static ?string $navigationLabel = 'Mesaje';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = -10;

    protected string $view = 'filament.pages.messages';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Conversațiile tale cu oaspeții, gazdele și firmele de mentenanță.';
    }

    /**
     * @return Collection<int, array{conversation: Conversation, title: string, unread: int}>
     */
    public function getEntries(): Collection
    {
        $user = auth()->user();

        if ($user === null) {
            return collect();
        }

        return Conversation::query()
            ->whereHas('users', fn ($query) => $query->where('users.id', $user->getKey()))
            ->with(['users', 'lastMessage.sender'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Conversation $conversation): array => [
                'conversation' => $conversation,
                'title' => $conversation->titleFor($user),
                'unread' => $conversation->unreadCountFor($user),
            ])
            ->values();
    }
}

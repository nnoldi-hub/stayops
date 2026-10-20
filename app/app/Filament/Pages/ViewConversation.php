<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewConversation extends Page
{
    protected static ?string $slug = 'messages/{conversation}';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Conversație';

    protected string $view = 'filament.pages.view-conversation';

    public Conversation $conversation;

    public string $body = '';

    public function mount(Conversation $conversation): void
    {
        $user = auth()->user();

        abort_unless($user !== null && $conversation->isParticipant($user), 403);

        $conversation->markReadFor($user);
        $conversation->load('users', 'booking');

        $this->conversation = $conversation;
    }

    public function getTitle(): string|Htmlable
    {
        $user = auth()->user();

        if (isset($this->conversation) && $user !== null) {
            return $this->conversation->titleFor($user);
        }

        return 'Conversație';
    }

    public function send(): void
    {
        $this->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth()->user();
        abort_unless($user !== null && $this->conversation->isParticipant($user), 403);

        $this->conversation->sendMessage($user, $this->body);

        $this->body = '';
        $this->conversation->refresh();
    }

    public function markRead(): void
    {
        $user = auth()->user();

        if ($user !== null && $this->conversation->isParticipant($user)) {
            $this->conversation->markReadFor($user);
        }
    }
}

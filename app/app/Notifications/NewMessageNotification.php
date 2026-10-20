<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
        public Message $message,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Mesaj nou de la '.$this->message->sender?->name,
            'body' => Str::limit($this->message->body, 100),
            'url' => route('messages.show', $this->conversation),
            'conversation_id' => $this->conversation->id,
        ];
    }
}

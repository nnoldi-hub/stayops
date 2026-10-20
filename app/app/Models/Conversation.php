<?php

namespace App\Models;

use App\Notifications\NewMessageNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Conversation extends Model
{
    protected $fillable = [
        'subject', 'direct_key', 'booking_id', 'task_id', 'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Găsește sau creează o conversație directă între doi utilizatori.
     */
    public static function findOrCreateDirect(User $a, User $b): self
    {
        $key = collect([$a->id, $b->id])->sort()->values()->implode('-');

        $conversation = static::firstOrCreate(['direct_key' => $key]);

        $conversation->users()->syncWithoutDetaching([$a->id, $b->id]);

        return $conversation;
    }

    /**
     * Trimite un mesaj și notifică participanții (bază de date).
     */
    public function sendMessage(User $sender, string $body): Message
    {
        $this->users()->syncWithoutDetaching([$sender->id]);

        $message = $this->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $this->forceFill(['last_message_at' => $message->created_at ?? now()])->save();

        $recipientIds = $this->users()
            ->where('users.id', '!=', $sender->getKey())
            ->pluck('users.id');

        User::query()
            ->whereKey($recipientIds)
            ->get()
            ->each(function (User $participant) use ($message): void {
                $participant->notify(new NewMessageNotification($this, $message));
            });

        return $message;
    }

    public function isParticipant(User $user): bool
    {
        return $this->users()->whereKey($user->getKey())->exists();
    }

    public function markReadFor(User $user): void
    {
        $this->users()->updateExistingPivot($user->getKey(), ['last_read_at' => now()]);
    }

    public function unreadCountFor(User $user): int
    {
        $lastRead = DB::table('conversation_user')
            ->where('conversation_id', $this->getKey())
            ->where('user_id', $user->getKey())
            ->value('last_read_at');

        return $this->messages()
            ->where('sender_id', '!=', $user->getKey())
            ->when($lastRead !== null, fn ($query) => $query->where('created_at', '>', $lastRead))
            ->count();
    }

    /**
     * Ceilalți participanți (excluzând utilizatorul curent).
     *
     * @return Collection<int, User>
     */
    public function othersExcept(User $user): Collection
    {
        return $this->users()->where('users.id', '!=', $user->getKey())->get();
    }

    /**
     * Numele interlocutorilor pentru afișare.
     */
    public function titleFor(User $viewer): string
    {
        if (filled($this->subject)) {
            return $this->subject;
        }

        $names = $this->othersExcept($viewer)
            ->pluck('name')
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : 'Conversație';
    }
}

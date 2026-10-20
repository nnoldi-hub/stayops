<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'locale', 'avatar_path', 'email_verified_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret'])]
class User extends Authenticatable implements FilamentUser
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    use Billable;

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Determines whether the user can access a given Filament panel.
     * Only SuperAdmin can access /admin; only Provider can access /provider.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole('SuperAdmin'),
            'provider' => $this->hasRole('Provider'),
            default => false,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
        ];
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function suspend(string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspended_reason' => $reason,
        ])->save();
    }

    public function activate(): void
    {
        $this->forceFill([
            'status' => self::STATUS_ACTIVE,
            'suspended_at' => null,
            'suspended_reason' => null,
        ])->save();
    }

    /**
     * @return HasOne<Host, $this>
     */
    public function host(): HasOne
    {
        return $this->hasOne(Host::class);
    }

    /**
     * @return HasOne<Provider, $this>
     */
    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    /**
     * @return BelongsToMany<Conversation, $this>
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Mesaje necitite din toate conversațiile (badge în meniu).
     */
    public function unreadMessagesCount(): int
    {
        return Message::query()
            ->join('conversation_user', 'conversation_user.conversation_id', '=', 'messages.conversation_id')
            ->where('conversation_user.user_id', $this->getKey())
            ->where('messages.sender_id', '!=', $this->getKey())
            ->where(function ($query) {
                $query->whereNull('conversation_user.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_user.last_read_at');
            })
            ->count();
    }

    /**
     * Gradul de completare al profilului (0–100) — badge de profesionalism.
     */
    public function profileCompletion(): int
    {
        $checks = [
            filled($this->name),
            $this->email_verified_at !== null,
            filled($this->phone),
            filled($this->avatar_path),
            $this->two_factor_confirmed_at !== null,
            $this->host()->exists() || $this->provider()->exists() || $this->hasRole('Guest'),
        ];

        return (int) round(count(array_filter($checks)) / count($checks) * 100);
    }

    /**
     * URL public al avatarului, sau null dacă nu e setat.
     */
    public function avatarUrl(): ?string
    {
        return filled($this->avatar_path)
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    /**
     * Iniţialele utilizatorului (fallback de avatar).
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part): bool => $part !== ''));

        if ($parts === []) {
            return '?';
        }

        $first = mb_substr($parts[0], 0, 1);

        if (count($parts) === 1) {
            return mb_strtoupper($first);
        }

        $last = mb_substr($parts[count($parts) - 1], 0, 1);

        return mb_strtoupper($first.$last);
    }

    /**
     * Paşii de completare ai profilului (pentru badge-ul de profesionalism).
     *
     * @return array<int, array{label: string, done: bool}>
     */
    public function profileCompletionSteps(): array
    {
        return [
            ['label' => 'Nume complet', 'done' => filled($this->name)],
            ['label' => 'Email verificat', 'done' => $this->email_verified_at !== null],
            ['label' => 'Telefon adăugat', 'done' => filled($this->phone)],
            ['label' => 'Fotografie de profil', 'done' => filled($this->avatar_path)],
            ['label' => 'Autentificare în doi paşi', 'done' => $this->two_factor_confirmed_at !== null],
            ['label' => 'Profil de platformă', 'done' => $this->host()->exists() || $this->provider()->exists() || $this->hasRole('Guest')],
        ];
    }

    /**
     * Whether this account currently holds a valid host subscription.
     */
    public function hasActiveHostSubscription(): bool
    {
        return $this->subscribed('host');
    }

    /**
     * The active host subscription, if any.
     */
    public function activeHostSubscription(): ?Subscription
    {
        $subscription = $this->subscription('host');

        return $subscription?->valid() ? $subscription : null;
    }

    /**
     * The plan backing the active host subscription, resolved by stripe_price_id.
     */
    public function activeHostPlan(): ?Plan
    {
        $subscription = $this->activeHostSubscription();

        if (! $subscription || blank($subscription->stripe_price)) {
            return null;
        }

        return Plan::query()
            ->where('stripe_price_id', $subscription->stripe_price)
            ->where('is_active', true)
            ->first();
    }
}

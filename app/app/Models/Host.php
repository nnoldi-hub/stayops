<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Role;

class Host extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (Host $host): void {
            $host->user?->assignRole(Role::findOrCreate('Host', 'web'));
        });
    }

    protected $fillable = [
        'user_id', 'company_name', 'cui', 'registration_number',
        'billing_address', 'billing_city', 'billing_country', 'vat_number',
        'kyc_status', 'kyc_documents', 'stripe_account_id', 'stripe_account_status',
        'onboarded_at',
    ];

    protected $casts = [
        'kyc_documents' => 'array',
        'onboarded_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->user?->hasActiveHostSubscription() ?? false;
    }

    public function activePlan(): ?Plan
    {
        return $this->user?->activeHostPlan();
    }

    public function platformFeePct(): float
    {
        $fallback = (float) config('services.stripe.platform_fee_pct', 0);
        $plan = $this->activePlan();

        return (float) ($plan->platform_fee_pct ?? $fallback);
    }

    /**
     * Hosts whose owner holds a valid, paid host subscription.
     *
     * @param  Builder<Host>  $query
     * @return Builder<Host>
     */
    public function scopeWithActiveSubscription(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $user) => $user->whereHas(
            'subscriptions',
            fn (Builder $subscription) => $subscription
                ->where('type', 'host')
                ->where(fn (Builder $state) => $state
                    ->whereIn('stripe_status', ['active', 'trialing'])
                    ->orWhere('ends_at', '>', now()))
        ));
    }
}

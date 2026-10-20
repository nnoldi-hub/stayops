<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Task extends Model implements Auditable, HasMedia
{
    use AuditableTrait, HasFactory, InteractsWithMedia;

    protected $fillable = [
        'property_id', 'booking_id', 'type', 'title', 'description',
        'provider_id', 'assigned_to_user_id', 'status', 'priority',
        'scheduled_at', 'started_at', 'completed_at', 'verified_at',
        'estimated_cost', 'actual_cost', 'currency', 'notes', 'checklist',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'checklist' => 'array',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Scope tasks visible to a provider user: directly assigned to them,
     * or belonging to a provider whose user account is the given user.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeVisibleToProviderUser(Builder $query, int|string|null $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query->where('assigned_to_user_id', $userId)
                ->orWhereHas('provider', fn (Builder $q) => $q->where('user_id', $userId));
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('before_photos')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('after_photos')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function hasAfterPhotos(): bool
    {
        return $this->getMedia('after_photos')->isNotEmpty();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Property extends Model implements Auditable, HasMedia
{
    use AuditableTrait, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'host_id', 'preferred_provider_id', 'slug', 'title', 'description', 'property_type',
        'address', 'city', 'county', 'country', 'postal_code',
        'latitude', 'longitude',
        'base_price', 'currency', 'cleaning_fee',
        'max_guests', 'bedrooms', 'beds', 'bathrooms',
        'check_in_time', 'check_out_time', 'min_nights', 'max_nights',
        'house_rules', 'status', 'rating_avg', 'reviews_count',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'rating_avg' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * @return BelongsTo<Host, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }

    /**
     * Properties visible to guests: published, owned by a KYC-approved host
     * whose owner holds an active platform subscription.
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereHas('host', fn (Builder $hostQuery): Builder => $hostQuery
                ->where('kyc_status', 'approved')
                ->has('user.subscriptions', '>=', 1)
                ->whereHas('user.subscriptions', fn (Builder $subscription): Builder => $subscription
                    ->where('type', 'host')
                    ->where(fn (Builder $state): Builder => $state
                        ->whereIn('stripe_status', ['active', 'trialing'])
                        ->orWhere('ends_at', '>', now()))));
    }

    public function preferredProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'preferred_provider_id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PropertyPricingRule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function icalFeeds(): HasMany
    {
        return $this->hasMany(IcalFeed::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'target');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('floor_plans')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->fit(Fit::Crop, 400, 300);

        $this->addMediaConversion('medium')
            ->nonQueued()
            ->fit(Fit::Contain, 1200, 900);
    }
}

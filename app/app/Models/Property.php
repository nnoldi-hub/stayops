<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Property extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'host_id', 'slug', 'title', 'description', 'property_type',
        'address', 'city', 'county', 'country', 'postal_code',
        'base_price', 'currency', 'cleaning_fee',
        'max_guests', 'bedrooms', 'beds', 'bathrooms',
        'check_in_time', 'check_out_time', 'min_nights', 'max_nights',
        'house_rules', 'status', 'rating_avg', 'reviews_count',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'rating_avg' => 'decimal:2',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
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
}

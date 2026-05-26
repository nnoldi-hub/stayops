<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending', 'confirmed', 'checked_in', 'checked_out',
        'cancelled', 'refunded', 'no_show', 'disputed',
    ];

    public const ALLOWED_TRANSITIONS = [
        'pending'      => ['confirmed', 'cancelled'],
        'confirmed'    => ['checked_in', 'cancelled', 'no_show'],
        'checked_in'   => ['checked_out', 'disputed'],
        'checked_out'  => ['disputed', 'refunded'],
        'cancelled'    => ['refunded'],
        'no_show'      => ['refunded'],
        'disputed'     => ['refunded', 'checked_out'],
        'refunded'     => [],
    ];

    protected $fillable = [
        'reference', 'property_id', 'guest_id',
        'guest_name', 'guest_email', 'guest_phone',
        'check_in', 'check_out', 'nights', 'guests_count', 'adults', 'children',
        'nightly_rate', 'subtotal', 'cleaning_fee', 'platform_fee', 'host_payout', 'total_price', 'currency',
        'status', 'source', 'external_id',
        'stripe_payment_intent_id', 'payment_status',
        'special_requests', 'internal_notes',
        'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'cancelled_at' => 'datetime',
        'nightly_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'host_payout' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(BookingStateTransition::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }
}

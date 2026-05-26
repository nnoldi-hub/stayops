<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'author_id', 'target_type', 'target_id',
        'rating', 'cleanliness', 'communication', 'accuracy', 'value',
        'comment', 'host_response', 'host_responded_at', 'is_published',
    ];

    protected $casts = [
        'host_responded_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}

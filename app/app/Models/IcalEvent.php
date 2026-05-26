<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcalEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_id', 'uid', 'starts_on', 'ends_on', 'summary', 'description', 'booking_id', 'raw',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'raw' => 'array',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(IcalFeed::class, 'feed_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

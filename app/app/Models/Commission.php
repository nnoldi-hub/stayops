<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'amount', 'percentage', 'currency', 'status', 'collected_at'];

    protected $casts = [
        'collected_at' => 'datetime',
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

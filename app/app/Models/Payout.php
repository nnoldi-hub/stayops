<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Payout extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $fillable = [
        'host_id', 'stripe_transfer_id', 'amount', 'currency', 'status',
        'period_start', 'period_end', 'booking_ids', 'failure_reason', 'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'booking_ids' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}

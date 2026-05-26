<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'booking_id', 'task_id', 'opener_id',
        'category', 'subject', 'description', 'status',
        'assigned_admin_id', 'resolution', 'refund_amount', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opener_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IcalFeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'direction', 'source_name', 'url', 'export_token',
        'last_sync_at', 'last_error', 'events_count', 'is_active',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(IcalEvent::class, 'feed_id');
    }
}

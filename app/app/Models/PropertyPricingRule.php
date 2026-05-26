<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'type', 'starts_on', 'ends_on', 'days_of_week',
        'modifier_type', 'modifier_value', 'priority', 'is_active',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'days_of_week' => 'array',
        'is_active' => 'boolean',
        'modifier_value' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

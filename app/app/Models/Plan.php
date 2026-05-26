<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name_ro', 'name_en', 'description_ro', 'description_en',
        'price', 'currency', 'interval', 'features',
        'max_properties', 'platform_fee_pct', 'stripe_price_id',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'platform_fee_pct' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}

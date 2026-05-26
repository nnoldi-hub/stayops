<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Host extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'company_name', 'cui', 'registration_number',
        'billing_address', 'billing_city', 'billing_country', 'vat_number',
        'kyc_status', 'kyc_documents', 'stripe_account_id', 'stripe_account_status',
        'onboarded_at',
    ];

    protected $casts = [
        'kyc_documents' => 'array',
        'onboarded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}

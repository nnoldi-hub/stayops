<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Provider extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'company_name', 'cui', 'service_types', 'coverage_cities',
        'kyc_status', 'kyc_documents', 'rating_avg', 'jobs_completed', 'is_active',
    ];

    protected $casts = [
        'service_types' => 'array',
        'coverage_cities' => 'array',
        'kyc_documents' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

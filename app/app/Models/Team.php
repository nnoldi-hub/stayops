<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['provider_id', 'name', 'description'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }
}

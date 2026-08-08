<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCenter extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Un centro de salud pertenece a una organización.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
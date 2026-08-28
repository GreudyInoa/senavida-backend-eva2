<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecuritySetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'health_center_id',
        'cta_max_attempts',
    ];

    protected $casts = [
        'cta_max_attempts' => 'integer',
    ];

    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class);
    }
}
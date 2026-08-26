<?php

namespace App\Models;

use App\Enums\PictogramSeverity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pictogram extends Model
{
    use HasUuids;

    protected $fillable = [
        'pictogram_category_id',
        'title',
        'phrase',
        'speech_text',
        'emoji',
        'severity',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'severity'  => PictogramSeverity::class,
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PictogramCategory::class, 'pictogram_category_id');
    }
}
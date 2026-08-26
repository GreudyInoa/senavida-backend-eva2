<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PictogramCategory extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function pictograms(): HasMany
    {
        return $this->hasMany(Pictogram::class);
    }
}
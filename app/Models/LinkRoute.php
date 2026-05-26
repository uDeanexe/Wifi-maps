<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkRoute extends Model
{
    protected $fillable = [
        'link_id',
        'provider',
        'geometry',
        'distance_meters',
        'duration_seconds',
        'last_error',
    ];

    protected $casts = [
        'geometry' => 'array',
        'distance_meters' => 'float',
        'duration_seconds' => 'float',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}


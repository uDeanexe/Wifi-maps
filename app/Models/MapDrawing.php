<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapDrawing extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'geometry',
        'properties',
    ];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

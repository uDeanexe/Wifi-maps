<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    protected $fillable = [
        'node_type_id',
        'code',
        'name',
        'latitude',
        'longitude',
        'address',
        'photo_path',
        'notes',
        'topology_x',
        'topology_y',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'topology_x' => 'integer',
        'topology_y' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(NodeType::class, 'node_type_id');
    }

    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(Link::class, 'source_node_id');
    }

    public function incomingLinks(): HasMany
    {
        return $this->hasMany(Link::class, 'target_node_id');
    }
}

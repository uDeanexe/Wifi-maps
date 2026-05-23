<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Link extends Model
{
    protected $fillable = [
        'source_node_id',
        'target_node_id',
        'cable_type',
        'core_count',
        'core_number',
        'pon_name',
        'odc_name',
        'notes',
    ];

    protected $casts = [
        'core_count' => 'integer',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'source_node_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'target_node_id');
    }
}

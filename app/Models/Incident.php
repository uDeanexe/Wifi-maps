<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    public const STATUSES = ['reported', 'assigned', 'in_progress', 'completed', 'closed'];

    protected $fillable = [
        'node_id',
        'category',
        'title',
        'description',
        'reporter_name',
        'reporter_contact',
        'photo_path',
        'noc_admin_name',
        'technician_name',
        'technician_contact',
        'technician_email',
        'work_order_notes',
        'technician_report',
        'status',
        'assigned_at',
        'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NodeType extends Model
{
    protected $fillable = ['name', 'label', 'icon'];

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }
}

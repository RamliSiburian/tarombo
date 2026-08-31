<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeSpouse extends Model
{
    protected $fillable = [
        'node_id',
        'name',
        'marga',
        'foto',
        'deskripsi',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }
}

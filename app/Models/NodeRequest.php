<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeRequest extends Model
{
    protected $fillable = [
        'node_id',
        'parent_node_id',
        'name',
        'gender',
        'marga',
        'asal_daerah',
        'tahun_lahir',
        'tahun_wafat',
        'foto',
        'deskripsi',
        'spouse_name',
        'spouse_marga',
        'spouse_foto',
        'spouse_deskripsi',
        'requester_name',
        'requester_email',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    public function parentNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'parent_node_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

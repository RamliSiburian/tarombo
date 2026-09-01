<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'gender',
        'marga',
        'asal_daerah',
        'tahun_lahir',
        'tahun_wafat',
        'foto',
        'deskripsi',
        'status',
        'level',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'parent_id');
    }

    public function activeChildren(): HasMany
    {
        return $this->hasMany(Node::class, 'parent_id')->where('status', 'active');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Node::class, 'parent_id')->whereIn('status', ['active', 'pending']);
    }

    public function allChildren(): HasMany
    {
        return $this->hasMany(Node::class, 'parent_id');
    }

    public function spouses(): HasMany
    {
        return $this->hasMany(NodeSpouse::class, 'node_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(NodeRequest::class, 'parent_node_id');
    }

    // Recursive children for full tree
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with(['childrenRecursive', 'spouses']);
    }

    // Get ancestry path from this node up to root
    public function getAncestorIds(): array
    {
        $ids = [];
        $node = $this;
        while ($node->parent_id !== null) {
            $ids[] = $node->parent_id;
            $node = $node->parent;
        }
        return $ids;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}

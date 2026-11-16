<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    protected $fillable = [
        'parent_id',
        'mother_spouse_id',
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
        'sort_order',
        'order_confirmed',
    ];

    protected $casts = [
        'level'           => 'integer',
        'sort_order'      => 'integer',
        'mother_spouse_id'=> 'integer',
        'order_confirmed' => 'boolean',
    ];

    protected $appends = ['has_sibling_conflict', 'conflicting_siblings'];

    protected ?array $cachedConflictingSiblings = null;

    /**
     * Sibling nodes that share the exact same sort_order and have unconfirmed order
     */
    public function getConflictingSiblingsAttribute(): array
    {
        if (!$this->parent_id || $this->order_confirmed) {
            return [];
        }

        if ($this->cachedConflictingSiblings !== null) {
            return $this->cachedConflictingSiblings;
        }

        return $this->cachedConflictingSiblings = static::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->where('sort_order', $this->sort_order)
            ->where('status', 'active')
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'marga', 'sort_order', 'order_confirmed', 'gender'])
            ->toArray();
    }

    /**
     * A node has a sibling conflict when:
     *  - It has a parent (is not root)
     *  - Its order is NOT yet confirmed by admin
     *  - Another active sibling shares the same sort_order
     */
    public function getHasSiblingConflictAttribute(): bool
    {
        if (!$this->parent_id || $this->order_confirmed) {
            return false;
        }

        return count($this->conflicting_siblings) > 0;
    }

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'parent_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(NodeSpouse::class, 'mother_spouse_id');
    }

    public function activeChildren(): HasMany
    {
        return $this->hasMany(Node::class, 'parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Node::class, 'parent_id')
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }

    public function allChildren(): HasMany
    {
        return $this->hasMany(Node::class, 'parent_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
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
        return $this->children()->with(['childrenRecursive', 'spouses', 'parent.spouses', 'mother']);
    }

    // Recursively update descendants level when this node's level changes
    public function updateDescendantLevels(): void
    {
        $children = $this->children()->get();
        foreach ($children as $child) {
            $child->level = $this->level + 1;
            $child->save();
            $child->updateDescendantLevels();
        }
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

    // Get ordered ancestor models from Root down to parent
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        while ($current !== null) {
            $ancestors[] = $current->load(['spouses']);
            $current = $current->parent;
        }
        return array_reverse($ancestors);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'node_id');
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

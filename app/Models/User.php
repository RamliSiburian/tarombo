<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'marga',
        'phone',
        'membership_status',
        'node_id',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isApproved(): bool
    {
        return $this->membership_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->membership_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->membership_status === 'rejected';
    }

    public function node(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    public function nodeRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(NodeRequest::class, 'user_id');
    }

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function scopeMembers($query)
    {
        return $query->where('role', 'member');
    }

    public function scopePending($query)
    {
        return $query->where('membership_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('membership_status', 'approved');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

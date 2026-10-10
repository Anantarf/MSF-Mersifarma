<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'region_id',
        'manager_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'code');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function callLists(): HasMany
    {
        return $this->hasMany(CallList::class, 'owner_id');
    }

    public function assignedApprovalCallLists(): HasMany
    {
        return $this->hasMany(CallList::class, 'assigned_approver_id');
    }

    public function callPlans(): HasMany
    {
        return $this->hasMany(CallPlan::class, 'mr_id');
    }

    public function callActuals(): HasMany
    {
        return $this->hasMany(CallActual::class, 'mr_id');
    }

    public function isMr(): bool
    {
        return $this->role === 'mr';
    }

    public function isDm(): bool
    {
        return $this->role === 'dm';
    }

    public function isRsm(): bool
    {
        return $this->role === 'rsm';
    }

    public function isMm(): bool
    {
        return $this->role === 'mm';
    }
}

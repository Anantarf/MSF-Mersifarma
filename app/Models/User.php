<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

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
        'username',
        'email',
        'password',
        'role',
        'region_id',
        'district',
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

    public function getSubordinateMrUsers(): Collection
    {
        if ($this->role === 'mr') {
            return collect([$this]);
        }

        if ($this->role === 'dm') {
            return self::where('role', 'mr')
                ->where('manager_id', $this->id)
                ->orderBy('name')
                ->get();
        }

        if ($this->role === 'rsm') {
            $dmIds = self::where('role', 'dm')
                ->where('manager_id', $this->id)
                ->pluck('id');

            return self::where('role', 'mr')
                ->whereIn('manager_id', $dmIds)
                ->orderBy('name')
                ->get();
        }

        if ($this->role === 'mm') {
            return self::where('role', 'mr')
                ->orderBy('name')
                ->get();
        }

        return collect();
    }

    public function getSubordinateMrIds(): array
    {
        return $this->getSubordinateMrUsers()->pluck('id')->all();
    }

    public function getRoleSubtitleAttribute(): string
    {
        return match ($this->username) {
            'mr' => 'MR Jakarta Selatan',
            'mr2' => 'MR Jakarta Selatan',
            'mr3' => 'MR Jakarta Timur',
            'mr4' => 'MR Jakarta Timur',
            'dm' => 'District Manager Jakarta Selatan',
            'dm2' => 'District Manager Jakarta Timur',
            'rsm' => 'Regional Sales Manager DKI Jakarta',
            'mm' => 'Marketing Manager Nasional',
            default => match ($this->role) {
                'mr' => $this->district ? 'MR '.$this->district : 'MR',
                'dm' => $this->district ? 'District Manager '.$this->district : 'District Manager',
                'rsm' => 'Regional Sales Manager',
                'mm' => 'Marketing Manager',
                default => strtoupper((string) $this->role),
            },
        };
    }
}

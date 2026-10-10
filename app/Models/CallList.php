<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallList extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'period_month',
        'status',
        'assigned_approver_id',
        'submitted_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignedApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_approver_id');
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(MasterCustomer::class, 'call_list_doctors', 'call_list_id', 'doctor_id');
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(CallListApprovalLog::class, 'call_list_id')->orderBy('created_at', 'asc');
    }

    public function callPlans(): HasMany
    {
        return $this->hasMany(CallPlan::class, 'call_list_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}

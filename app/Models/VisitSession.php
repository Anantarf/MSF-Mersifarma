<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VisitSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'mr_id',
        'plan_id',
        'doctor_id',
        'visit_date',
        'check_in_at',
        'check_out_at',
        'status',
        'draft_payload',
        'draft_revision',
        'cancellation_reason',
        'cancelled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'draft_payload' => 'array',
        ];
    }

    public function mr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mr_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CallPlan::class, 'plan_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(MasterCustomer::class, 'doctor_id');
    }

    public function callActual(): HasOne
    {
        return $this->hasOne(CallActual::class, 'visit_session_id');
    }
}

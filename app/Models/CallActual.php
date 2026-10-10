<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CallActual extends Model
{
    use HasFactory;

    protected $fillable = [
        'mr_id',
        'plan_id',
        'visit_session_id',
        'doctor_id',
        'visit_date',
        'check_in_at',
        'check_out_at',
        'visit_type',
        'photo_url',
        'signature_url',
        'visit_reason',
        'deviation_reason',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
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

    public function visitSession(): BelongsTo
    {
        return $this->belongsTo(VisitSession::class, 'visit_session_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(MasterCustomer::class, 'doctor_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'call_actual_products', 'call_actual_id', 'product_id');
    }
}

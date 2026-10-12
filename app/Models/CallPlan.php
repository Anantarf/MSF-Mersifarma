<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CallPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_list_id',
        'mr_id',
        'doctor_id',
        'plan_date',
        'planned_time',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'reschedule_reason',
        'rescheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'cancelled_at' => 'datetime',
            'rescheduled_at' => 'datetime',
        ];
    }

    public function callList(): BelongsTo
    {
        return $this->belongsTo(CallList::class, 'call_list_id');
    }

    public function mr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mr_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(MasterCustomer::class, 'doctor_id');
    }

    public function callActual(): HasOne
    {
        return $this->hasOne(CallActual::class, 'plan_id');
    }

    public function visitSession(): HasOne
    {
        return $this->hasOne(VisitSession::class, 'plan_id');
    }

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Memeriksa apakah MR memiliki Call Plan aktif (berstatus scheduled dan belum lewat/basi)
     * untuk dokter tertentu dalam periode bulan yang sama dengan tanggal referensi.
     */
    public static function hasActiveSchedule(int $mrId, int $doctorId, string $referenceDate): bool
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $periodStart = Carbon::parse($referenceDate)->startOfMonth()->toDateString();
        $periodEnd = Carbon::parse($referenceDate)->endOfMonth()->toDateString();
        $startDate = max($periodStart, $today);

        if ($startDate > $periodEnd) {
            return false;
        }

        return static::where('mr_id', $mrId)
            ->where('doctor_id', $doctorId)
            ->where('status', self::STATUS_SCHEDULED)
            ->whereBetween('plan_date', [$startDate, $periodEnd])
            ->exists();
    }
}

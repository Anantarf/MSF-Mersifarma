<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $title
 * @property string $district_name
 * @property string|null $specialization
 * @property int $duration_months
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $status
 * @property int $created_by
 * @property int|null $approved_by
 * @property string|null $rejection_reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ManagerialCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'district_name',
        'specialization',
        'duration_months',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'approved_by',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_months' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_products', 'campaign_id', 'product_id');
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(MasterCustomer::class, 'campaign_doctors', 'campaign_id', 'doctor_id')
            ->withPivot('target_visits');
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    public function scopeForDistrict(Builder $query, string $district): Builder
    {
        return $query->where('district_name', $district);
    }
}

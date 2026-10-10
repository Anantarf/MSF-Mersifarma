<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'specialization',
        'address',
        'phone',
    ];

    public function callLists(): BelongsToMany
    {
        return $this->belongsToMany(CallList::class, 'call_list_doctors', 'doctor_id', 'call_list_id');
    }

    public function callPlans(): HasMany
    {
        return $this->hasMany(CallPlan::class, 'doctor_id');
    }

    public function callActuals(): HasMany
    {
        return $this->hasMany(CallActual::class, 'doctor_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallListDoctor extends Model
{
    public $timestamps = false;
    protected $table = 'call_list_doctors';

    protected $fillable = [
        'call_list_id',
        'doctor_id',
    ];

    public function callList(): BelongsTo
    {
        return $this->belongsTo(CallList::class, 'call_list_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(MasterCustomer::class, 'doctor_id');
    }
}

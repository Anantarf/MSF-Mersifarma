<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallListApprovalLog extends Model
{
    public $timestamps = false;
    protected $table = 'call_list_approval_logs';

    protected $fillable = [
        'call_list_id',
        'actor_id',
        'event',
        'event_origin',
        'from_status',
        'to_status',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function callList(): BelongsTo
    {
        return $this->belongsTo(CallList::class, 'call_list_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallActualProduct extends Model
{
    public $timestamps = false;
    protected $table = 'call_actual_products';

    protected $fillable = [
        'call_actual_id',
        'product_id',
    ];

    public function callActual(): BelongsTo
    {
        return $this->belongsTo(CallActual::class, 'call_actual_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

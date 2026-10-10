<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
    ];

    public function callActuals(): BelongsToMany
    {
        return $this->belongsToMany(CallActual::class, 'call_actual_products', 'product_id', 'call_actual_id');
    }
}

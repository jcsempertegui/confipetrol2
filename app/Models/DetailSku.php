<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailSku extends Model
{
    protected $fillable = [
        'detail_type',
        'detail_id',
        'sku_id',
        'quantity',
    ];

    public function detail()
    {
        return $this->morphTo();
    }

    public function productSku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }
}
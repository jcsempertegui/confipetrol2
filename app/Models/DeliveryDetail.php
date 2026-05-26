<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'product_id',
        'warehouse_id',
        'sku_id',
        'quantity',
        'observations',
        'created_at',
        'updated_at',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}

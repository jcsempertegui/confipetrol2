<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemitoDetail extends Model
{
    use HasFactory;

    protected $table = 'remito_details';

    protected $fillable = [
        'remito_id',
        'product_id',
        'warehouse_id',
        'sku_id',
        'quantity',
        'observations',
        'created_at',
        'updated_at',
    ];

    public function remito()
    {
        return $this->belongsTo(Remito::class);
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

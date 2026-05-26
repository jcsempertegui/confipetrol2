<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventorie extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_price',
        'sale_price',
        'profit',
        'stock_lot',
        'stock_nolot',
        'stock',
        'product_id',
        'warehouse_id',
        'status',
    ];
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

}
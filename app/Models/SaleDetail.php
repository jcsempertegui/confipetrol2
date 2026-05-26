<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleDetail extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_id',
        'warehouse_id',
        'quantity',
        'purchase_price',
        'sale_price',
        'price_type',
        'wholesale_min_quantity',
        'subtotal',
        'discount',
        'observations',
        'employee_id',
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\DetailLot;
use App\Models\DetailSku;

class SaleDetail extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_id',
        'warehouse_id',
        'variant_id',
        'quantity',
        'purchase_price',
        'sale_price',
        'price_type',
        'wholesale_min_quantity',
        'subtotal',
        'discount',
        'observations',
        'is_takeaway',
        'employee_id',
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function detailLots()
    {
        return $this->morphMany(DetailLot::class, 'detail');
    }

    public function detailSkus()
    {
        return $this->morphMany(DetailSku::class, 'detail');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function additionals()
    {
        return $this->hasMany(OrderDetailAdditional::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
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
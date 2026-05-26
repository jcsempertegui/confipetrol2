<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'features',
        'image',
        'type',
        'has_loyalty',
        'loyalty_req_qty',
        'lote',
        'minimum_stock',
        'status',
        'categorie_id',
        'brand_id',
        'unit_id',
        'production_area_id',
    ];

    public function categories()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function brands()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function units()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function productionArea()
    {
        return $this->belongsTo(ProductionArea::class, 'production_area_id');
    }

    public function inventories()
    {
        return $this->hasOne(Inventorie::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }
    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }
}
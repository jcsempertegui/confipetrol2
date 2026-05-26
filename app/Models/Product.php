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

    public function inventories()
    {
        return $this->hasMany(Inventorie::class);
    }

}

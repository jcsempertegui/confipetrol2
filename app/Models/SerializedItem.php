<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerializedItem extends Model
{
    protected $fillable = ['product_variant_id', 'serial_number', 'status'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function attributeValues()
    {
        return $this->hasMany(SerializedItemAttributeValue::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantAttributeValue extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_variant_id', 'product_attribute_id', 'value'];
}

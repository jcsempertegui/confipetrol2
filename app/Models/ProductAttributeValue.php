<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'product_attribute_id', 'value'];
}

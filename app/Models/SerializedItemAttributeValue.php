<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerializedItemAttributeValue extends Model
{
    public $timestamps = false;

    protected $fillable = ['serialized_item_id', 'product_attribute_id', 'value'];
}

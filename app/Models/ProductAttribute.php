<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = ['name', 'code', 'type', 'scope', 'options', 'status'];

    protected $casts = ['options' => 'array', 'status' => 'boolean'];

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withPivot(['required', 'position']);
    }
}

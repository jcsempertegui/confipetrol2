<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_number',
        'quantity',
        'expiration_date',
        'product_id',
        'branch_id',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branche::class);
    }


}
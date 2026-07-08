<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'type', 'previous_stock', 'new_stock', 'difference',
        'reason', 'cost', 'total', 'product_id', 'lot_id', 'branch_id', 'user_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

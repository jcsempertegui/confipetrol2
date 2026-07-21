<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLotAllocation extends Model
{
    protected $fillable = ['inventory_lot_id', 'dispatch_note_item_id', 'delivery_item_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function lot()
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}

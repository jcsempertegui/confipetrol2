<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    protected $fillable = ['delivery_id', 'product_variant_id', 'quantity', 'notes'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function serializedItems()
    {
        return $this->belongsToMany(SerializedItem::class, 'delivery_serialized_items');
    }

    public function lotAllocations()
    {
        return $this->hasMany(InventoryLotAllocation::class);
    }
}

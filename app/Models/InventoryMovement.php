<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los movimientos del Kardex son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los movimientos del Kardex no pueden eliminarse.'));
    }

    protected $fillable = ['product_variant_id', 'serialized_item_id', 'dispatch_note_id', 'delivery_id', 'reversal_of_id', 'movement_type', 'quantity', 'occurred_at', 'created_by'];

    protected $casts = ['quantity' => 'decimal:3', 'occurred_at' => 'datetime'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function serializedItem()
    {
        return $this->belongsTo(SerializedItem::class);
    }

    public function dispatchNote()
    {
        return $this->belongsTo(DispatchNote::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

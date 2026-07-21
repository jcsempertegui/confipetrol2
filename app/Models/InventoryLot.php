<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLot extends Model
{
    protected $fillable = ['product_variant_id', 'lot_number', 'expiration_date', 'received_at', 'is_legacy', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['expiration_date' => 'date', 'received_at' => 'date', 'is_legacy' => 'boolean', 'status' => 'boolean'];
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCurrentStockAttribute(): float
    {
        return (float) $this->movements()->sum('quantity');
    }
}

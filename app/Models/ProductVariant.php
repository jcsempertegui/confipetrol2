<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'sku', 'name', 'minimum_stock', 'status'];

    protected $casts = ['minimum_stock' => 'decimal:3', 'status' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(VariantAttributeValue::class);
    }

    public function serializedItems()
    {
        return $this->hasMany(SerializedItem::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getCurrentStockAttribute(): float
    {
        return (float) $this->inventoryMovements()->sum('quantity');
    }

    public function getAttributeSummaryAttribute(): string
    {
        $this->loadMissing(['product.attributeValues.attribute', 'attributeValues.attribute']);

        return $this->product->attributeValues
            ->concat($this->attributeValues)
            ->filter(fn ($value) => filled($value->value) && $value->attribute)
            ->map(fn ($value) => $value->attribute->name.': '.$value->value)
            ->unique()
            ->join(' · ');
    }
}

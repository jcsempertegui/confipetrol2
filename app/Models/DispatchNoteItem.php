<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchNoteItem extends Model
{
    protected $fillable = ['dispatch_note_id', 'product_variant_id', 'quantity', 'notes'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function dispatchNote()
    {
        return $this->belongsTo(DispatchNote::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function serializedItems()
    {
        return $this->belongsToMany(SerializedItem::class, 'dispatch_note_serialized_items');
    }
}

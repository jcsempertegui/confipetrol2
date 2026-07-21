<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $fillable = ['category_id', 'code', 'name', 'description', 'unit', 'tracking_type', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function getUsesExpirationLotsAttribute(): bool
    {
        if ($this->tracking_type !== 'bulk') {
            return false;
        }

        return $this->category->attributes()->get()->contains(function (ProductAttribute $attribute) {
            if (! $attribute->status || $attribute->type !== 'date') {
                return false;
            }

            $identity = mb_strtolower($attribute->code.' '.$attribute->name);

            return str_contains($identity, 'venc') || str_contains($identity, 'caduc') || str_contains($identity, 'expir');
        });
    }

    public function getCatalogExpirationDateAttribute(): ?string
    {
        $expirationAttributeIds = $this->category->attributes()->get()
            ->filter(function (ProductAttribute $attribute) {
                if ($attribute->type !== 'date') {
                    return false;
                }
                $identity = mb_strtolower($attribute->code.' '.$attribute->name);

                return str_contains($identity, 'venc') || str_contains($identity, 'caduc') || str_contains($identity, 'expir');
            })->pluck('id');

        if ($expirationAttributeIds->isEmpty()) {
            return null;
        }

        $variantValue = DB::table('variant_attribute_values')
            ->join('product_variants', 'product_variants.id', '=', 'variant_attribute_values.product_variant_id')
            ->where('product_variants.product_id', $this->id)
            ->whereIn('variant_attribute_values.product_attribute_id', $expirationAttributeIds)
            ->whereNotNull('variant_attribute_values.value')
            ->value('variant_attribute_values.value');

        return $variantValue ?: $this->attributeValues()
            ->whereIn('product_attribute_id', $expirationAttributeIds)
            ->whereNotNull('value')
            ->value('value');
    }
}

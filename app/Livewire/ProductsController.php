<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\SerializedItem;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsController extends Component
{
    use AuditLog, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $productId;

    public $category_id = '';

    public $code = '';

    public $name = '';

    public $description = '';

    public $unit = 'unidad';

    public $tracking_type = 'bulk';

    public $status = 1;

    public $searchTerm = '';

    public array $productValues = [];

    public array $variants = [];

    public function mount(): void
    {
        $this->addVariant();
    }

    public function render()
    {
        $products = Product::with(['category', 'variants.attributeValues', 'variants.serializedItems', 'attributeValues'])
            ->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', '%'.$this->searchTerm.'%')->orWhere('code', 'like', '%'.$this->searchTerm.'%')->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', '%'.$this->searchTerm.'%'))))
            ->latest()->paginate(15);
        $categories = Category::with(['attributes' => fn ($q) => $q->where('product_attributes.status', true)])->where('status', true)->orderBy('name')->get();

        $selected = $categories->firstWhere('id', (int) $this->category_id);
        $codePreview = $selected ? Str::upper($selected->code).'-'.str_pad((string) $selected->next_product_number, 4, '0', STR_PAD_LEFT) : null;

        return view('livewire.products.products', compact('products', 'categories', 'codePreview'))->extends('layouts.theme.app');
    }

    public function updatedSearchTerm(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->productValues = [];
        foreach ($this->variants as &$v) {
            $v['values'] = [];
        }
        $this->variants = [];
        $this->addVariant();
    }

    public function addVariant(): void
    {
        $this->variants[] = ['id' => null, 'sku' => '', 'name' => '', 'minimum_stock' => 0, 'values' => [], 'serials' => ''];
    }

    public function removeVariant(int $index): void
    {
        if (count($this->variants) > 1) {
            array_splice($this->variants, $index, 1);
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->productId ? 'editar-producto' : 'crear-producto'), 403);
        $this->code = Str::upper(trim($this->code));
        $rules = [
            'category_id' => ['required', Rule::exists('categories', 'id')->where('status', true)], 'code' => ['nullable', 'string', 'max:80', 'regex:/^[A-Z0-9][A-Z0-9._\/-]*$/', Rule::unique('products')->ignore($this->productId)],
            'name' => 'required|string|max:200', 'description' => 'nullable|string|max:2000', 'unit' => ['required', 'string', 'max:40', 'regex:/^[\pL\pN .\/-]+$/u'], 'tracking_type' => 'required|in:bulk,serialized', 'status' => 'boolean',
            'variants' => 'required|array|min:1', 'variants.*.name' => 'nullable|string|max:150',
            'variants.*.minimum_stock' => 'nullable|numeric|min:0|max:999999999',
        ];
        $this->validate($rules);
        if ($this->productId && (int) Product::findOrFail($this->productId)->category_id !== (int) $this->category_id) {
            $this->addError('category_id', 'No se puede cambiar la categoría de un producto existente porque afectaría sus atributos y trazabilidad.');

            return;
        }
        $category = Category::with('attributes')->findOrFail($this->category_id);
        $hasVariants = $category->attributes->where('scope', 'variant')->isNotEmpty();
        $unitAttribute = $category->attributes->firstWhere('scope', 'unit');
        $this->tracking_type = $unitAttribute ? 'serialized' : 'bulk';
        foreach ($category->attributes as $attribute) {
            if ($attribute->pivot->required && $attribute->scope === 'product' && blank($this->productValues[$attribute->id] ?? null)) {
                $this->addError('productValues.'.$attribute->id, 'Este atributo es obligatorio.');
            }
            if ($attribute->scope === 'product' && filled($this->productValues[$attribute->id] ?? null)) {
                $this->validateAttributeValue($attribute, $this->productValues[$attribute->id], 'productValues.'.$attribute->id);
            }
            if ($attribute->scope === 'variant') {
                foreach ($this->variants as $i => $variant) {
                    if ($attribute->pivot->required && blank($variant['values'][$attribute->id] ?? null)) {
                        $this->addError("variants.$i.values.$attribute->id", 'Este atributo es obligatorio.');
                    }
                    if (filled($variant['values'][$attribute->id] ?? null)) {
                        $this->validateAttributeValue($attribute, $variant['values'][$attribute->id], "variants.$i.values.$attribute->id");
                    }
                }
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->code = $this->code ?: $this->reserveCode($category);
        if (! $hasVariants) {
            $this->variants = [[
                'id' => $this->variants[0]['id'] ?? null,
                'sku' => $this->code,
                'name' => '', 'minimum_stock' => $this->variants[0]['minimum_stock'] ?? 0, 'values' => [], 'serials' => $this->variants[0]['serials'] ?? '',
            ]];
        }
        foreach ($this->variants as $index => &$variant) {
            if (blank($variant['sku'])) {
                $suffix = collect($variant['values'] ?? [])->filter()->map(fn ($value) => Str::upper(Str::slug((string) $value, '')))->join('-');
                $variant['sku'] = $this->code.'-'.($suffix ?: 'V'.($index + 1));
            }
        }
        unset($variant);
        foreach ($this->variants as $i => $variant) {
            $uniqueSku = Rule::unique('product_variants', 'sku')->ignore($variant['id'] ?? null);
            if ($this->productId) {
                $uniqueSku->where(fn ($query) => $query->where('product_id', '!=', $this->productId));
            }
            $rules["variants.$i.sku"] = ['required', 'string', 'max:100', 'distinct', $uniqueSku];
        }
        $this->validate($rules);
        if ($unitAttribute) {
            $allSerials = collect($this->variants)->flatMap(fn ($row) => $this->parseSerials($row['serials'] ?? '', false));
            if ($unitAttribute->pivot->required && $allSerials->isEmpty()) {
                $this->addError('variants.0.serials', 'Registra al menos un '.$unitAttribute->name.'.');
            }
            if ($allSerials->count() !== $allSerials->unique()->count()) {
                $this->addError('variants.0.serials', 'Hay identificadores repetidos en el formulario.');
            }
            foreach ($allSerials as $serial) {
                if (mb_strlen($serial) > 150 || ! preg_match('/^[\pL\pN._\/-]+$/u', $serial)) {
                    $this->addError('variants.0.serials', 'Los identificadores admiten hasta 150 caracteres y solo letras, números, punto, guion, guion bajo o barra.');
                    break;
                }
            }
            $ownVariantIds = $this->productId ? Product::findOrFail($this->productId)->variants()->pluck('id') : collect();
            if (SerializedItem::whereIn('serial_number', $allSerials)->when($ownVariantIds->isNotEmpty(), fn ($query) => $query->whereNotIn('product_variant_id', $ownVariantIds))->exists()) {
                $this->addError('variants.0.serials', 'Uno de los identificadores ya está registrado en otro producto.');
            }
            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        $before = $this->productId ? $this->productSnapshot(Product::findOrFail($this->productId)) : null;
        DB::transaction(function () use ($category, $before) {
            $product = Product::updateOrCreate(['id' => $this->productId], $this->only(['category_id', 'code', 'name', 'description', 'unit', 'tracking_type', 'status']));
            foreach ($category->attributes->where('scope', 'product') as $attribute) {
                $product->attributeValues()->updateOrCreate(['product_attribute_id' => $attribute->id], ['value' => $this->productValues[$attribute->id] ?? null]);
            }
            $kept = [];
            foreach ($this->variants as $row) {
                $variant = filled($row['id'] ?? null)
                    ? $product->variants()->whereKey($row['id'])->first()
                    : $product->variants()->where('sku', $row['sku'])->first();
                $variant ??= $product->variants()->make();
                $variant->fill(['sku' => $row['sku'], 'name' => $row['name'] ?: null, 'minimum_stock' => $product->tracking_type === 'serialized' ? 0 : ($row['minimum_stock'] ?? 0), 'status' => true])->save();
                $kept[] = $variant->id;
                foreach ($category->attributes->where('scope', 'variant') as $attribute) {
                    $variant->attributeValues()->updateOrCreate(['product_attribute_id' => $attribute->id], ['value' => $row['values'][$attribute->id] ?? null]);
                }
                if ($product->tracking_type === 'serialized') {
                    $serials = $this->parseSerials($row['serials'] ?? '');
                    $variant->serializedItems()->whereNotIn('serial_number', $serials)->update(['status' => 'inactive']);
                    foreach ($serials as $serial) {
                        $item = SerializedItem::where('serial_number', $serial)->first() ?? new SerializedItem;
                        $item->fill(['product_variant_id' => $variant->id, 'serial_number' => $serial, 'status' => 'available'])->save();
                    }
                }
            }
            $product->variants()->whereNotIn('id', $kept)->update(['status' => false]);
            $this->logActivity('PRODUCTOS', $this->productId ? 'EDITAR' : 'CREAR', 'Producto '.$product->name, $product->id, $before, $this->productSnapshot($product));
        });
        $this->resetForm();
        $this->dispatch('alert', 'Producto guardado correctamente.', 'success');
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('editar-producto'), 403);
        $p = Product::with(['attributeValues', 'variants.attributeValues', 'variants.serializedItems'])->findOrFail($id);
        foreach (['category_id', 'code', 'name', 'description', 'unit', 'tracking_type', 'status'] as $field) {
            $this->{$field} = $p->{$field};
        }
        $this->productId = $id;
        $this->productValues = $p->attributeValues->pluck('value', 'product_attribute_id')->all();
        $this->variants = $p->variants->where('status', true)->map(fn ($v) => ['id' => $v->id, 'sku' => $v->sku, 'name' => $v->name ?? '', 'minimum_stock' => (float) $v->minimum_stock, 'values' => $v->attributeValues->pluck('value', 'product_attribute_id')->all(), 'serials' => $v->serializedItems->where('status', '!=', 'inactive')->pluck('serial_number')->join("\n")])->values()->all();
    }

    public function toggle(int $id): void
    {
        abort_unless(auth()->user()->can('eliminar-producto'), 403);
        $p = Product::findOrFail($id);
        $before = ['status' => (bool) $p->status];
        $p->update(['status' => ! $p->status]);
        $this->logActivity('PRODUCTOS', $p->status ? 'RESTAURAR' : 'ELIMINAR', 'Cambio de estado del producto '.$p->name, $p->id, $before, ['status' => (bool) $p->status]);
    }

    public function resetForm(): void
    {
        $this->reset(['productId', 'category_id', 'code', 'name', 'description', 'productValues', 'variants']);
        $this->tracking_type = 'bulk';
        $this->unit = 'unidad';
        $this->status = 1;
        $this->addVariant();
        $this->resetValidation();
    }

    private function reserveCode(Category $category): string
    {
        return DB::transaction(function () use ($category) {
            $locked = Category::whereKey($category->id)->lockForUpdate()->firstOrFail();
            $number = $locked->next_product_number;
            do {
                $code = Str::upper($locked->code).'-'.str_pad((string) $number++, 4, '0', STR_PAD_LEFT);
            } while (Product::where('code', $code)->exists());
            $locked->update(['next_product_number' => $number]);

            return $code;
        });
    }

    private function parseSerials(string $serials, bool $unique = true)
    {
        $values = collect(preg_split('/[\r\n,]+/', $serials))->map(fn ($value) => trim($value))->filter();

        return ($unique ? $values->unique() : $values)->values();
    }

    private function validateAttributeValue($attribute, mixed $value, string $field): void
    {
        $valid = match ($attribute->type) {
            'number' => is_numeric($value),
            'date' => strtotime((string) $value) !== false,
            'select' => in_array((string) $value, array_map('strval', $attribute->options ?? []), true),
            'boolean' => in_array($value, [0, 1, '0', '1', true, false], true),
            default => is_scalar($value) && mb_strlen((string) $value) <= 2000,
        };
        if (! $valid) {
            $this->addError($field, 'El valor no corresponde al tipo configurado para '.$attribute->name.'.');
        }
    }

    private function productSnapshot(Product $product): array
    {
        $product->load(['category', 'category.attributes', 'attributeValues', 'variants.attributeValues', 'variants.serializedItems']);
        $attributeNames = $product->category->attributes->pluck('name', 'id');

        return [
            'categoría' => $product->category->name,
            'código' => $product->code,
            'nombre' => $product->name,
            'unidad' => $product->unit,
            'descripción' => $product->description,
            'estado' => (bool) $product->status,
            'atributos' => $product->attributeValues->mapWithKeys(fn ($value) => [$attributeNames[$value->product_attribute_id] ?? $value->product_attribute_id => $value->value])->all(),
            'variantes' => $product->variants->map(fn ($variant) => [
                'sku' => $variant->sku,
                'nombre' => $variant->name,
                'stock_mínimo' => (float) $variant->minimum_stock,
                'atributos' => $variant->attributeValues->mapWithKeys(fn ($value) => [$attributeNames[$value->product_attribute_id] ?? $value->product_attribute_id => $value->value])->all(),
                'identificadores_únicos' => $variant->serializedItems->mapWithKeys(fn ($item) => [$item->serial_number => $item->status])->all(),
            ])->all(),
        ];
    }
}

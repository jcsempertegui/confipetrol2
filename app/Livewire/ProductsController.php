<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
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

        return view('livewire.products.products', compact('products', 'categories'))->extends('layouts.theme.app');
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
        $this->variants[] = ['id' => null, 'sku' => '', 'name' => '', 'values' => [], 'serials' => ''];
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
        $rules = [
            'category_id' => 'required|exists:categories,id', 'code' => ['nullable', 'string', 'max:80', Rule::unique('products')->ignore($this->productId)],
            'name' => 'required|string|max:200', 'description' => 'nullable|string|max:2000', 'tracking_type' => 'required|in:bulk,serialized', 'status' => 'boolean',
            'variants' => 'required|array|min:1', 'variants.*.name' => 'nullable|string|max:150',
        ];
        $category = Category::with('attributes')->findOrFail($this->category_id);
        $hasVariants = $category->attributes->where('scope', 'variant')->isNotEmpty();
        $this->code = $this->code ?: $this->makeCode($category);
        if (! $hasVariants) {
            $this->variants = [[
                'id' => $this->variants[0]['id'] ?? null,
                'sku' => $this->code,
                'name' => '', 'values' => [], 'serials' => '',
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
            $rules["variants.$i.sku"] = ['required', 'string', 'max:100', 'distinct', Rule::unique('product_variants', 'sku')->ignore($variant['id'] ?? null)];
        }
        $this->validate($rules);
        foreach ($category->attributes as $attribute) {
            if (! $attribute->pivot->required) {
                continue;
            }
            if ($attribute->scope === 'product' && blank($this->productValues[$attribute->id] ?? null)) {
                $this->addError('productValues.'.$attribute->id, 'Este atributo es obligatorio.');
            }
            if ($attribute->scope === 'variant') {
                foreach ($this->variants as $i => $variant) {
                    if (blank($variant['values'][$attribute->id] ?? null)) {
                        $this->addError("variants.$i.values.$attribute->id", 'Este atributo es obligatorio.');
                    }
                }
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $before = $this->productId ? $this->productSnapshot(Product::findOrFail($this->productId)) : null;
        DB::transaction(function () use ($category, $before) {
            $product = Product::updateOrCreate(['id' => $this->productId], $this->only(['category_id', 'code', 'name', 'description', 'tracking_type', 'status']));
            foreach ($category->attributes->where('scope', 'product') as $attribute) {
                $product->attributeValues()->updateOrCreate(['product_attribute_id' => $attribute->id], ['value' => $this->productValues[$attribute->id] ?? null]);
            }
            $kept = [];
            foreach ($this->variants as $row) {
                $variant = $product->variants()->updateOrCreate(['id' => $row['id'] ?? null], ['sku' => $row['sku'], 'name' => $row['name'] ?: null, 'status' => true]);
                $kept[] = $variant->id;
                foreach ($category->attributes->where('scope', 'variant') as $attribute) {
                    $variant->attributeValues()->updateOrCreate(['product_attribute_id' => $attribute->id], ['value' => $row['values'][$attribute->id] ?? null]);
                }
                if ($product->tracking_type === 'serialized') {
                    $serials = collect(preg_split('/[\r\n,]+/', $row['serials'] ?? ''))->map(fn ($v) => trim($v))->filter()->unique();
                    foreach ($serials as $serial) {
                        $variant->serializedItems()->firstOrCreate(['serial_number' => $serial]);
                    }
                }
            }
            $product->variants()->whereNotIn('id', $kept)->delete();
            $this->logActivity('PRODUCTOS', $this->productId ? 'EDITAR' : 'CREAR', 'Producto '.$product->name, $product->id, $before, $this->productSnapshot($product));
        });
        $this->resetForm();
        $this->dispatch('alert', 'Producto guardado correctamente.', 'success');
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('editar-producto'), 403);
        $p = Product::with(['attributeValues', 'variants.attributeValues', 'variants.serializedItems'])->findOrFail($id);
        foreach (['category_id', 'code', 'name', 'description', 'tracking_type', 'status'] as $field) {
            $this->{$field} = $p->{$field};
        }
        $this->productId = $id;
        $this->productValues = $p->attributeValues->pluck('value', 'product_attribute_id')->all();
        $this->variants = $p->variants->map(fn ($v) => ['id' => $v->id, 'sku' => $v->sku, 'name' => $v->name ?? '', 'values' => $v->attributeValues->pluck('value', 'product_attribute_id')->all(), 'serials' => $v->serializedItems->pluck('serial_number')->join("\n")])->all();
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
        $this->status = 1;
        $this->addVariant();
        $this->resetValidation();
    }

    private function makeCode(Category $category): string
    {
        $base = Str::upper($category->code).'-';
        $lastNumber = Product::where('category_id', $category->id)->pluck('code')
            ->map(fn ($code) => preg_match('/^'.preg_quote($base, '/').'(\d+)$/', $code, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;
        $code = $base.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return $code;
    }

    private function productSnapshot(Product $product): array
    {
        $product->load(['category', 'category.attributes', 'attributeValues', 'variants.attributeValues', 'variants.serializedItems']);
        $attributeNames = $product->category->attributes->pluck('name', 'id');

        return [
            'categoría' => $product->category->name,
            'código' => $product->code,
            'nombre' => $product->name,
            'descripción' => $product->description,
            'estado' => (bool) $product->status,
            'atributos' => $product->attributeValues->mapWithKeys(fn ($value) => [$attributeNames[$value->product_attribute_id] ?? $value->product_attribute_id => $value->value])->all(),
            'variantes' => $product->variants->map(fn ($variant) => [
                'sku' => $variant->sku,
                'nombre' => $variant->name,
                'atributos' => $variant->attributeValues->mapWithKeys(fn ($value) => [$attributeNames[$value->product_attribute_id] ?? $value->product_attribute_id => $value->value])->all(),
                'números_de_serie' => $variant->serializedItems->pluck('serial_number')->all(),
            ])->all(),
        ];
    }
}

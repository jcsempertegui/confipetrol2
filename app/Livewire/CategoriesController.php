<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\ProductAttribute;
use App\Traits\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CategoriesController extends Component
{
    use AuditLog;

    public $categoryId;

    public $name = '';

    public $code = '';

    public $description = '';

    public $status = 1;

    public $attributeId;

    public $attributeName = '';

    public $attributeCode = '';

    public $attributeType = 'text';

    public $attributeScope = 'product';

    public $attributeOptions = '';

    public $attributeStatus = 1;

    public $selectedCategoryId;

    public $attributeRequired = true;

    public function render()
    {
        return view('livewire.categories.categories', [
            'categories' => Category::with('attributes')->withCount('products')->orderBy('name')->get(),
            'selectedCategory' => $this->selectedCategoryId ? Category::with('attributes')->find($this->selectedCategoryId) : null,
        ])->extends('layouts.theme.app');
    }

    public function saveCategory(): void
    {
        abort_unless(auth()->user()->can($this->categoryId ? 'editar-categoria' : 'crear-categoria'), 403);
        $this->code = Str::upper(trim($this->code));
        $data = $this->validate([
            'name' => 'required|string|max:150',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('categories')->ignore($this->categoryId)],
            'description' => 'nullable|string|max:1000', 'status' => 'boolean',
        ]);
        $this->code = $this->code ?: $this->makeCategoryCode();
        $data['code'] = $this->code;
        $before = $this->categoryId ? Category::findOrFail($this->categoryId)->only(['name', 'code', 'description', 'status']) : null;
        $category = Category::updateOrCreate(['id' => $this->categoryId], $data);
        $this->selectedCategoryId = $category->id;
        $this->logActivity('CATEGORIAS', $this->categoryId ? 'EDITAR' : 'CREAR', 'Categoría '.$category->name, $category->id, $before, $category->only(['name', 'code', 'description', 'status']));
        $this->resetCategory();
        $this->dispatch('alert', 'Categoría guardada correctamente.', 'success');
    }

    public function editCategory(int $id): void
    {
        abort_unless(auth()->user()->can('editar-categoria'), 403);
        $category = Category::with('attributes')->findOrFail($id);
        $this->categoryId = $id;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
        $this->status = (int) $category->status;
        $this->selectedCategoryId = $category->id;
    }

    public function toggleCategory(int $id): void
    {
        abort_unless(auth()->user()->can('eliminar-categoria'), 403);
        $category = Category::findOrFail($id);
        $before = ['status' => (bool) $category->status];
        $category->update(['status' => ! $category->status]);
        $this->logActivity('CATEGORIAS', $category->status ? 'RESTAURAR' : 'ELIMINAR', 'Cambio de estado de la categoría '.$category->name, $category->id, $before, ['status' => (bool) $category->status]);
    }

    public function saveAttribute(): void
    {
        abort_unless(auth()->user()->can('gestionar-atributos'), 403);
        abort_unless($this->selectedCategoryId, 422, 'Seleccione una categoría.');
        $category = Category::findOrFail($this->selectedCategoryId);
        $this->attributeCode = Str::lower(trim($this->attributeCode ?: $category->code.'-'.Str::slug($this->attributeName)));
        $data = $this->validate([
            'attributeName' => 'required|string|max:150',
            'attributeCode' => ['required', 'string', 'max:50', Rule::unique('product_attributes', 'code')->ignore($this->attributeId)],
            'attributeType' => 'required|in:text,number,select,boolean,date',
            'attributeScope' => 'required|in:product,variant,unit',
            'attributeOptions' => 'nullable|string|max:2000', 'attributeStatus' => 'boolean',
        ]);
        if (Str::lower(trim($this->attributeName)) === 'color' || Str::lower(trim($this->attributeCode)) === 'color' || str_ends_with(Str::lower(trim($this->attributeCode)), '-color')) {
            $this->addError('attributeName', 'El campo Color fue retirado del catálogo y no puede volver a agregarse.');

            return;
        }
        if ($this->attributeScope === 'unit') {
            $existingUnitAttribute = $category->attributes()->where('scope', 'unit')->where('product_attributes.id', '!=', $this->attributeId)->exists();
            if ($existingUnitAttribute) {
                $this->addError('attributeScope', 'La categoría ya tiene un identificador único por unidad.');

                return;
            }
            $data['attributeType'] = 'text';
        }
        $options = $this->attributeType === 'select' ? collect(explode(',', $this->attributeOptions))->map(fn ($v) => trim($v))->filter()->values()->all() : null;
        if ($this->attributeId) {
            $current = ProductAttribute::findOrFail($this->attributeId);
            $isUsed = $current->productValues()->exists() || $current->variantValues()->exists() || ($current->scope === 'unit' && $current->categories()->whereHas('products.variants.serializedItems')->exists());
            if ($isUsed && ($current->type !== $data['attributeType'] || $current->scope !== $data['attributeScope'])) {
                $this->addError('attributeScope', 'No se puede cambiar el tipo o uso porque este atributo ya tiene información registrada. Puede desactivarlo y crear otro.');

                return;
            }
            if ($isUsed && $current->type === 'select') {
                $usedValues = $current->productValues()->pluck('value')->merge($current->variantValues()->pluck('value'))->filter()->unique();
                if ($usedValues->diff($options ?? [])->isNotEmpty()) {
                    $this->addError('attributeOptions', 'No puede retirar opciones que ya están utilizadas por productos.');

                    return;
                }
            }
        }
        $before = $this->attributeId ? $this->attributeSnapshot(ProductAttribute::findOrFail($this->attributeId), $category) : null;
        $attribute = ProductAttribute::updateOrCreate(['id' => $this->attributeId], [
            'name' => $data['attributeName'], 'code' => $data['attributeCode'], 'type' => $data['attributeType'],
            'scope' => $data['attributeScope'], 'options' => $options, 'status' => $data['attributeStatus'],
        ]);
        $category->attributes()->syncWithoutDetaching([$attribute->id => [
            'required' => (bool) $this->attributeRequired,
            'position' => $category->attributes()->count(),
        ]]);
        $category->attributes()->updateExistingPivot($attribute->id, ['required' => (bool) $this->attributeRequired]);
        $this->logActivity('CATEGORIAS', $this->attributeId ? 'EDITAR' : 'CREAR', 'Atributo '.$attribute->name.' de la categoría '.$category->name, $attribute->id, $before, $this->attributeSnapshot($attribute->fresh(), $category));
        $this->resetAttribute();
        $this->dispatch('alert', 'Atributo guardado correctamente.', 'success');
    }

    public function editAttribute(int $id): void
    {
        abort_unless(auth()->user()->can('gestionar-atributos'), 403);
        $a = ProductAttribute::findOrFail($id);
        $this->attributeId = $id;
        $this->attributeName = $a->name;
        $this->attributeCode = $a->code;
        $this->attributeType = $a->type;
        $this->attributeScope = $a->scope;
        $this->attributeOptions = implode(', ', $a->options ?? []);
        $this->attributeStatus = (int) $a->status;
        $this->attributeRequired = (bool) Category::findOrFail($this->selectedCategoryId)->attributes()->whereKey($id)->firstOrFail()->pivot->required;
    }

    public function selectCategory(int $id): void
    {
        $this->selectedCategoryId = Category::findOrFail($id)->id;
        $this->resetAttribute();
    }

    public function removeAttribute(int $id): void
    {
        abort_unless(auth()->user()->can('gestionar-atributos'), 403);
        $category = Category::findOrFail($this->selectedCategoryId);
        $attribute = $category->attributes()->whereKey($id)->firstOrFail();
        $isUsed = $attribute->productValues()->exists() || $attribute->variantValues()->exists()
            || ($attribute->scope === 'unit' && $category->products()->whereHas('variants.serializedItems')->exists());
        if ($isUsed) {
            $this->dispatch('alert', 'Este atributo ya contiene información y no puede retirarse. Puede editarlo sin cambiar su tipo.', 'warning');

            return;
        }
        $before = $this->attributeSnapshot($attribute, $category);
        $category->attributes()->detach($id);
        $this->logActivity('CATEGORIAS', 'ELIMINAR', 'Atributo '.$attribute->name.' retirado de la categoría '.$category->name, $attribute->id, $before, null);
        $this->resetAttribute();
    }

    public function resetCategory(): void
    {
        $this->reset(['categoryId', 'name', 'code', 'description']);
        $this->status = 1;
        $this->resetValidation();
    }

    public function resetAttribute(): void
    {
        $this->reset(['attributeId', 'attributeName', 'attributeCode', 'attributeOptions']);
        $this->attributeType = 'text';
        $this->attributeScope = 'product';
        $this->attributeStatus = 1;
        $this->attributeRequired = true;
        $this->resetValidation();
    }

    private function attributeSnapshot(ProductAttribute $attribute, Category $category): array
    {
        $pivot = $category->attributes()->whereKey($attribute->id)->first()?->pivot;

        return [
            'categoría' => $category->name,
            'nombre' => $attribute->name,
            'tipo' => $attribute->type,
            'uso' => $attribute->scope,
            'opciones' => $attribute->options,
            'obligatorio' => (bool) ($pivot?->required ?? $this->attributeRequired),
            'estado' => (bool) $attribute->status,
        ];
    }

    private function makeCategoryCode(): string
    {
        $words = collect(preg_split('/\s+/', Str::ascii(trim($this->name))))
            ->filter(fn ($word) => ! in_array(strtolower($word), ['de', 'del', 'la', 'las', 'el', 'los', 'y'], true));
        $base = $words->count() > 1
            ? $words->map(fn ($word) => strtoupper(substr($word, 0, 1)))->join('')
            : strtoupper(substr($words->first() ?: 'CAT', 0, 3));
        $code = $base;
        $number = 2;
        while (Category::where('code', $code)->where('id', '!=', $this->categoryId)->exists()) {
            $code = $base.$number++;
        }

        return $code;
    }
}

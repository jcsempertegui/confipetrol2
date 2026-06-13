<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Categorie;
use App\Models\Unit;
use App\Models\Branche;
use App\Models\Inventorie;
use App\Models\ProductSku;
use App\Models\Kardex;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Models\Color;
use App\Models\Size;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductsController extends Component
{
    use WithPagination, WithFileUploads, AuditLog;

    protected $paginationTheme = 'bootstrap';

    public $code, $name, $features, $model, $image, $image_preview;
    public $categorie_id, $brand_id, $unit_id, $product_id;
    public $minimum_stock = 0, $initial_stock = 0;
    public $isEditMode = false;

    public $filter_category = '';
    public $filter_brand = '';
    public $filter_type = '';
    public $filter_status = '1';

    public $searchTerm;

    public $categories, $brands, $units;

    public $name_category, $name_brand, $name_unit, $unit_base_unit, $unit_factor;

    public $type = 0;
    public $lote = false;

    public $branch_id, $pos_type, $openAccordion = null;

    public $colors = [], $sizes = [];
    public $color_id = '', $size_id = '';
    public $skus = [];

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    protected $listeners = ['delete'];

    public function updatedPerPage()
    {
        $this->resetPage();
    }
    public function updatedFilterCategory()
    {
        $this->resetPage();
    }
    public function updatedFilterBrand()
    {
        $this->resetPage();
    }
    public function updatedFilterType()
    {
        $this->resetPage();
    }
    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedType($value)
    {
        if ($value != 0) {
            $this->lote = false;
        }
        if ($value != 3) {
            $this->skus = [];
            $this->color_id = '';
            $this->size_id = '';
        }
        if ($value == 1) {
            $this->categorie_id = null;
        } else {
            $this->model = '';
        }
    }

    public function clearFilters()
    {
        $this->filter_category = '';
        $this->filter_brand = '';
        $this->filter_type = '';
        $this->filter_status = '1';
        $this->resetPage();
    }

    private function getDefaultWarehouseId()
    {
        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        return $defaultWarehouse ? $defaultWarehouse->id : 1;
    }

    public function refreshData($branchId = null)
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $branch = Branche::find($this->branch_id) ?? Branche::first();
        $this->pos_type = $branch ? $branch->pos_type : 1;
        $this->resetPage();
    }

    public function toggleAccordion($id)
    {
        $this->openAccordion = $this->openAccordion === $id ? null : $id;
    }

    public function mount()
    {
        $this->categories = Categorie::where('status', 1)->orderBy('id', 'asc')->get();
        $this->brands = Brand::where('status', 1)->orderBy('id', 'asc')->get();
        $this->units = Unit::where('status', 1)->orderBy('id', 'asc')->get();
        $this->colors = Color::where('status', 1)->orderBy('id', 'asc')->get();
        $this->sizes = Size::where('status', 1)->orderBy('id', 'asc')->get();

        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $branch = Branche::find($this->branch_id) ?? Branche::first();
        $this->pos_type = $branch ? $branch->pos_type : 1;
    }

    public function render()
    {
        $warehouseId = $this->getDefaultWarehouseId();

        $products = Product::query()
            ->select([
                'products.id',
                'products.code',
                'products.name',
                'products.type',
                'products.status',
                'products.brand_id',
                'products.categorie_id',
                'products.unit_id',
                'products.minimum_stock',
                'products.image',
            ])
            ->with([
                'brands:id,name',
                'categories:id,name',
                'units:id,name',
                'inventories' => function ($query) use ($warehouseId) {
                    $query->select('product_id', 'warehouse_id')
                        ->where('warehouse_id', $warehouseId);
                },
            ])
            ->when($this->filter_status !== '', fn($q) => $q->where('products.status', $this->filter_status))
            ->when($this->filter_category, fn($q) => $q->where('products.categorie_id', $this->filter_category))
            ->when($this->filter_brand, fn($q) => $q->where('products.brand_id', $this->filter_brand))
            ->when($this->filter_type !== '', fn($q) => $q->where('products.type', $this->filter_type))
            ->when(strlen($this->searchTerm ?? '') > 0, function ($query) {
                $query->where(function ($q) {
                    $q->where('products.code', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('products.name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('brands', fn($q) => $q->where('name', 'like', '%' . $this->searchTerm . '%'))
                        ->orWhereHas('categories', fn($q) => $q->where('name', 'like', '%' . $this->searchTerm . '%'));
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.products.products', [
            'products' => $products,
            'startCount' => $products->total() - ($products->currentPage() - 1) * $products->perPage(),
        ])->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->code = '';
        $this->name = '';
        $this->features = '';
        $this->model = '';
        $this->categorie_id = null;
        $this->brand_id = null;
        $this->unit_id = null;
        $this->product_id = '';
        $this->minimum_stock = 0;
        $this->initial_stock = 0;
        $this->image = null;
        $this->image_preview = null;
        $this->type = 0;
        $this->lote = false;
        $this->skus = [];
        $this->color_id = '';
        $this->size_id = '';
        $this->openAccordion = null;
        $this->isEditMode = false;
        $this->dispatch('reset-image-preview');
    }

    public function storeOrUpdate()
    {
        ini_set('memory_limit', '256M');
        set_time_limit(120);

        $rules = [
            'code' => 'required|unique:products,code,' . ($this->isEditMode ? $this->product_id : ''),
            'name' => 'required|min:3',
            'minimum_stock' => 'required|numeric|min:0',
            'brand_id' => 'required|numeric',
            'unit_id' => 'required|numeric',
        ];

        if ($this->type == 1) {
            $rules['model'] = 'required|min:1';
            $rules['categorie_id'] = 'nullable';
        } else {
            $rules['categorie_id'] = 'required|numeric';
            $rules['model'] = 'nullable';
        }

        if (!$this->isEditMode) {
            $rules['initial_stock'] = 'nullable|numeric|min:0';
        }

        if ($this->image && is_object($this->image)) {
            $rules['image'] = 'nullable|image|max:20480';
        } else {
            $rules['image'] = 'nullable';
        }

        $messages = [
            'code.required' => 'El código es requerido',
            'code.unique' => 'El código ya está en uso',
            'name.required' => 'El producto es requerido',
            'name.min' => 'El nombre debe tener al menos 3 caracteres',
            'model.required' => 'El modelo es requerido',
            'minimum_stock.required' => 'El stock mínimo es requerido',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'La imagen es demasiado pesada (Máx 20MB).',
            'categorie_id.required' => 'La categoría es requerida',
            'brand_id.required' => 'La marca es requerida',
            'unit_id.required' => 'La unidad es requerida',
        ];

        $this->validate($rules, $messages);

        if ($this->type == 3 && empty($this->skus)) {
            $this->dispatch('alert', 'AGREGAR AL MENOS UNA (Talla/Color) PARA EPPS.', 'warning');
            return;
        }

        DB::beginTransaction();

        try {
            $imagePath = null;
            if ($this->image && is_object($this->image)) {
                if ($this->isEditMode && $this->product_id) {
                    $prodCheck = Product::find($this->product_id);
                    if ($prodCheck && $prodCheck->image && Storage::disk('public')->exists($prodCheck->image)) {
                        Storage::disk('public')->delete($prodCheck->image);
                    }
                }
                $manager = new ImageManager(new Driver());
                $filename = 'PROD_' . time() . '.webp';
                $img = $manager->read($this->image->getRealPath());
                $img->scaleDown(width: 1920);
                $encoded = (string) $img->toWebp(95);
                Storage::disk('public')->put('products/' . $filename, $encoded);
                $imagePath = 'products/' . $filename;
                unset($img, $encoded);
            } elseif ($this->isEditMode) {
                $imagePath = $this->image;
            }

            $productsData = [
                'code' => $this->code,
                'name' => $this->name,
                'features' => $this->features,
                'model' => ($this->type == 1) ? $this->model : null,
                'minimum_stock' => $this->minimum_stock,
                'type' => $this->type,
                'lote' => ($this->type == 0) ? $this->lote : false,
                'categorie_id' => ($this->type == 1) ? null : $this->categorie_id,
                'brand_id' => $this->brand_id,
                'unit_id' => $this->unit_id,
                'image' => $imagePath,
            ];

            $product = Product::updateOrCreate(['id' => $this->product_id], $productsData);

            $warehouseId = $this->getDefaultWarehouseId();
            $allWarehouses = Warehouse::pluck('id')->toArray();

            if ($this->isEditMode) {
                foreach ($allWarehouses as $wId) {
                    Inventorie::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $wId],
                        ['stock_lot' => 0, 'stock_nolot' => 0]
                    );
                }
            } else {
                foreach ($allWarehouses as $wId) {
                    Inventorie::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $wId],
                        ['stock_lot' => 0, 'stock_nolot' => 0]
                    );
                }

                $totalInitialStock = ($this->type == 3 && count($this->skus) > 0)
                    ? collect($this->skus)->sum('stock')
                    : ($this->initial_stock ?: 0);

                if ($totalInitialStock > 0) {
                    $invToUpdate = Inventorie::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->first();
                    if ($invToUpdate) {
                        if ($this->lote && $this->type == 0) {
                            $invToUpdate->stock_lot += $totalInitialStock;
                            Lot::create([
                                'lot_number' => 'INIT-' . ($product->code ?? time()),
                                'quantity' => $totalInitialStock,
                                'product_id' => $product->id,
                                'branch_id' => $this->branch_id,
                            ]);
                        } else {
                            $invToUpdate->stock_nolot += $totalInitialStock;
                        }
                        $invToUpdate->save();

                        Kardex::create([
                            'type' => 'ENTRADA',
                            'description' => 'STOCK INICIAL',
                            'quantity_in' => $totalInitialStock,
                            'balance' => $totalInitialStock,
                            'price' => 0,
                            'total' => 0,
                            'product_id' => $product->id,
                            'user_id' => auth()->id(),
                            'warehouse_id' => $warehouseId,
                            'transaction_type' => 'initial_stock',
                            'transaction_id' => $product->id,
                            'status' => 1,
                        ]);
                    }
                }
            }

            if ($this->type == 3 && count($this->skus) > 0) {
                $existingCombos = collect($this->skus)->map(fn($s) => ($s['color_id'] ?: 'null') . '-' . ($s['size_id'] ?: 'null'))->toArray();
                $branches = [$this->branch_id];

                foreach ($branches as $bId) {
                    foreach (ProductSku::where('product_id', $product->id)->where('branch_id', $bId)->get() as $dbSku) {
                        $combo = ($dbSku->color_id ?: 'null') . '-' . ($dbSku->size_id ?: 'null');
                        if (!in_array($combo, $existingCombos))
                            $dbSku->delete();
                    }

                    foreach ($this->skus as $sku) {
                        $skuModel = ProductSku::where('product_id', $product->id)
                            ->where('branch_id', $bId)
                            ->where('color_id', $sku['color_id'])
                            ->where('size_id', $sku['size_id'])
                            ->first();

                        if ($skuModel) {
                            $skuModel->update(['sku' => $sku['sku'], 'price' => $sku['price'] ?? null]);
                        } else {
                            $initStock = (!$this->isEditMode && $bId == $this->branch_id) ? ($sku['stock'] ?? 0) : 0;
                            ProductSku::create([
                                'product_id' => $product->id,
                                'branch_id' => $bId,
                                'color_id' => $sku['color_id'],
                                'size_id' => $sku['size_id'],
                                'sku' => $sku['sku'],
                                'price' => $sku['price'] ?? null,
                                'stock' => $initStock,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $isEdit = $this->isEditMode;
            $accion = $isEdit ? 'EDITAR' : 'CREAR';
            $desc = $isEdit
                ? "Editó producto: [{$product->code}] {$product->name}"
                : "Creó producto: [{$product->code}] {$product->name}";
            $this->logActivity('PRODUCTOS', $accion, $desc, $product->id, null,
                ['code' => $product->code, 'name' => $product->name, 'type' => $product->type]);

            $message = $isEdit ? 'PRODUCTO ACTUALIZADO EXITOSAMENTE.' : 'PRODUCTO CREADO CON ÉXITO.';
            $this->resetInputFields();
            $this->dispatch('productStoreOrUpdate', $message);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ERROR AL GUARDAR PRODUCTO: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            $this->dispatch('alert', 'Error al guardar: ' . $e->getMessage(), 'error');
        }
    }

    public function edit($id)
    {
        $this->resetValidation();

        $product = Product::findOrFail($id);

        $this->product_id = $product->id;
        $this->code = $product->code;
        $this->image = $product->image;
        $this->name = $product->name;
        $this->features = $product->features;
        $this->model = $product->model;
        $this->minimum_stock = $product->minimum_stock;
        $this->categorie_id = $product->categorie_id;
        $this->brand_id = $product->brand_id;
        $this->unit_id = $product->unit_id;
        $this->image_preview = $product->image ? asset('storage/' . $product->image) : null;
        $this->type = $product->type;
        $this->lote = (bool) $product->lote;
        $this->isEditMode = true;

        $this->dispatch('load-image-preview', ['image' => $this->image_preview]);

        if ($product->type == 3) {
            $this->skus = ProductSku::with(['color', 'size'])
                ->where('product_id', $product->id)
                ->where('branch_id', $this->branch_id)
                ->get()
                ->map(fn($item) => [
                    'color_id' => $item->color_id,
                    'color_name' => $item->color ? $item->color->name : '-',
                    'size_id' => $item->size_id,
                    'size_name' => $item->size ? $item->size->name : '-',
                    'sku' => $item->sku,
                    'price' => $item->price,
                    'is_custom_price' => !is_null($item->price),
                    'stock' => $item->stock,
                ])->toArray();
        }
    }

    public function addSkuToProduct()
    {
        if (!$this->color_id && !$this->size_id) {
            $this->dispatch('alert', 'Debe seleccionar al menos una Talla o un Color.', 'warning');
            return;
        }

        foreach ($this->skus as $sku) {
            if ($sku['color_id'] == $this->color_id && $sku['size_id'] == $this->size_id) {
                $this->dispatch('alert', 'Esta combinación ya fue agregada.', 'warning');
                return;
            }
        }

        $colorName = '-';
        $colorPrefix = '';
        if ($this->color_id) {
            $colorName = collect($this->colors)->firstWhere('id', $this->color_id)->name ?? '-';
            $colorPrefix = '-' . strtoupper(substr($colorName, 0, 3));
        }

        $sizeName = '-';
        $sizePrefix = '';
        if ($this->size_id) {
            $sizeName = collect($this->sizes)->firstWhere('id', $this->size_id)->name ?? '-';
            $sizePrefix = '-' . strtoupper(substr($sizeName, 0, 3));
        }

        $baseCode = $this->code ? (strlen($this->code) > 6 ? substr($this->code, -5) : $this->code) : 'PRD';

        $this->skus[] = [
            'color_id' => $this->color_id ?: null,
            'color_name' => $colorName,
            'size_id' => $this->size_id ?: null,
            'size_name' => $sizeName,
            'sku' => strtoupper($baseCode . $colorPrefix . $sizePrefix),
            'price' => null,
            'is_custom_price' => false,
            'stock' => 0,
        ];

        $this->color_id = '';
        $this->size_id = '';
        $this->dispatch('alert', 'Combinación agregada.', 'success');
    }

    public function updateSkuCode($index, $value)
    {
        if (isset($this->skus[$index]))
            $this->skus[$index]['sku'] = $value;
    }

    public function toggleCustomPrice($index)
    {
        if (!isset($this->skus[$index]))
            return;
        $this->skus[$index]['is_custom_price'] = !($this->skus[$index]['is_custom_price'] ?? false);
        if (!$this->skus[$index]['is_custom_price'])
            $this->skus[$index]['price'] = null;
    }

    public function updateSkuPrice($index, $value)
    {
        if (isset($this->skus[$index]))
            $this->skus[$index]['price'] = $value === '' ? null : floatval($value);
    }

    public function updateSkuStock($index, $value)
    {
        if (isset($this->skus[$index]))
            $this->skus[$index]['stock'] = $value === '' ? 0 : intval($value);
    }

    public function removeSku($index)
    {
        unset($this->skus[$index]);
        $this->skus = array_values($this->skus);
    }

    public function delete($id)
    {
        $product = Product::find($id);
        if ($product) {
            $newStatus = $product->status == 1 ? 0 : 1;
            $product->update(['status' => $newStatus]);

            $accion = $newStatus == 1 ? 'RESTAURAR' : 'ELIMINAR';
            $this->logActivity(
                'PRODUCTOS', $accion,
                ($newStatus == 1 ? 'Restauró' : 'Eliminó') . " producto: [{$product->code}] {$product->name}",
                $product->id,
                ['status' => $newStatus == 1 ? 0 : 1],
                ['status' => $newStatus]
            );

            $message = $newStatus == 1 ? 'PRODUCTO RESTAURADO EXITOSAMENTE.' : 'PRODUCTO ELIMINADO EXITOSAMENTE.';
            $this->dispatch('productDeleted', $message);
        }
    }

    public function generateCode()
    {
        $this->code = substr(now()->format('YmHisv') . uniqid(), 0, 15);
        $this->dispatch('alert', 'CÓDIGO GENERADO CON ÉXITO.', 'success');
    }

    public function storeCategory()
    {
        $this->validate(
            ['name_category' => 'required|unique:categories,name'],
            ['name_category.required' => 'La categoría es requerida', 'name_category.unique' => 'La categoría ya existe']
        );
        $category = Categorie::updateOrCreate(['name' => $this->name_category]);
        $this->resetInputCategoryBrandUnit();
        $this->categories = Categorie::where('status', 1)->orderBy('id', 'asc')->get();
        $this->categorie_id = $category->id;
        $this->dispatch('alert', 'CATEGORÍA CREADA CON ÉXITO.', 'success', 'category');
    }

    public function storeBrand()
    {
        $this->validate(
            ['name_brand' => 'required|unique:brands,name'],
            ['name_brand.required' => 'La marca es requerida', 'name_brand.unique' => 'La marca ya existe']
        );
        $brand = Brand::updateOrCreate(['name' => $this->name_brand]);
        $this->resetInputCategoryBrandUnit();
        $this->brands = Brand::where('status', 1)->orderBy('id', 'asc')->get();
        $this->brand_id = $brand->id;
        $this->dispatch('alert', 'MARCA CREADA CON ÉXITO.', 'success', 'brand');
    }

    public function storeUnit()
    {
        $this->validate(
            ['name_unit' => 'required|unique:units,name'],
            ['name_unit.required' => 'La unidad es requerida', 'name_unit.unique' => 'La unidad ya existe']
        );
        $unit = Unit::updateOrCreate(['name' => $this->name_unit], [
            'name' => $this->name_unit,
            'base_unit' => $this->unit_base_unit ?: null,
            'factor' => $this->unit_factor ?: null,
        ]);
        $this->resetInputCategoryBrandUnit();
        $this->units = Unit::where('status', 1)->orderBy('id', 'asc')->get();
        $this->unit_id = $unit->id;
        $this->dispatch('alert', 'UNIDAD CREADA CON ÉXITO.', 'success', 'unit');
    }

    public function resetInputCategoryBrandUnit()
    {
        $this->resetErrorBag(['name_category', 'name_brand', 'name_unit']);
        $this->name_category = '';
        $this->name_brand = '';
        $this->name_unit = '';
        $this->unit_base_unit = '';
        $this->unit_factor = '';
    }
}
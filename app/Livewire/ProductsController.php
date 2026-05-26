<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Categorie;
use App\Models\Unit;
use App\Models\Branche;
use App\Models\Inventorie;
use App\Models\Kardex;
use App\Models\Warehouse;
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
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    public $code, $name, $features, $image, $image_preview, $categorie_id, $brand_id, $unit_id, $product_id;
    public $purchase_price, $sale_price, $profit = 25, $minimum_stock = 0, $initial_stock = 0;
    public $isEditMode = false;

    public $filter_category = '';
    public $filter_brand = '';
    public $filter_type = '';
    public $filter_status = '1';

    public $searchTerm;
    public $categories, $brands, $units;
    public $name_category, $name_brand, $name_unit, $unit_base_unit, $unit_factor, $branch_id;
    public $type = 0;
    public $lote = false;

    public $has_loyalty = false;
    public $loyalty_req_qty = 5;
    public $loyalty_program_enabled;

    public $pos_type, $openAccordion = null;
    public $camera_barcode_enabled;

    protected $listeners = [
        'delete',
    ];

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

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
        $this->camera_barcode_enabled = $branch ? $branch->camera_barcode_enabled : 0;
        $this->loyalty_program_enabled = $branch ? $branch->loyalty_program : 0;
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

        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);

        $branch = Branche::find($this->branch_id) ?? Branche::first();
        $this->pos_type = $branch ? $branch->pos_type : 1;
        $this->camera_barcode_enabled = $branch ? $branch->camera_barcode_enabled : 0;
        $this->loyalty_program_enabled = $branch ? $branch->loyalty_program : 0;
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
                'products.image'
            ])
            ->with([
                'brands:id,name',
                'categories:id,name',
                'units:id,name',
                'inventories' => function ($query) use ($warehouseId) {
                    $query->select('product_id', 'purchase_price', 'sale_price', 'warehouse_id')
                        ->where('warehouse_id', $warehouseId);
                }
            ])
            ->when($this->filter_status !== '', function ($q) {
                $q->where('products.status', $this->filter_status);
            })
            ->when($this->filter_category, function ($q) {
                $q->where('products.categorie_id', $this->filter_category);
            })
            ->when($this->filter_brand, function ($q) {
                $q->where('products.brand_id', $this->filter_brand);
            })
            ->when($this->filter_type !== '', function ($q) {
                $q->where('products.type', $this->filter_type);
            })
            ->when(strlen($this->searchTerm ?? '') > 0, function ($query) {
                $query->where(function ($q) {
                    $q->where('products.code', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('products.name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('brands', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('categories', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('units', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.products.products', [
            'products' => $products,
            'startCount' => $products->total() - ($products->currentPage() - 1) * $products->perPage()
        ])
            ->extends('layouts.theme.app');
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
        $this->categorie_id = null;
        $this->brand_id = null;
        $this->unit_id = null;
        $this->product_id = '';
        $this->purchase_price = '';
        $this->sale_price = null;
        $this->profit = 25;
        $this->minimum_stock = 0;
        $this->initial_stock = 0;
        $this->image = null;
        $this->image_preview = null;

        $this->has_loyalty = false;
        $this->loyalty_req_qty = 5;

        $this->type = 0;
        $this->lote = false;

        $this->openAccordion = null;
        $this->isEditMode = false;
        $this->dispatch('reset-image-preview');
    }

    public function storeOrUpdate()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $rules = [
            'code'          => 'required|unique:products,code,' . ($this->isEditMode ? $this->product_id : ''),
            'name'          => 'required|min:3',
            'purchase_price'=> 'required|min:0',
            'sale_price'    => 'required|min:0',
            'minimum_stock' => 'required|min:0',
            'profit'        => 'required|min:0',
            'categorie_id'  => 'required|numeric',
            'brand_id'      => 'required|numeric',
            'loyalty_req_qty' => $this->has_loyalty ? 'required|numeric|min:1' : 'nullable',
        ];

        if ($this->type != 5) {
            $rules['unit_id'] = 'required|numeric';
        } else {
            $rules['unit_id'] = 'nullable';
        }

        if (!$this->isEditMode) {
            $rules['initial_stock'] = 'nullable|numeric|min:0';
        }

        $rules['image'] = ($this->image && is_object($this->image))
            ? 'nullable|image|max:20480'
            : 'nullable';

        $messages = [
            'code.required'          => 'El codigo es requerido',
            'code.unique'            => 'El codigo ya está en uso',
            'name.required'          => 'El producto es requerido',
            'name.min'               => 'El producto debe tener al menos 3 caracteres',
            'purchase_price.required'=> 'El precio compra es requerido',
            'sale_price.required'    => 'El precio venta es requerido',
            'loyalty_req_qty.required' => 'Debe ingresar la cantidad requerida para la fidelización.',
            'loyalty_req_qty.numeric'  => 'La cantidad requerida debe ser un número.',
            'minimum_stock.required' => 'El minimo stock es requerido',
            'profit.required'        => 'La utilidad es requerida',
            'image.image'            => 'El campo debe ser una imagen.',
            'image.max'              => 'La imagen es demasiado pesada (Máx 20MB).',
            'categorie_id.required'  => 'La categoría es requerida',
            'brand_id.required'      => 'La marca es requerida',
            'unit_id.required'       => 'La unidad es requerida',
        ];

        $this->validate($rules, $messages);

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
                'code'          => $this->code,
                'name'          => $this->name,
                'features'      => $this->features,
                'minimum_stock' => $this->minimum_stock,
                'type'          => $this->type,
                'lote'          => $this->lote,
                'has_loyalty'   => $this->has_loyalty ? 1 : 0,
                'loyalty_req_qty' => $this->loyalty_req_qty ?: 5,
                'categorie_id'  => $this->categorie_id,
                'brand_id'      => $this->brand_id,
                'unit_id'       => $this->unit_id ?: 1,
                'image'         => $imagePath,
            ];

            $product = Product::updateOrCreate(
                ['id' => $this->product_id],
                $productsData
            );

            $warehouseId = $this->getDefaultWarehouseId();
            $allWarehouses = Warehouse::pluck('id')->toArray();

            if ($this->isEditMode) {
                Inventorie::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId)
                    ->update([
                        'purchase_price' => $this->purchase_price,
                        'sale_price'     => $this->sale_price,
                        'profit'         => $this->profit,
                    ]);

                foreach ($allWarehouses as $wId) {
                    Inventorie::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $wId],
                        [
                            'purchase_price' => $this->purchase_price ?: 0,
                            'sale_price'     => $this->sale_price ?: 0,
                            'profit'         => $this->profit ?: 25,
                            'stock_lot'      => 0,
                            'stock_nolot'    => 0,
                        ]
                    );
                }
            } else {
                foreach ($allWarehouses as $wId) {
                    Inventorie::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $wId],
                        [
                            'purchase_price' => $this->purchase_price ?: 0,
                            'sale_price'     => $this->sale_price ?: 0,
                            'profit'         => $this->profit ?: 25,
                            'stock_lot'      => 0,
                            'stock_nolot'    => 0,
                        ]
                    );
                }

                if (in_array($this->type, [0, 3, 4, 5])) {
                    $totalInitialStock = intval($this->initial_stock ?: 0);

                    if ($totalInitialStock > 0) {
                        $invToUpdate = Inventorie::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouseId)
                            ->first();

                        if ($invToUpdate) {
                            $invToUpdate->stock_nolot += $totalInitialStock;
                            $invToUpdate->save();

                            Kardex::create([
                                'type'             => 'ENTRADA',
                                'description'      => 'STOCK INICIAL',
                                'quantity_in'      => $totalInitialStock,
                                'quantity_out'     => 0,
                                'balance'          => $totalInitialStock,
                                'price'            => $this->purchase_price ?: 0,
                                'total'            => $totalInitialStock * ($this->purchase_price ?: 0),
                                'product_id'       => $product->id,
                                'user_id'          => auth()->id(),
                                'warehouse_id'     => $warehouseId,
                                'transaction_type' => 'initial_stock',
                                'transaction_id'   => $product->id,
                                'status'           => 1,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $message = $this->isEditMode ? 'PRODUCTO ACTUALIZADO EXITOSAMENTE.' : 'PRODUCTO CREADO CON ÉXITO.';
            $this->resetInputFields();
            $this->dispatch('productStoreOrUpdate', $message);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("ERROR CRITICO AL GUARDAR PRODUCTO: " . $e->getMessage());
            $this->dispatch('alert', 'Error al guardar: ' . $e->getMessage(), 'error');
        }
    }

    public function edit($id)
    {
        $this->resetValidation();

        $warehouseId = $this->getDefaultWarehouseId();

        $product = Product::select(
                'products.*',
                'inventories.purchase_price',
                'inventories.sale_price',
                'inventories.profit'
            )
            ->join('inventories', 'products.id', '=', 'inventories.product_id')
            ->where('products.id', $id)
            ->where('inventories.warehouse_id', $warehouseId)
            ->firstOrFail();

        $this->product_id       = $product->id;
        $this->code             = $product->code;
        $this->image            = $product->image;
        $this->name             = $product->name;
        $this->features         = $product->features;
        $this->minimum_stock    = $product->minimum_stock;
        $this->purchase_price   = $product->purchase_price;
        $this->sale_price       = $product->sale_price;
        $this->profit           = $product->profit;
        $this->categorie_id     = $product->categorie_id;
        $this->brand_id         = $product->brand_id;
        $this->unit_id          = $product->unit_id;
        $this->image_preview    = $product->image ? asset('storage/' . $product->image) : null;
        $this->type             = $product->type;
        $this->lote             = (bool) $product->lote;
        $this->has_loyalty      = (bool) $product->has_loyalty;
        $this->loyalty_req_qty  = $product->loyalty_req_qty;

        $this->isEditMode = true;
        $this->dispatch('load-image-preview', ['image' => $this->image_preview]);
    }

    public function delete($id)
    {
        $product = Product::find($id);

        if ($product) {
            $newEstado = $product->status == 1 ? 0 : 1;
            $product->update(['status' => $newEstado]);
            $message = $newEstado == 1 ? 'PRODUCTO RESTAURADO EXITOSAMENTE.' : 'PRODUCTO ELIMINADO EXITOSAMENTE.';
            $this->dispatch('productDeleted', $message);
        } else {
            session()->flash('message', 'PRODUCTO NO ENCONTRADO.');
        }
    }

    public function generateCode()
    {
        $timestamp = now()->format('YmHisv');
        $uniquePart = uniqid();
        $this->code = substr($timestamp . $uniquePart, 0, 15);
        $this->dispatch('alert', 'CODIGO DE BARRAS GENERADO CON ÉXITO.', 'success');
    }

    private function roundToNearestTenth($value)
    {
        return round($value * 10) / 10;
    }

    public function calculateSalePrice()
    {
        if (is_numeric($this->purchase_price) && is_numeric($this->profit)) {
            $calculated = $this->purchase_price * (1 + ($this->profit / 100));
            $rounded = $this->roundToNearestTenth($calculated);
            $this->sale_price = number_format($rounded, 2, '.', '');
        } else {
            $this->sale_price = null;
        }
    }

    public function calculatePurchasePrice()
    {
        if (is_numeric($this->sale_price) && is_numeric($this->profit)) {
            if (!is_numeric($this->purchase_price) || $this->purchase_price == 0) {
                $calculated = $this->sale_price / (1 + ($this->profit / 100));
                $rounded = $this->roundToNearestTenth($calculated);
                $this->purchase_price = number_format($rounded, 2, '.', '');
            } else {
                $this->profit = round((($this->sale_price / $this->purchase_price) - 1) * 100, 2);
            }
        } else {
            $this->purchase_price = null;
        }
    }

    public function storeCategory()
    {
        $this->validate(
            ['name_category' => 'required|unique:categories,name'],
            ['name_category.required' => 'La categoria es requerida', 'name_category.unique' => 'La categoria ya está en uso']
        );

        $category = Categorie::updateOrCreate(['name' => $this->name_category]);
        $this->resetInputCategoryBrandUnit();

        $this->categories = Categorie::where('status', 1)->orderBy('id', 'asc')->get();
        $this->categorie_id = $category->id;
        $this->dispatch('alert', 'CATEGORIA CREADA CON ÉXITO.', 'success', 'category');
    }

    public function storeBrand()
    {
        $this->validate(
            ['name_brand' => 'required|unique:brands,name'],
            ['name_brand.required' => 'La marca es requerida', 'name_brand.unique' => 'La marca ya está en uso']
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
            ['name_unit.required' => 'La unidad es requerida', 'name_unit.unique' => 'La unidad ya está en uso']
        );

        $unit = Unit::updateOrCreate(
            ['name' => $this->name_unit],
            ['name' => $this->name_unit, 'base_unit' => $this->unit_base_unit ?: null, 'factor' => $this->unit_factor ?: null]
        );
        $this->resetInputCategoryBrandUnit();

        $this->units = Unit::where('status', 1)->orderBy('id', 'asc')->get();
        $this->unit_id = $unit->id;
        $this->dispatch('alert', 'UNIDAD CREADA CON ÉXITO.', 'success', 'unit');
    }

    public function resetInputCategoryBrandUnit()
    {
        $this->resetErrorBag(['name_category', 'name_brand', 'name_unit']);
        $this->name_category  = '';
        $this->name_brand     = '';
        $this->name_unit      = '';
        $this->unit_base_unit = '';
        $this->unit_factor    = '';
    }
}

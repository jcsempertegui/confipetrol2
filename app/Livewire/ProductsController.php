<?php

namespace App\Livewire;

use App\Models\Printer;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Categorie;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\Branche;
use App\Models\Additional;
use App\Models\Variant;
use App\Models\Inventorie;
use App\Models\AdditionalProduct;
use App\Models\VariantProduct;
use App\Models\ProductComponent;
use App\Models\ProductionArea;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\Color;
use App\Models\Size;
use App\Models\ProductSku;
use App\Models\Kardex;
use App\Models\Lot;
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

    public $gallery = [];
    public $temp_gallery = [];
    public $saved_gallery = [];
    public $images_to_delete = [];

    public $isAdditionalMode = true, $isVariantMode = true, $isIngredientMode = true, $isPackagingMode = true;

    public $searchTerm, $searchAdditional, $searchVariant, $searchIngredient, $searchPackaging, $searchProductUnit, $searchComboProduct;
    public $categories, $brands, $units;
    public $name_category, $name_brand, $name_unit, $unit_base_unit, $unit_factor, $branch_id;
    public $type = 0;
    public $lote = false;

    public $has_loyalty = false;
    public $loyalty_req_qty = 5;
    public $loyalty_program_enabled;

    public $additional_prices = [];

    public $pos_type, $openAccordion = null, $enable_size_color;
    public $enable_product_gallery, $camera_barcode_enabled;

    public $name_additional, $price;
    public $name_variant, $price_variant;
    public $list_additionals = [], $additionals = [];
    public $list_variants = [], $variants = [];

    public $list_ingredients = [], $ingredients = [];
    public $list_packagings = [], $packagings = [];
    public $list_product_units = [], $product_units = [];
    public $list_combo_products = [], $combo_products = [];

    public $production_areas, $production_area_id, $name_production_area;

    public $colors = [], $sizes = [];
    public $color_id = '', $size_id = '';
    public $skus = [];

    public $apply_additionals_all_branches = false;
    public $apply_skus_all_branches = false;

    protected $listeners = [
        'delete',
        'deleteAdditionalProduct',
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

    public function updatedUnitId($value)
    {
        foreach ($this->product_units as $index => $item) {
            if ($item['unit_id'] == $value) {
                unset($this->product_units[$index]);
                $this->product_units = array_values($this->product_units);
                $this->dispatch('alert', 'LA UNIDAD SE REMOVIÓ DE CONVERSIONES AL SER ELEGIDA COMO BASE.', 'info');
                break;
            }
        }
    }

    public function updatedType($value)
    {
        if ($value == 5) {
            $this->additionals = [];
            $this->variants = [];
            $this->ingredients = [];
            $this->skus = [];
            $this->profit = 25;
            $this->purchase_price = null;
            $this->sale_price = null;
            $this->initial_stock = 0;
            $this->minimum_stock = 0;
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
        $this->enable_size_color = $branch ? $branch->enable_size_color : 0;
        $this->enable_product_gallery = $branch ? $branch->enable_product_gallery : 0;
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

        $this->colors = Color::where('status', 1)->orderBy('id', 'asc')->get();
        $this->sizes = Size::where('status', 1)->orderBy('id', 'asc')->get();

        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);

        $branch = Branche::find($this->branch_id) ?? Branche::first();
        $this->pos_type = $branch ? $branch->pos_type : 1;
        $this->enable_size_color = $branch ? $branch->enable_size_color : 0;
        $this->enable_product_gallery = $branch ? $branch->enable_product_gallery : 0;
        $this->camera_barcode_enabled = $branch ? $branch->camera_barcode_enabled : 0;
        $this->loyalty_program_enabled = $branch ? $branch->loyalty_program : 0;

        if ($this->pos_type == 4 || $this->pos_type == 0) {
            $this->production_areas = ProductionArea::where('status', 1)
                ->orderBy('id', 'asc')
                ->get();
        }
    }

    public function render()
    {
        $this->listAdditional();
        $this->listVariant();
        $this->listIngredient();
        $this->listPackaging();
        $this->listProductUnits();
        $this->listComboProducts();

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
            ->when(strlen($this->searchTerm) > 0, function ($query) {
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
        $this->production_area_id = null;
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

        $this->gallery = [];
        $this->temp_gallery = [];
        $this->saved_gallery = [];
        $this->images_to_delete = [];

        $this->additional_prices = [];

        $this->type = 0;
        $this->lote = false;
        $this->additionals = [];
        $this->variants = [];
        $this->ingredients = [];
        $this->packagings = [];
        $this->product_units = [];
        $this->combo_products = [];
        $this->skus = [];
        $this->color_id = '';
        $this->size_id = '';

        $this->apply_additionals_all_branches = false;
        $this->apply_skus_all_branches = false;
        $this->searchPackaging = '';
        $this->searchProductUnit = '';
        $this->searchComboProduct = '';
        $this->isPackagingMode = true;

        $this->openAccordion = null;
        $this->isEditMode = false;
        $this->dispatch('reset-image-preview');
    }

    public function addPrice()
    {
        $count = 0;
        foreach ($this->additional_prices as $p) {
            if ($p['type'] === 'normal') {
                $count++;
            }
        }
        $this->additional_prices[] = [
            'name' => 'Precio ' . ($count + 1),
            'type' => 'normal',
            'price' => null,
            'min_quantity' => null,
            'max_quantity' => null,
        ];
    }

    public function updatePriceName($index)
    {
        if (isset($this->additional_prices[$index])) {
            $type = $this->additional_prices[$index]['type'];
            $count = 0;
            foreach ($this->additional_prices as $k => $p) {
                if ($p['type'] === $type && $k <= $index) {
                    $count++;
                }
            }

            if ($type === 'wholesale') {
                $this->additional_prices[$index]['name'] = 'Precio por Mayor ' . $count;
            } else {
                $this->additional_prices[$index]['name'] = 'Precio ' . $count;
            }
        }
    }

    public function removePrice($index)
    {
        unset($this->additional_prices[$index]);
        $this->additional_prices = array_values($this->additional_prices);
    }

    public function updatedTempGallery()
    {
        $this->gallery = array_merge($this->gallery, $this->temp_gallery);

        $totalSaved = count($this->saved_gallery);
        $totalNew = count($this->gallery);

        if (($totalSaved + $totalNew) > 3) {
            $allowedNew = 3 - $totalSaved;
            if ($allowedNew < 0)
                $allowedNew = 0;

            $this->gallery = array_slice($this->gallery, 0, $allowedNew);

            $this->dispatch('alert', 'Solo puedes tener un máximo de 3 imágenes en total.', 'warning');
            $this->resetErrorBag('gallery');
            $this->addError('gallery', 'Límite de 3 imágenes excedido.');
        }
    }

    public function storeOrUpdate()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        if ($this->type == 5) {
            $this->minimum_stock = 0;
            $this->initial_stock = 0;
        }

        if ((count($this->saved_gallery) + count($this->gallery)) > 3) {
            $this->dispatch('alert', 'Has excedido el límite de 3 imágenes.', 'error');
            return;
        }

        $rules = [
            'code' => 'required|unique:products,code,' . ($this->isEditMode ? $this->product_id : ''),
            'name' => 'required|min:3',
            'purchase_price' => 'required|min:0',
            'sale_price' => 'required|min:0',
            'minimum_stock' => 'required|min:0',
            'profit' => 'required|min:0',
            'categorie_id' => 'required|numeric',
            'brand_id' => 'required|numeric',
            'loyalty_req_qty' => $this->has_loyalty ? 'required|numeric|min:1' : 'nullable',
            'gallery' => 'nullable|array|max:3',
            'gallery.*' => 'image|max:20480',
            'additional_prices.*.name' => 'required',
            'additional_prices.*.price' => 'required|numeric|min:0',
            'additional_prices.*.type' => 'required|in:normal,wholesale',
        ];

        if ($this->type != 5) {
            $rules['unit_id'] = 'required|numeric';
        } else {
            $rules['unit_id'] = 'nullable';
        }

        if (!$this->isEditMode) {
            $rules['initial_stock'] = 'nullable|numeric|min:0';
        }

        if ($this->image && is_object($this->image)) {
            $rules['image'] = 'nullable|image|max:20480';
        } else {
            $rules['image'] = 'nullable';
        }

        if ($this->pos_type == 4 || $this->pos_type == 0) {
            $rules['production_area_id'] = 'nullable|numeric';
        }

        $messages = [
            'code.required' => 'El codigo es requerido',
            'code.unique' => 'El codigo ya está en uso',
            'name.required' => 'El producto es requerido',
            'name.min' => 'El producto debe tener al menos 3 caracteres',
            'purchase_price.required' => 'El precio compra es requerido',
            'sale_price.required' => 'El precio venta es requerido',
            'loyalty_req_qty.required' => 'Debe ingresar la cantidad requerida para la fidelización.',
            'loyalty_req_qty.numeric' => 'La cantidad requerida debe ser un número.',
            'minimum_stock.required' => 'El minimo stock es requerido',
            'profit.required' => 'La utilidad es requerido',
            'image.image' => 'El campo debe ser una imagen.',
            'image.max' => 'La imagen es demasiado pesada (Máx 20MB).',
            'categorie_id.required' => 'La categoría es requerida',
            'brand_id.required' => 'La marca es requerida',
            'unit_id.required' => 'La unidad es requerida',
            'production_area_id.numeric' => 'El área de producción debe ser válida',
            'additional_prices.*.name.required' => 'El nombre del precio adicional es requerido',
            'additional_prices.*.price.required' => 'El valor del precio adicional es requerido',
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
                'code' => $this->code,
                'name' => $this->name,
                'features' => $this->features,
                'minimum_stock' => $this->minimum_stock,
                'type' => $this->type,
                'lote' => $this->lote,
                'has_loyalty' => $this->has_loyalty ? 1 : 0,
                'loyalty_req_qty' => $this->loyalty_req_qty ?: 5,
                'categorie_id' => $this->categorie_id,
                'brand_id' => $this->brand_id,
                'unit_id' => $this->unit_id ?: 1,
                'image' => $imagePath,
            ];

            if ($this->pos_type == 4 || $this->pos_type == 0) {
                $productsData['production_area_id'] = $this->production_area_id ?: null;
            }

            $product = Product::updateOrCreate(
                ['id' => $this->product_id],
                $productsData
            );

            if (!empty($this->images_to_delete)) {
                $imagesToDelete = ProductImage::whereIn('id', $this->images_to_delete)->get();
                foreach ($imagesToDelete as $img) {
                    if (Storage::disk('public')->exists($img->image_path)) {
                        Storage::disk('public')->delete($img->image_path);
                    }
                    $img->delete();
                }
            }

            if (!empty($this->gallery)) {
                $manager = new ImageManager(new Driver());
                foreach ($this->gallery as $photo) {
                    if (!$photo->isValid())
                        continue;
                    try {
                        $filename = 'GALLERY_' . $product->id . '_' . uniqid() . '.webp';
                        $img = $manager->read($photo->getRealPath());
                        $img->scaleDown(width: 1920);
                        $encoded = (string) $img->toWebp(95);
                        Storage::disk('public')->put('product_gallery/' . $filename, $encoded);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => 'product_gallery/' . $filename
                        ]);
                        unset($img, $encoded);
                    } catch (\Exception $e) {
                        Log::error("Error subiendo imagen galería: " . $e->getMessage());
                    }
                }
            }

            $warehouseId = $this->getDefaultWarehouseId();
            $allWarehouses = Warehouse::pluck('id')->toArray();

            if ($this->isEditMode) {
                Inventorie::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->update([
                    'purchase_price' => $this->purchase_price,
                    'sale_price' => $this->sale_price,
                    'profit' => $this->profit,
                ]);

                foreach($allWarehouses as $wId) {
                    Inventorie::firstOrCreate([
                        'product_id' => $product->id,
                        'warehouse_id' => $wId
                    ], [
                        'purchase_price' => $this->purchase_price ?: 0,
                        'sale_price' => $this->sale_price ?: 0,
                        'profit' => $this->profit ?: 25,
                        'stock_lot' => 0,
                        'stock_nolot' => 0
                    ]);
                }

            } else {
                foreach($allWarehouses as $wId) {
                    Inventorie::firstOrCreate([
                        'product_id' => $product->id,
                        'warehouse_id' => $wId
                    ], [
                        'purchase_price' => $this->purchase_price ?: 0,
                        'sale_price' => $this->sale_price ?: 0,
                        'profit' => $this->profit ?: 25,
                        'stock_lot' => 0,
                        'stock_nolot' => 0
                    ]);
                }

                if (in_array($this->type, [0, 3, 4, 5])) {
                    $totalInitialStock = 0;
                    if ($this->type == 0 && count($this->skus) > 0) {
                        $totalInitialStock = collect($this->skus)->sum('stock');
                    } else {
                        $totalInitialStock = $this->initial_stock ?: 0;
                    }

                    if ($totalInitialStock > 0) {
                        $invToUpdate = Inventorie::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->first();
                        if ($invToUpdate) {
                            $lot_id = null;
                            if ($this->lote) {
                                $invToUpdate->stock_lot += $totalInitialStock;
                                $lotName = 'INIT-' . ($product->code ?? time());
                                $lot = Lot::create([
                                    'lot_number' => $lotName,
                                    'quantity' => $totalInitialStock,
                                    'product_id' => $product->id,
                                    'branch_id' => $this->branch_id,
                                ]);
                                $lot_id = $lot->id;
                            } else {
                                $invToUpdate->stock_nolot += $totalInitialStock;
                            }
                            $invToUpdate->save();

                            Kardex::create([
                                'type' => 'ENTRADA',
                                'description' => 'STOCK INICIAL',
                                'quantity_in' => $totalInitialStock,
                                'balance' => $totalInitialStock,
                                'price' => $this->purchase_price ?: 0,
                                'total' => $totalInitialStock * ($this->purchase_price ?: 0),
                                'product_id' => $product->id,
                                'lot_id' => $lot_id,
                                'user_id' => auth()->id(),
                                'warehouse_id' => $warehouseId,
                                'transaction_type' => 'initial_stock',
                                'transaction_id' => $product->id,
                                'status' => 1,
                            ]);
                        }
                    }
                }
            }

            $priceIdsToKeep = [];
            foreach ($this->additional_prices as $ap) {
                $priceData = [
                    'name' => $ap['name'],
                    'type' => $ap['type'],
                    'price' => $ap['price'] ?: 0,
                    'min_quantity' => ($ap['type'] === 'wholesale' && isset($ap['min_quantity']) && $ap['min_quantity'] !== '') ? $ap['min_quantity'] : null,
                    'max_quantity' => ($ap['type'] === 'wholesale' && isset($ap['max_quantity']) && $ap['max_quantity'] !== '') ? $ap['max_quantity'] : null,
                    'status' => 1
                ];

                if (isset($ap['id'])) {
                    $priceRecord = ProductPrice::updateOrCreate(
                        ['id' => $ap['id'], 'product_id' => $product->id],
                        $priceData
                    );
                } else {
                    $priceRecord = ProductPrice::create(array_merge(['product_id' => $product->id], $priceData));
                }
                $priceIdsToKeep[] = $priceRecord->id;
            }
            ProductPrice::where('product_id', $product->id)->whereNotIn('id', $priceIdsToKeep)->delete();

            if (in_array($this->type, [0, 2])) {
                $additionalsIds = collect($this->additionals)->pluck('additional_id')->toArray();

                $branchesToApply = $this->apply_additionals_all_branches
                    ? Branche::pluck('id')->toArray()
                    : [$this->branch_id];

                foreach ($branchesToApply as $bId) {
                    AdditionalProduct::where('product_id', $product->id)
                        ->where('branch_id', $bId)
                        ->whereNotIn('additional_id', $additionalsIds)
                        ->delete();

                    foreach ($this->additionals as $add) {
                        AdditionalProduct::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'branch_id' => $bId,
                                'additional_id' => $add['additional_id']
                            ],
                            [
                                'selection_type' => $add['selection_type'],
                                'price' => $add['price'] ?? 0,
                            ]
                        );
                    }
                }
            }

            if (in_array($this->type, [0, 2])) {
                $variantsIds = collect($this->variants)->pluck('variant_id')->toArray();
                VariantProduct::where('product_id', $product->id)->where('branch_id', $this->branch_id)->whereNotIn('variant_id', $variantsIds)->delete();
                foreach ($this->variants as $variant) {
                    VariantProduct::updateOrCreate(
                        ['product_id' => $product->id, 'branch_id' => $this->branch_id, 'variant_id' => $variant['variant_id']],
                        ['price' => $variant['price_variant'] ?? 0]
                    );
                }
            }

            if (in_array($this->type, [0])) {
                $existingSkuCombos = collect($this->skus)->map(function ($s) {
                    return ($s['color_id'] ?: 'null') . '-' . ($s['size_id'] ?: 'null');
                })->toArray();

                $branchesToApply = $this->apply_skus_all_branches
                    ? Branche::pluck('id')->toArray()
                    : [$this->branch_id];

                foreach ($branchesToApply as $bId) {
                    $dbSkus = ProductSku::where('product_id', $product->id)->where('branch_id', $bId)->get();
                    foreach ($dbSkus as $dbSku) {
                        $combo = ($dbSku->color_id ?: 'null') . '-' . ($dbSku->size_id ?: 'null');
                        if (!in_array($combo, $existingSkuCombos)) {
                            $dbSku->delete();
                        }
                    }

                    foreach ($this->skus as $sku) {
                        $skuModel = ProductSku::where('product_id', $product->id)
                            ->where('branch_id', $bId)
                            ->where('color_id', $sku['color_id'])
                            ->where('size_id', $sku['size_id'])
                            ->first();

                        if ($skuModel) {
                            $skuModel->update([
                                'sku' => $sku['sku'],
                                'price' => $sku['price'] ?? null
                            ]);
                        } else {
                            $skuInitialStock = (!$this->isEditMode && $bId == $this->branch_id) ? ($sku['stock'] ?? 0) : 0;
                            ProductSku::create([
                                'product_id' => $product->id,
                                'branch_id' => $bId,
                                'color_id' => $sku['color_id'],
                                'size_id' => $sku['size_id'],
                                'sku' => $sku['sku'],
                                'price' => $sku['price'] ?? null,
                                'stock' => $skuInitialStock
                            ]);
                        }
                    }
                }
            }

            if ($this->type == 2) {
                $componentIds = collect($this->ingredients)->pluck('ingredient_id')->toArray();
                ProductComponent::where('product_id', $product->id)
                    ->whereNotIn('component_id', $componentIds)
                    ->delete();

                foreach ($this->ingredients as $ing) {
                    ProductComponent::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'component_id' => $ing['ingredient_id']
                        ],
                        [
                            'quantity' => $ing['quantity'] ?? 1
                        ]
                    );
                }
            }

            if ($this->type == 5) {
                $comboIds = collect($this->combo_products)->pluck('product_id')->toArray();
                ProductComponent::where('product_id', $product->id)
                    ->whereNotIn('component_id', $comboIds)
                    ->delete();

                foreach ($this->combo_products as $cp) {
                    ProductComponent::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'component_id' => $cp['product_id']
                        ],
                        [
                            'quantity' => $cp['quantity'] ?? 1
                        ]
                    );
                }
            }

            if (in_array($this->type, [0, 2])) {
                $packagingIds = collect($this->packagings)->pluck('packaging_id')->toArray();
                DB::table('packaging_products')->where('product_id', $product->id)
                    ->whereNotIn('packaging_id', $packagingIds)
                    ->delete();

                foreach ($this->packagings as $pac) {
                    $exists = DB::table('packaging_products')
                        ->where('product_id', $product->id)
                        ->where('packaging_id', $pac['packaging_id'])
                        ->first();
                    if ($exists) {
                        DB::table('packaging_products')
                            ->where('id', $exists->id)
                            ->update([
                                'quantity' => $pac['quantity'] ?? 1,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('packaging_products')->insert([
                            'product_id' => $product->id,
                            'packaging_id' => $pac['packaging_id'],
                            'quantity' => $pac['quantity'] ?? 1,
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            if (in_array($this->type, [0])) {
                $unitIds = collect($this->product_units)->pluck('unit_id')->toArray();
                DB::table('product_units')->where('product_id', $product->id)
                    ->whereNotIn('unit_id', $unitIds)
                    ->delete();

                foreach ($this->product_units as $pu) {
                    $exists = DB::table('product_units')
                        ->where('product_id', $product->id)
                        ->where('unit_id', $pu['unit_id'])
                        ->first();
                    if ($exists) {
                        DB::table('product_units')
                            ->where('id', $exists->id)
                            ->update([
                                'purchase_price' => $pu['purchase_price'] ?? 0,
                                'price' => $pu['price'] ?? 0,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('product_units')->insert([
                            'product_id' => $product->id,
                            'unit_id' => $pu['unit_id'],
                            'purchase_price' => $pu['purchase_price'] ?? 0,
                            'price' => $pu['price'] ?? 0,
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
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

    $product = Product::with('images')
        ->select(
            'products.*',
            'inventories.purchase_price',
            'inventories.sale_price',
            'inventories.profit'
        )
        ->join('inventories', 'products.id', '=', 'inventories.product_id')
        ->where('products.id', $id)
        ->where('inventories.warehouse_id', $warehouseId)
        ->firstOrFail();

    $this->product_id = $product->id;
    $this->code = $product->code;
    $this->image = $product->image;
    $this->name = $product->name;
    $this->features = $product->features;
    $this->minimum_stock = $product->minimum_stock;
    $this->purchase_price = $product->purchase_price;
    $this->sale_price = $product->sale_price;
    $this->profit = $product->profit;
    $this->categorie_id = $product->categorie_id;
    $this->brand_id = $product->brand_id;
    $this->unit_id = $product->unit_id;
    $this->production_area_id = $product->production_area_id ?: null;
    $this->image_preview = $product->image ? asset('storage/' . $product->image) : null;
    $this->type = $product->type;
    $this->lote = (bool) $product->lote;

    $this->has_loyalty = (bool) $product->has_loyalty;
    $this->loyalty_req_qty = $product->loyalty_req_qty;

    $this->saved_gallery = $product->images;
    $this->images_to_delete = [];

    $this->additional_prices = ProductPrice::where('product_id', $product->id)->get()->toArray();

    $this->isEditMode = true;
    $this->dispatch('load-image-preview', ['image' => $this->image_preview]);

    if (in_array($product->type, [0, 2])) {
        $this->additionals = AdditionalProduct::with('additional')
            ->where('product_id', $product->id)->where('branch_id', $this->branch_id)->get()
            ->map(function ($item) {
                return [
                    'additional_id' => $item->additional_id,
                    'additional' => $item->additional,
                    'selection_type' => $item->selection_type,
                    'product_id' => $item->product_id,
                    'price' => $item->price ?? 0,
                ];
            })->toArray();

        $this->variants = VariantProduct::with('variant')
            ->where('product_id', $product->id)->where('branch_id', $this->branch_id)->get()
            ->map(function ($item) {
                return [
                    'variant_id' => $item->variant_id,
                    'variant' => $item->variant,
                    'selection_type' => $item->selection_type,
                    'product_id' => $item->product_id,
                    'price_variant' => $item->price ?? 0,
                ];
            })->toArray();
    }

    if (in_array($product->type, [0])) {
        $this->skus = ProductSku::with(['color', 'size'])
            ->where('product_id', $product->id)
            ->where('branch_id', $this->branch_id)
            ->get()
            ->map(function ($item) {
                return [
                    'color_id' => $item->color_id,
                    'color_name' => $item->color ? $item->color->name : '-',
                    'size_id' => $item->size_id,
                    'size_name' => $item->size ? $item->size->name : '-',
                    'sku' => $item->sku,
                    'price' => $item->price,
                    'is_custom_price' => !is_null($item->price),
                    'stock' => $item->stock
                ];
            })->toArray();

        $this->product_units = DB::table('product_units')
            ->join('units', 'product_units.unit_id', '=', 'units.id')
            ->where('product_units.product_id', $product->id)
            ->select('product_units.unit_id', 'units.name as unit_name', 'units.base_unit as unit_base_unit', 'units.factor', 'product_units.price', 'product_units.purchase_price')
            ->get()
            ->map(function ($item) {
                return [
                    'unit_id' => $item->unit_id,
                    'unit_name' => $item->unit_name,
                    'unit_base_unit' => $item->unit_base_unit,
                    'factor' => $item->factor,
                    'purchase_price' => $item->purchase_price,
                    'price' => $item->price,
                ];
            })->toArray();
    }

    if ($product->type == 2) {
        $this->ingredients = ProductComponent::with(['component.units'])
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) {
                return [
                    'ingredient_id' => $item->component_id,
                    'name' => $item->component->name,
                    'unit_name' => $item->component->units->name ?? 'Unid',
                    'quantity' => $item->quantity,
                ];
            })->toArray();
    }

    if ($product->type == 5) {
        $this->combo_products = ProductComponent::with([
            'component' => function ($q) {
                $q->select('id', 'code', 'name', 'unit_id');
            },
            'component.inventories' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        ])
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) {
                $inv = $item->component->inventories->first();
                return [
                    'product_id' => $item->component_id,
                    'code' => $item->component->code ?? '-',
                    'name' => $item->component->name,
                    'purchase_price' => $inv ? $inv->purchase_price : 0,
                    'sale_price' => $inv ? $inv->sale_price : 0,
                    'quantity' => intval($item->quantity),
                ];
            })->toArray();
    }

    if (in_array($product->type, [0, 2])) {
        $this->packagings = DB::table('packaging_products')
            ->join('products', 'packaging_products.packaging_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->where('packaging_products.product_id', $product->id)
            ->select('packaging_products.packaging_id', 'products.name', 'units.name as unit_name', 'packaging_products.quantity')
            ->get()
            ->map(function ($item) {
                return [
                    'packaging_id' => $item->packaging_id,
                    'name' => $item->name,
                    'unit_name' => $item->unit_name ?? 'Unid',
                    'quantity' => $item->quantity,
                ];
            })->toArray();
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

        $baseCode = 'PRD';
        if ($this->code) {
            $baseCode = strlen($this->code) > 6 ? substr($this->code, -5) : $this->code;
        }

        $generatedSku = strtoupper($baseCode . $colorPrefix . $sizePrefix);

        $this->skus[] = [
            'color_id' => $this->color_id ?: null,
            'color_name' => $colorName,
            'size_id' => $this->size_id ?: null,
            'size_name' => $sizeName,
            'sku' => $generatedSku,
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
        if (!isset($this->skus[$index]))
            return;
        $this->skus[$index]['sku'] = $value;
    }

    public function toggleCustomPrice($index)
    {
        if (!isset($this->skus[$index]))
            return;

        $this->skus[$index]['is_custom_price'] = !($this->skus[$index]['is_custom_price'] ?? false);

        if (!$this->skus[$index]['is_custom_price']) {
            $this->skus[$index]['price'] = null;
        }
    }

    public function updateSkuPrice($index, $value)
    {
        if (!isset($this->skus[$index]))
            return;
        $this->skus[$index]['price'] = $value == '' ? null : floatval($value);
    }

    public function updateSkuStock($index, $value)
    {
        if (!isset($this->skus[$index]))
            return;
        $this->skus[$index]['stock'] = $value == '' ? 0 : intval($value);
    }

    public function removeSku($index)
    {
        unset($this->skus[$index]);
        $this->skus = array_values($this->skus);
    }

    public function deleteGalleryImage($imageId)
    {
        $this->images_to_delete[] = $imageId;

        $this->saved_gallery = collect($this->saved_gallery)->filter(function ($img) use ($imageId) {
            return $img->id != $imageId;
        })->values();

        $this->dispatch('alert', 'Imagen marcada para eliminar. Guarde cambios.', 'warning');
    }

    public function removeNewGalleryImage($index)
    {
        array_splice($this->gallery, $index, 1);
    }

    public function listIngredient()
    {
        $query = Product::where('status', 1)
            ->where('type', 3)
            ->where('name', 'like', '%' . $this->searchIngredient . '%');

        if ($this->product_id) {
            $query->where('id', '!=', $this->product_id);
        }

        $this->list_ingredients = $query->get();
    }

    public function addIngredientToProduct($id)
    {
        foreach ($this->ingredients as $item) {
            if ($item['ingredient_id'] == $id) {
                $this->dispatch('alert', 'ESTE INGREDIENTE YA FUE AGREGADO.', 'warning');
                return;
            }
        }

        $ingredient = Product::with('units')->find($id);

        if ($ingredient) {
            $this->ingredients[] = [
                'ingredient_id' => $ingredient->id,
                'name' => $ingredient->name,
                'unit_name' => $ingredient->units->name ?? 'Unid',
                'quantity' => 1,
            ];
            $this->dispatch('alert', 'INGREDIENTE AGREGADO A LA RECETA.', 'success', 'ingredient');
        }
    }

    public function updateIngredientQuantity($index, $value)
    {
        if (!isset($this->ingredients[$index]))
            return;
        $qty = floatval($value);
        $this->ingredients[$index]['quantity'] = $qty;
    }

    public function removeIngredient($index)
    {
        unset($this->ingredients[$index]);
        $this->ingredients = array_values($this->ingredients);
        $this->dispatch('alert', 'INGREDIENTE ELIMINADO DE LA RECETA.', 'success', 'ingredient');
    }

    public function resetIngredient()
    {
        $this->isIngredientMode = true;
        $this->searchIngredient = '';
    }

    public function toggleIngredientMode()
    {
        $this->isIngredientMode = !$this->isIngredientMode;
        $this->searchIngredient = '';
        $this->resetValidation();
    }

    public function listComboProducts()
    {
        $warehouseId = $this->getDefaultWarehouseId();
        $query = Product::with([
            'inventories' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        ])
            ->where('status', 1)
            ->whereIn('type', [0, 1, 2])
            ->where('name', 'like', '%' . $this->searchComboProduct . '%');

        if ($this->product_id) {
            $query->where('id', '!=', $this->product_id);
        }

        $this->list_combo_products = $query->get();
    }

    public function addComboProduct($id)
    {
        foreach ($this->combo_products as $item) {
            if ($item['product_id'] == $id) {
                $this->dispatch('alert', 'ESTE PRODUCTO YA FUE AGREGADO AL COMBO.', 'warning');
                return;
            }
        }

        $warehouseId = $this->getDefaultWarehouseId();
        $product = Product::with([
            'inventories' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        ])->find($id);

        if ($product) {
            $inv = $product->inventories->first();
            $this->combo_products[] = [
                'product_id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'purchase_price' => $inv ? $inv->purchase_price : 0,
                'sale_price' => $inv ? $inv->sale_price : 0,
                'quantity' => 1,
            ];
            $this->calculateComboTotals();
            $this->dispatch('alert', 'PRODUCTO AGREGADO AL COMBO.', 'success', 'combo');
        }
    }

    public function updateComboProductQuantity($index, $value)
    {
        if (!isset($this->combo_products[$index]))
            return;
        $this->combo_products[$index]['quantity'] = intval($value);
        $this->calculateComboTotals();
    }

    public function removeComboProduct($index)
    {
        unset($this->combo_products[$index]);
        $this->combo_products = array_values($this->combo_products);
        $this->calculateComboTotals();
        $this->dispatch('alert', 'PRODUCTO ELIMINADO DEL COMBO.', 'success');
    }

    public function calculateComboTotals()
    {
        $total = 0;
        foreach ($this->combo_products as $item) {
            $total += (intval($item['quantity']) * floatval($item['sale_price']));
        }
        $this->sale_price = number_format($total, 2, '.', '');
    }

    public function resetComboSearch()
    {
        $this->searchComboProduct = '';
    }

    public function listPackaging()
    {
        $query = Product::where('status', 1)
            ->where('type', 4)
            ->where('name', 'like', '%' . $this->searchPackaging . '%');

        if ($this->product_id) {
            $query->where('id', '!=', $this->product_id);
        }

        $this->list_packagings = $query->get();
    }

    public function addPackagingToProduct($id)
    {
        foreach ($this->packagings as $item) {
            if ($item['packaging_id'] == $id) {
                $this->dispatch('alert', 'ESTE EMPAQUE YA FUE AGREGADO.', 'warning');
                return;
            }
        }

        $packaging = Product::with('units')->find($id);

        if ($packaging) {
            $this->packagings[] = [
                'packaging_id' => $packaging->id,
                'name' => $packaging->name,
                'unit_name' => $packaging->units->name ?? 'Unid',
                'quantity' => 1,
            ];
            $this->dispatch('alert', 'EMPAQUE AGREGADO AL PRODUCTO.', 'success', 'packaging');
        }
    }

    public function updatePackagingQuantity($index, $value)
    {
        if (!isset($this->packagings[$index]))
            return;
        $qty = floatval($value);
        $this->packagings[$index]['quantity'] = $qty;
    }

    public function removePackaging($index)
    {
        unset($this->packagings[$index]);
        $this->packagings = array_values($this->packagings);
        $this->dispatch('alert', 'EMPAQUE ELIMINADO.', 'success', 'packaging');
    }

    public function resetPackaging()
    {
        $this->isPackagingMode = true;
        $this->searchPackaging = '';
    }

    public function togglePackagingMode()
    {
        $this->isPackagingMode = !$this->isPackagingMode;
        $this->searchPackaging = '';
        $this->resetValidation();
    }

    public function listProductUnits()
    {
        $this->list_product_units = Unit::where('status', 1)
            ->where('name', 'like', '%' . $this->searchProductUnit . '%')
            ->get();
    }

    public function addUnitToProduct($id)
    {
        if ($this->unit_id == $id) {
            $this->dispatch('alert', 'ESTA UNIDAD YA ESTÁ SELECCIONADA COMO UNIDAD BASE EN INFORMACIÓN.', 'warning');
            return;
        }

        foreach ($this->product_units as $item) {
            if ($item['unit_id'] == $id) {
                $this->dispatch('alert', 'ESTA UNIDAD YA FUE AGREGADA.', 'warning');
                return;
            }
        }

        $unit = Unit::find($id);

        if ($unit) {
            $this->product_units[] = [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'unit_base_unit' => $unit->base_unit,
                'factor' => $unit->factor,
                'purchase_price' => 0,
                'price' => 0,
            ];
            $this->dispatch('alert', 'UNIDAD AGREGADA AL PRODUCTO.', 'success', 'productUnit');
        }
    }

    public function updateProductUnitPrice($index, $value)
    {
        if (!isset($this->product_units[$index]))
            return;
        $this->product_units[$index]['price'] = floatval($value);
    }

    public function updateProductUnitPurchasePrice($index, $value)
    {
        if (!isset($this->product_units[$index]))
            return;
        $this->product_units[$index]['purchase_price'] = floatval($value);
    }

    public function removeProductUnit($index)
    {
        unset($this->product_units[$index]);
        $this->product_units = array_values($this->product_units);
        $this->dispatch('alert', 'UNIDAD ELIMINADA.', 'success', 'productUnit');
    }

    public function resetProductUnitSearch()
    {
        $this->searchProductUnit = '';
    }

    public function delete($id)
    {
        $product = Product::find($id);

        if ($product) {
            $newEstado = $product->status == 1 ? 0 : 1;
            $product->update([
                'status' => $newEstado
            ]);
            $message = $newEstado == 1 ? 'PRODUCTO RESTAURADO EXITOSAMENTE.' : 'PRODUCTO ELIMINADO EXITOSAMENTE.';
            $this->dispatch('productDeleted', $message);
        } else {
            session()->flash('message', 'PRODUCTO NO ENCONTRADO.');
        }
    }

    public function updateSelectionType($index)
    {
        if (isset($this->additionals[$index])) {
            $this->additionals[$index]['selection_type'] = $this->additionals[$index]['selection_type'] == 1 ? 0 : 1;
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
        $rules = [
            'name_category' => 'required|unique:categories,name'
        ];

        $messages = [
            'name_category.required' => 'La categoria es requerida',
            'name_category.unique' => 'La categoria ya está en uso',
        ];

        $this->validate($rules, $messages);

        $categoriesData = [
            'name' => $this->name_category
        ];
        $category = Categorie::updateOrCreate($categoriesData);

        $this->resetInputCategoryBrandUnit();

        $this->categories = Categorie::where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
        $this->categorie_id = $category->id;

        $this->dispatch('alert', 'CATEGORIA CREADA CON ÉXITO.', 'success', 'category');
    }

    public function storeBrand()
    {
        $rules = [
            'name_brand' => 'required|unique:brands,name',
        ];

        $messages = [
            'name_brand.required' => 'La marca es requerida',
            'name_brand.unique' => 'La marca ya está en uso',
        ];

        $this->validate($rules, $messages);

        $brandsData = [
            'name' => $this->name_brand
        ];
        $brand = Brand::updateOrCreate($brandsData);

        $this->resetInputCategoryBrandUnit();

        $this->brands = Brand::where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
        $this->brand_id = $brand->id;
        $this->dispatch('alert', 'MARCA CREADA CON ÉXITO.', 'success', 'brand');
    }

    public function storeUnit()
    {
        $rules = [
            'name_unit' => 'required|unique:units,name',
        ];

        $messages = [
            'name_unit.required' => 'La unidad es requerida',
            'name_unit.unique' => 'La unidad ya está en uso',
        ];

        $this->validate($rules, $messages);

        $unitsData = [
            'name' => $this->name_unit,
            'base_unit' => $this->unit_base_unit ?: null,
            'factor' => $this->unit_factor ?: null,
        ];
        $unit = Unit::updateOrCreate(['name' => $this->name_unit], $unitsData);

        $this->resetInputCategoryBrandUnit();

        $this->units = Unit::where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
        $this->unit_id = $unit->id;
        $this->dispatch('alert', 'UNIDAD CREADA CON ÉXITO.', 'success', 'unit');
    }

    public function resetInputCategoryBrandUnit()
    {
        $this->resetErrorBag(['name_category']);
        $this->resetErrorBag(['name_brand']);
        $this->resetErrorBag(['name_unit']);
        $this->name_category = '';
        $this->name_brand = '';
        $this->name_unit = '';
        $this->unit_base_unit = '';
        $this->unit_factor = '';
    }

    public function storeAdditional()
    {
        $rules = [
            'name_additional' => 'required|unique:brands,name',
            'price' => 'required|min:0',
        ];

        $messages = [
            'name_additional.required' => 'La descripcion es requerida',
            'name_additional.unique' => 'La descripcion ya está en uso',
            'price.required' => 'El precio es requerido',
        ];

        $this->validate($rules, $messages);

        $additionalData = [
            'name' => $this->name_additional,
            'price' => $this->price
        ];
        $additional = Additional::updateOrCreate($additionalData);

        $this->resetInputRecipe();

        $this->dispatch('alert', 'ADICIONAL CREADA CON ÉXITO.', 'success', 'additional');
        $this->resetAdditional();
    }

    public function resetInputRecipe()
    {
        $this->resetErrorBag(['name_additional']);
        $this->resetErrorBag(['price']);
        $this->name_additional = '';
        $this->price = '';
        $this->resetErrorBag(['name_variant']);
        $this->resetErrorBag(['price_variant']);
        $this->name_variant = '';
        $this->price_variant = '';
    }

    public function listAdditional()
    {
        $this->list_additionals = Additional::where('status', 1)
            ->where('name', 'like', '%' . $this->searchAdditional . '%')
            ->get();
    }

    public function addAdditionalToProduct($id)
    {
        foreach ($this->additionals as $item) {
            if ($item['additional_id'] == $id) {
                $this->dispatch('alert', 'ESTE ADICIONAL YA FUE SELECCIONADO.', 'warning');
                return;
            }
        }

        $additional = Additional::find($id);

        if ($additional) {
            $this->additionals[] = [
                'additional_id' => $additional->id,
                'additional' => $additional,
                'selection_type' => 0,
                'price' => $additional->price ?? 0,
                'product_id' => $this->product_id ?? null,
            ];
            $this->dispatch('alert', 'ADICIONAL ASIGNADO CON ÉXITO.', 'success', 'additional');
        }
    }

    public function updateAdditionalPrice($index, $value)
    {
        if (!isset($this->additionals[$index]))
            return;

        $price = floatval($value);
        $this->additionals[$index]['price'] = $price;
        $this->dispatch('alert', 'PRECIO ACTUALIZADO CON ÉXITO.', 'success', 'additional');
    }

    public function removeAdditional($index)
    {
        unset($this->additionals[$index]);
        $this->additionals = array_values($this->additionals);
        $this->dispatch('alert', 'ADICIONAL ELIMINADO CON ÉXITO.', 'success', 'additional');
    }

    public function resetAdditional()
    {
        $this->isAdditionalMode = true;
        $this->searchAdditional = '';
        $this->resetValidation();
        $this->resetInputRecipe();
    }

    public function toggleAdditionalMode()
    {
        $this->isAdditionalMode = !$this->isAdditionalMode;
        $this->searchAdditional = '';
        $this->resetValidation();
        $this->resetInputRecipe();
    }

    public function storeVariant()
    {
        $rules = [
            'name_variant' => 'required|unique:brands,name',
            'price_variant' => 'required|min:0',
        ];

        $messages = [
            'name_variant.required' => 'La descripcion es requerida',
            'name_variant.unique' => 'La descripcion ya está en uso',
            'price_variant.required' => 'El precio es requerido',
        ];

        $this->validate($rules, $messages);

        $variantData = [
            'name' => $this->name_variant,
            'price' => $this->price_variant
        ];
        $variant = Variant::updateOrCreate($variantData);

        $this->resetInputRecipe();

        $this->dispatch('alert', 'VARIANTE CREADA CON ÉXITO.', 'success', 'variant');
        $this->resetVariant();
    }

    public function listVariant()
    {
        $this->list_variants = Variant::where('status', 1)
            ->where('name', 'like', '%' . $this->searchVariant . '%')
            ->get();
    }

    public function addVariantToProduct($id)
    {
        foreach ($this->variants as $item) {
            if ($item['variant_id'] == $id) {
                $this->dispatch('alert', 'ESTA VARIANTE YA FUE SELECCIONADO.', 'warning');
                return;
            }
        }

        $variant = Variant::find($id);

        if ($variant) {
            $this->variants[] = [
                'variant_id' => $variant->id,
                'variant' => $variant,
                'selection_type' => 0,
                'price_variant' => $variant->price ?? 0,
                'product_id' => $this->product_id ?? null,
            ];
            $this->dispatch('alert', 'VARIANTES ASIGNADO CON ÉXITO.', 'success', 'variant');
        }
    }

    public function updateVariantPrice($index, $value)
    {
        if (!isset($this->variants[$index]))
            return;

        $price_variant = floatval($value);
        $this->variants[$index]['price_variant'] = $price_variant;
        $this->dispatch('alert', 'PRECIO ACTUALIZADO CON ÉXITO.', 'success', 'variant');
    }

    public function removeVariant($index)
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
        $this->dispatch('alert', 'VARIANTES ELIMINADO CON ÉXITO.', 'success', 'variant');
    }

    public function resetVariant()
    {
        $this->isVariantMode = true;
        $this->searchVariant = '';
        $this->resetValidation();
        $this->resetInputRecipe();
    }

    public function toggleVariantMode()
    {
        $this->isVariantMode = !$this->isVariantMode;
        $this->searchVariant = '';
        $this->resetValidation();
        $this->resetInputRecipe();
    }
}
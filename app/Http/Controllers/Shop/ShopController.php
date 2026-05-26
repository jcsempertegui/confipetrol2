<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Categorie;
use App\Models\Warehouse;
use App\Models\Brand;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = Warehouse::where('branch_id', 1)->where('is_default', 1)->value('id') ?? 1;

        $categories = Categorie::where('status', 1)->where('id', '!=', 1)->orderBy('sort_order', 'asc')->get();

        $featuredProducts = Product::select(['id', 'code', 'name', 'image', 'type', 'categorie_id'])
            ->with([
                'inventories' => function ($q) use ($warehouseId) {
                    $q->select('product_id', 'stock', 'stock_lot', 'stock_nolot', 'sale_price', 'warehouse_id')
                        ->where('warehouse_id', $warehouseId);
                }
            ])
            ->where('status', 1)
            ->whereIn('type', [0, 1, 5])
            ->inRandomOrder()
            ->limit(12)
            ->get();

        return view('shop.index', compact('categories', 'featuredProducts'));
    }

    public function products(Request $request)
    {
        $categoryId = $request->get('category', 0);
        $search = $request->get('search', '');
        $brandId = $request->get('brand', 0);
        $minPrice = $request->get('min_price', 0);
        $maxPrice = $request->get('max_price', 999999);

        $warehouseId = Warehouse::where('branch_id', 1)->where('is_default', 1)->value('id') ?? 1;

        $categories = Categorie::where('status', 1)->where('id', '!=', 1)->orderBy('sort_order', 'asc')->get();
        $brands = Brand::where('status', 1)->orderBy('name', 'asc')->get();

        $query = Product::select(['id', 'code', 'name', 'image', 'type', 'categorie_id', 'brand_id', 'lote', 'features'])
            ->with([
                'inventories' => function ($q) use ($warehouseId) {
                    $q->select('product_id', 'stock', 'stock_lot', 'stock_nolot', 'sale_price', 'warehouse_id')
                        ->where('warehouse_id', $warehouseId);
                }
            ])
            ->where('status', 1)
            ->whereIn('type', [0, 1, 5]);

        if ($categoryId != 0) {
            $query->where('categorie_id', $categoryId);
        }

        if ($brandId != 0) {
            $query->where('brand_id', $brandId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(24)->withQueryString();

        $productIds = $products->pluck('id')->toArray();

        $skusByProduct = \DB::table('product_skus')
            ->join('sizes', 'product_skus.size_id', '=', 'sizes.id')
            ->leftJoin('colors', 'product_skus.color_id', '=', 'colors.id')
            ->where('product_skus.branch_id', 1)
            ->where('product_skus.stock', '>', 0)
            ->whereIn('product_skus.product_id', $productIds)
            ->select('product_skus.id', 'product_skus.product_id', 'product_skus.price', 'product_skus.stock', 'sizes.name as size_name', 'colors.name as color_name')
            ->get()
            ->groupBy('product_id');

        $unitsByProduct = \DB::table('product_units')
            ->join('units', 'product_units.unit_id', '=', 'units.id')
            ->where('product_units.status', 1)
            ->whereIn('product_units.product_id', $productIds)
            ->select('product_units.id', 'product_units.product_id', 'units.name', 'product_units.price', 'units.factor')
            ->get()
            ->groupBy('product_id');

        return view('shop.products', compact('categories', 'brands', 'products', 'categoryId', 'search', 'brandId', 'minPrice', 'maxPrice', 'skusByProduct', 'unitsByProduct', 'warehouseId'));
    }
    public function detail($id)
    {
        $warehouseId = Warehouse::where('branch_id', 1)->where('is_default', 1)->value('id') ?? 1;

        $product = Product::with([
            'categories',
            'brands',
            'units',
            'images',
            'inventories' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        ])->where('status', 1)->findOrFail($id);

        $relatedProducts = Product::select(['id', 'code', 'name', 'image', 'type'])
            ->with([
                'inventories' => function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }
            ])
            ->where('categorie_id', $product->categorie_id)
            ->where('id', '!=', $product->id)
            ->where('status', 1)
            ->limit(4)
            ->get();

        $inv = is_iterable($product->inventories) ? $product->inventories->first() : $product->inventories;
        $basePrice = $inv ? $inv->sale_price : 0;

        $skus = \DB::table('product_skus')
            ->join('sizes', 'product_skus.size_id', '=', 'sizes.id')
            ->leftJoin('colors', 'product_skus.color_id', '=', 'colors.id')
            ->where('product_skus.product_id', $id)
            ->where('product_skus.branch_id', 1)
            ->where('product_skus.stock', '>', 0)
            ->select('product_skus.id', 'product_skus.price', 'product_skus.stock', 'sizes.name as size_name', 'colors.name as color_name')
            ->get();

        $hasSkus = $skus->count() > 0;

        $productUnits = \DB::table('product_units')
            ->join('units', 'product_units.unit_id', '=', 'units.id')
            ->where('product_units.product_id', $id)
            ->where('product_units.status', 1)
            ->select('product_units.id', 'units.name', 'product_units.price', 'units.factor')
            ->get();

        $hasUnits = $productUnits->count() > 0;

        $stock = $inv ? ($product->lote == 1 ? $inv->stock_lot : $inv->stock_nolot) : 0;

        $unitsForJs = [];
        if ($hasUnits) {
            $baseUnitName = $product->units ? $product->units->name : 'UNIDAD';
            $unitsForJs[] = ['id' => 0, 'name' => $baseUnitName, 'factor' => 1, 'price' => $basePrice, 'stock' => $stock];
            foreach ($productUnits as $pu) {
                $factor = $pu->factor > 0 ? $pu->factor : 1;
                $unitsForJs[] = [
                    'id' => $pu->id,
                    'name' => $pu->name,
                    'factor' => $pu->factor,
                    'price' => $pu->price > 0 ? $pu->price : $basePrice * $factor,
                    'stock' => floor($stock / $factor)
                ];
            }
        }

        return view('shop.detail', compact('product', 'relatedProducts', 'hasSkus', 'hasUnits', 'skus', 'unitsForJs', 'basePrice', 'stock'));
    }
}
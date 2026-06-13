<?php

namespace Database\Seeders;

use App\Models\Inventorie;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 5000000; $i++) {
            $code = "P" . str_pad($i, 5, "0", STR_PAD_LEFT); 
            $name = "Producto $i";

            // Crear producto
            $product = Product::create([
                'code' => $code,
                'name' => $name,
                'features' => "Características del producto $i",
                'image' => null,
                'lote' => 0,
                'categorie_id' => 1, 
                'brand_id' => 1,     
                'unit_id' => 1,      
            ]);

            Inventorie::create([
                'stock_lot' => 0,
                'stock_nolot' => rand(10, 100),
                'product_id' => $product->id,
                'branch_id' => 1,
            ]);
        }
    }
}

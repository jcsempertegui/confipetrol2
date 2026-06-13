<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Categorie;
use App\Models\Brand;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProductsImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $warehouse_id;

    public function __construct($warehouse_id)
    {
        $this->warehouse_id = $warehouse_id;
    }
    
    public function model(array $row)
    {
        if (Product::where('code', $row['codigo'])->exists()) {
            return null;
        }

        $categorie = Categorie::firstOrCreate(
            ['name' => $row['categoria']],
            ['status' => 1]
        );

        $brand = Brand::firstOrCreate(
            ['name' => $row['marca']],
            ['status' => 1]
        );

        $unit = Unit::firstOrCreate(
            ['name' => $row['unidad'] ?? 'Unidad'],
            ['status' => 1]
        );

        return new Product([
            'code' => $row['codigo'],
            'name' => $row['producto'],
            'categorie_id' => $categorie->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'type' => 2,
            'lote' => 0,
            'minimum_stock' => 0,
            'status' => 1
        ]);
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
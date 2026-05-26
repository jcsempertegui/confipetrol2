<?php

namespace Database\Seeders;

use App\Models\Branche;
use App\Models\Printer;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrancheSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branche::create([
            'code' => 'CM01',
            'branch_type' => 'Casa Matriz',
            'name' => 'CASA MATRIZ',
            'phone' => '7065987',
            'address' => 'MIRAFLORES',
        ]);

        Warehouse::create([
            'name' => 'Almacén Principal',
            'branch_id' => $branch->id,
            'is_default' => 1,
            'status' => 1
        ]);
    }
}
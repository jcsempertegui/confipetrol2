<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(BrancheSeeder::class);
        $this->call(SettingSeeder::class);

        DB::table('categories')->insert([
            [
                'name' => 'GENERAL',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);

        DB::table('brands')->insert([
            [
                'name' => 'GENERAL',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);

        DB::table('units')->insert([
            [
                'name' => 'UNIDAD',
                'base_unit' => 'UNIDAD',
                'factor' => 1,
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);

        DB::table('customers')->insert([
            [
                'name' => 'PUBLICO GENERAL',
                'document_type' => 'CI',
                'document' => '000',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);

        DB::table('suppliers')->insert([
            [
                'name' => 'PROVEEDOR GENERAL',
                'contact_person' => 'SIN CONTACTO',
                'document' => '000',
                'phone' => null,
                'address' => 'SIN DIRECCIÓN',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);

        DB::table('colors')->insert([
            ['name' => 'SIN COLOR', 'status' => 1],
            ['name' => 'NEGRO',     'status' => 1],
            ['name' => 'BLANCO',    'status' => 1],
            ['name' => 'ROJO',      'status' => 1],
            ['name' => 'AZUL',      'status' => 1],
            ['name' => 'VERDE',     'status' => 1],
            ['name' => 'AMARILLO',  'status' => 1],
            ['name' => 'GRIS',      'status' => 1],
        ]);

        DB::table('sizes')->insert([
            ['name' => 'SIN TALLA', 'status' => 1],
            ['name' => 'XS',        'status' => 1],
            ['name' => 'S',         'status' => 1],
            ['name' => 'M',         'status' => 1],
            ['name' => 'L',         'status' => 1],
            ['name' => 'XL',        'status' => 1],
            ['name' => 'XXL',       'status' => 1],
        ]);

        $this->call(UserSeeder::class);
    }
}
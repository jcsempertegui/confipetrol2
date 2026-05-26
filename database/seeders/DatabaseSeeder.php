<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
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

        /* $this->call(ProductSeeder::class);*/
        $this->call(UserSeeder::class);


    }
}
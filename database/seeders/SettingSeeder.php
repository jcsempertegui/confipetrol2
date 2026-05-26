<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::create([
            'business' => 'MASTEC POS',
            'owner' => '',
            'nit' => '000000',
            'email' => 'correo@gmail.com',
            'image' => '',
            'message' => '<p><strong>Gracias por su Compra!!! Vuelva Pronto.</strong></p>',
            'branch_id' => 1,
            
        ]);
       
    }
}
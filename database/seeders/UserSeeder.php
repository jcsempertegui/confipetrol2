<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Role::create(['name' => 'SUPER ADMIN']);
        $permisos = Permission::pluck('id', 'id')->all();
        $rol->syncPermissions($permisos);

        $user1 = User::create([
            'login'     => 'mastec',
            'name'      => 'Mastec Digital',
            'lastname'  => 'mastec',
            'document'  => '0000000',
            'phone'     => '000000',
            'email'     => 'mastecdigital@gmail.com',
            'password'  => bcrypt('md2601'),
            'branch_id' => 1,
        ]);
        $user1->assignRole('SUPER ADMIN');

        $user2 = User::create([
            'login'     => 'admin',
            'name'      => 'Admin User',
            'lastname'  => 'admin',
            'document'  => '0000001',
            'phone'     => '000001',
            'email'     => 'adminuser@gmail.com',
            'password'  => bcrypt('ad2601'),
            'branch_id' => 1,
        ]);
        $user2->assignRole('SUPER ADMIN');
    }
}
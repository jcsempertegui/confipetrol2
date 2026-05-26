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
        // Crear usuario SUPER ADMIN
        $user1 = User::create([
            'login' => 'mastec',
            'name' => 'Mastec Digital',
            'lastname' => 'mastec',
            'document' => '0000000',
            'phone' => '000000',
            'email' => 'mastecdigital@gmail.com',
            'password' => bcrypt('md2601'),
            'branch_id' => 1,
        ]);

        // Crear rol SUPER ADMIN y asignar todos los permisos
        $rol = Role::create(['name' => 'SUPER ADMIN']);
        $permisos = Permission::pluck('id', 'id')->all();
        $rol->syncPermissions($permisos);

        $user1->assignRole('SUPER ADMIN');

        // Crear usuario ADMIN
        $user2 = User::create([
            'login' => 'admin',
            'name' => 'Admin User',
            'lastname' => 'admin',
            'document' => '0000001',
            'phone' => '000001',
            'email' => 'adminuser@gmail.com',
            'password' => bcrypt('ad2601'),
            'branch_id' => 1,
        ]);

        // Crear rol ADMIN y asignar permisos limitados
        $rolAdmin = Role::create(['name' => 'ADMIN']);
        $permisosAdmin = Permission::whereNotIn('name', [
            'ver-rol',
            'ver-ajustesadicionales',
            'ver-sucursales',
            'ver-importar',
        ])->pluck('id', 'id')->all();

        $rolAdmin->syncPermissions($permisosAdmin);

        $user2->assignRole('ADMIN');


        $userQA = User::create([
            'login' => 'test',
            'name' => 'QA',
            'lastname' => 'Tester',
            'document' => '2002155',
            'phone' => '70012345',
            'email' => 'test@qa.com',
            'password' => bcrypt('mastec26'),
            'branch_id' => 1,
        ]);

        $userQA->assignRole('ADMIN');
        // ================================
        // Crear 5000 usuarios adicionales
        // ================================
        /*for ($i = 1; $i <= 5; $i++) {
            $name = "Usuario$i";
            $lastname = "Test$i";
            $login = "user$i";
            $document = '1000000' + $i;
            $email = "user$i@example.com";

            $user = User::create([
                'login' => $login,
                'name' => $name,
                'lastname' => $lastname,
                'document' => $document,
                'phone' => '700000' . $i,
                'email' => $email,
                'password' => bcrypt("password123"),
                'branch_id' => 1,
            ]);

            // Asignar rol ADMIN por defecto a los usuarios random
            $user->assignRole('ADMIN');
        }*/
    }
}
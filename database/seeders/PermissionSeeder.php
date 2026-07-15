<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'Usuarios' => ['ver-usuario', 'crear-usuario', 'editar-usuario', 'eliminar-usuario', 'restaurar-usuario'],
            'Roles' => ['ver-rol', 'crear-rol', 'editar-rol', 'eliminar-rol', 'restaurar-rol'],
            'Logs' => ['ver-log'],
            'Backups' => ['ver-backup', 'crear-backup', 'restaurar-backup', 'eliminar-backup'],
            'Categorías' => ['ver-categoria', 'crear-categoria', 'editar-categoria', 'eliminar-categoria', 'gestionar-atributos'],
            'Productos' => ['ver-producto', 'crear-producto', 'editar-producto', 'eliminar-producto'],
        ];
        foreach ($permissions as $group => $names) {
            foreach ($names as $name) {
                Permission::updateOrCreate(['name' => $name, 'guard_name' => 'web'], ['grupo' => $group]);
            }
        }
    }
}

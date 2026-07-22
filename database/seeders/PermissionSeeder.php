<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'Usuarios' => ['ver-usuario', 'crear-usuario', 'editar-usuario', 'eliminar-usuario', 'restaurar-usuario'],
            'Roles' => ['ver-rol', 'crear-rol', 'editar-rol', 'eliminar-rol', 'restaurar-rol'],
            'Logs' => ['ver-log', 'exportar-log'],
            'Backups' => ['ver-backup', 'crear-backup', 'descargar-backup', 'restaurar-backup', 'eliminar-backup'],
            'Categorías' => ['ver-categoria', 'crear-categoria', 'editar-categoria', 'eliminar-categoria', 'gestionar-atributos'],
            'Productos' => ['ver-producto', 'crear-producto', 'editar-producto', 'eliminar-producto'],
            'Trabajadores' => ['ver-trabajador', 'crear-trabajador', 'editar-trabajador', 'eliminar-trabajador', 'restaurar-trabajador'],
            'Remitos' => ['ver-remito', 'crear-remito', 'editar-remito', 'eliminar-remito', 'confirmar-remito', 'anular-remito'],
            'Entregas' => ['ver-entrega', 'crear-entrega', 'editar-entrega', 'eliminar-entrega', 'confirmar-entrega', 'anular-entrega'],
            'Inventario' => ['ver-inventario', 'ver-kardex'],
            'Reportes' => ['ver-reporte', 'exportar-reporte'],
        ];
        foreach ($permissions as $group => $names) {
            foreach ($names as $name) {
                Permission::updateOrCreate(['name' => $name, 'guard_name' => 'web'], ['grupo' => $group]);
            }
        }

        Role::where('name', 'SUPER ADMIN')->where('guard_name', 'web')->first()?->syncPermissions(Permission::all());
    }
}

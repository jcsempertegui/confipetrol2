<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            // Administracion
            ['name' => 'ver-ajustes',       'grupo' => 'Administracion'],
            ['name' => 'ver-log',            'grupo' => 'Administracion'],
            ['name' => 'ver-sucursales',     'grupo' => 'Administracion'],
            ['name' => 'crear-sucursal',     'grupo' => 'Administracion'],
            ['name' => 'editar-sucursal',    'grupo' => 'Administracion'],
            ['name' => 'eliminar-sucursal',  'grupo' => 'Administracion'],
            ['name' => 'cambiar-sucursal',   'grupo' => 'Administracion'],

            // Usuarios
            ['name' => 'ver-usuario',        'grupo' => 'Usuarios'],
            ['name' => 'crear-usuario',      'grupo' => 'Usuarios'],
            ['name' => 'editar-usuario',     'grupo' => 'Usuarios'],
            ['name' => 'eliminar-usuario',   'grupo' => 'Usuarios'],
            ['name' => 'restaurar-usuario',  'grupo' => 'Usuarios'],

            // Roles
            ['name' => 'ver-rol',            'grupo' => 'Roles'],
            ['name' => 'crear-rol',          'grupo' => 'Roles'],
            ['name' => 'editar-rol',         'grupo' => 'Roles'],
            ['name' => 'eliminar-rol',       'grupo' => 'Roles'],
            ['name' => 'restaurar-rol',      'grupo' => 'Roles'],

            // Productos
            ['name' => 'ver-productos',      'grupo' => 'Productos'],
            ['name' => 'importar-productos', 'grupo' => 'Productos'],
            ['name' => 'crear-productos',    'grupo' => 'Productos'],
            ['name' => 'editar-productos',   'grupo' => 'Productos'],
            ['name' => 'eliminar-productos', 'grupo' => 'Productos'],
            ['name' => 'restaurar-productos','grupo' => 'Productos'],

            // Unidades
            ['name' => 'ver-unidades',       'grupo' => 'Unidades'],
            ['name' => 'crear-unidades',     'grupo' => 'Unidades'],
            ['name' => 'editar-unidades',    'grupo' => 'Unidades'],
            ['name' => 'eliminar-unidades',  'grupo' => 'Unidades'],
            ['name' => 'restaurar-unidades', 'grupo' => 'Unidades'],

            // Tallas
            ['name' => 'ver-tallas',         'grupo' => 'Tallas'],
            ['name' => 'crear-tallas',       'grupo' => 'Tallas'],
            ['name' => 'editar-tallas',      'grupo' => 'Tallas'],
            ['name' => 'eliminar-tallas',    'grupo' => 'Tallas'],
            ['name' => 'restaurar-tallas',   'grupo' => 'Tallas'],

            // Colores
            ['name' => 'ver-colores',        'grupo' => 'Colores'],
            ['name' => 'crear-colores',      'grupo' => 'Colores'],
            ['name' => 'editar-colores',     'grupo' => 'Colores'],
            ['name' => 'eliminar-colores',   'grupo' => 'Colores'],
            ['name' => 'restaurar-colores',  'grupo' => 'Colores'],

            // Categorias
            ['name' => 'ver-categorias',     'grupo' => 'Categorias'],
            ['name' => 'crear-categorias',   'grupo' => 'Categorias'],
            ['name' => 'editar-categorias',  'grupo' => 'Categorias'],
            ['name' => 'eliminar-categorias','grupo' => 'Categorias'],
            ['name' => 'restaurar-categorias','grupo' => 'Categorias'],

            // Marcas
            ['name' => 'ver-marcas',         'grupo' => 'Marcas'],
            ['name' => 'crear-marcas',       'grupo' => 'Marcas'],
            ['name' => 'editar-marcas',      'grupo' => 'Marcas'],
            ['name' => 'eliminar-marcas',    'grupo' => 'Marcas'],
            ['name' => 'restaurar-marcas',   'grupo' => 'Marcas'],

            // Trabajadores
            ['name' => 'ver-trabajadores',       'grupo' => 'Trabajadores'],
            ['name' => 'crear-trabajadores',     'grupo' => 'Trabajadores'],
            ['name' => 'editar-trabajadores',    'grupo' => 'Trabajadores'],
            ['name' => 'eliminar-trabajadores',  'grupo' => 'Trabajadores'],
            ['name' => 'restaurar-trabajadores', 'grupo' => 'Trabajadores'],

            // Inventario
            ['name' => 'ver-stock',          'grupo' => 'Inventario'],
            ['name' => 'ver-kardex',         'grupo' => 'Inventario'],

            // Entregas
            ['name' => 'nueva-entrega',      'grupo' => 'Entregas'],
            ['name' => 'listar-entrega',     'grupo' => 'Entregas'],
            ['name' => 'eliminar-entrega',   'grupo' => 'Entregas'],

            // Remitos
            ['name' => 'nuevo-remito',       'grupo' => 'Remitos'],
            ['name' => 'listar-remito',      'grupo' => 'Remitos'],
            ['name' => 'eliminar-remito',    'grupo' => 'Remitos'],

            // Reportes
            ['name' => 'ver-reporteentrega', 'grupo' => 'Reportes'],
            ['name' => 'ver-reporteremito',  'grupo' => 'Reportes'],
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(
                ['name' => $permiso['name'], 'guard_name' => 'web'],
                ['grupo' => $permiso['grupo']]
            );
        }
    }
}

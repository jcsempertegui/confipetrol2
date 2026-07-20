<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div><img src="{{ asset('assets/images/login.png') }}" class="logo-icon" alt="Confipetrol"></div>
        <div><h4 class="logo-text">CONFIPETROL</h4></div>
        <div class="toggle-icon ms-auto"><i class="bx bx-menu-alt-right"></i></div>
    </div>
    <ul class="metismenu" id="menu">
        <li class="{{ request()->routeIs('home') ? 'mm-active' : '' }}"><a href="{{ route('home') }}"><div class="parent-icon"><i class="bx bx-home-circle"></i></div><div class="menu-title">Inicio</div></a></li>
        @canany(['ver-producto','ver-categoria'])
        <li class="menu-label">Productos</li>
        @can('ver-categoria')<li class="{{ request()->routeIs('categories') ? 'mm-active' : '' }}"><a href="{{ route('categories') }}"><div class="parent-icon"><i class="bx bx-category"></i></div><div class="menu-title">Categorías y atributos</div></a></li>@endcan
        @can('ver-producto')<li class="{{ request()->routeIs('products') ? 'mm-active' : '' }}"><a href="{{ route('products') }}"><div class="parent-icon"><i class="bx bx-package"></i></div><div class="menu-title">Productos</div></a></li>@endcan
        @endcanany
        @canany(['ver-trabajador','ver-remito','ver-entrega'])
        <li class="menu-label">Movimientos</li>
        @can('ver-trabajador')<li class="{{ request()->routeIs('workers') ? 'mm-active' : '' }}"><a href="{{ route('workers') }}"><div class="parent-icon"><i class="bx bx-group"></i></div><div class="menu-title">Trabajadores</div></a></li>@endcan
        @can('ver-remito')<li class="{{ request()->routeIs('dispatch-notes') ? 'mm-active' : '' }}"><a href="{{ route('dispatch-notes') }}"><div class="parent-icon"><i class="bx bx-transfer"></i></div><div class="menu-title">Remitos</div></a></li>@endcan
        @can('ver-entrega')<li class="{{ request()->routeIs('deliveries') ? 'mm-active' : '' }}"><a href="{{ route('deliveries') }}"><div class="parent-icon"><i class="bx bx-package"></i></div><div class="menu-title">Entregas</div></a></li>@endcan
        @endcanany
        @can('ver-inventario')<li class="menu-label">Inventario y Kardex</li><li class="{{ request()->routeIs('inventory') ? 'mm-active' : '' }}"><a href="{{ route('inventory') }}"><div class="parent-icon"><i class="bx bx-bar-chart-alt-2"></i></div><div class="menu-title">Inventario y Kardex</div></a></li>@endcan
        @can('ver-reporte')<li class="menu-label">Reportes</li><li class="{{ request()->routeIs('reports') ? 'mm-active' : '' }}"><a href="{{ route('reports') }}"><div class="parent-icon"><i class="bx bx-file"></i></div><div class="menu-title">Reportes</div></a></li>@endcan
        <li class="menu-label">Administración</li>
        @can('ver-usuario')<li class="{{ request()->routeIs('users') ? 'mm-active' : '' }}"><a href="{{ route('users') }}"><div class="parent-icon"><i class="bx bx-user"></i></div><div class="menu-title">Usuarios</div></a></li>@endcan
        @can('ver-rol')<li class="{{ request()->routeIs('roles') ? 'mm-active' : '' }}"><a href="{{ route('roles') }}"><div class="parent-icon"><i class="bx bx-shield-quarter"></i></div><div class="menu-title">Roles y permisos</div></a></li>@endcan
        @can('ver-log')<li class="{{ request()->routeIs('logs') ? 'mm-active' : '' }}"><a href="{{ route('logs') }}"><div class="parent-icon"><i class="bx bx-history"></i></div><div class="menu-title">Logs</div></a></li>@endcan
        @can('ver-backup')<li class="{{ request()->routeIs('backups') ? 'mm-active' : '' }}"><a href="{{ route('backups') }}"><div class="parent-icon"><i class="bx bx-data"></i></div><div class="menu-title">Backups</div></a></li>@endcan
    </ul>
</div>

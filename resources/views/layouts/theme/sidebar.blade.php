<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div><img src="{{ asset('assets/images/login.png') }}" class="logo-icon" alt="Confipetrol"></div>
        <div><h4 class="logo-text">CONFIPETROL</h4></div>
        <div class="toggle-icon ms-auto"><i class="bx bx-menu-alt-right"></i></div>
    </div>
    <ul class="metismenu" id="menu">
        <li><a href="{{ route('home') }}"><div class="parent-icon"><i class="bx bx-home-circle"></i></div><div class="menu-title">Inicio</div></a></li>
        @canany(['ver-producto','ver-categoria'])
        <li class="menu-label">Productos</li>
        @can('ver-categoria')<li><a href="{{ route('categories') }}"><div class="parent-icon"><i class="bx bx-category"></i></div><div class="menu-title">1. Categorías y atributos</div></a></li>@endcan
        @can('ver-producto')<li><a href="{{ route('products') }}"><div class="parent-icon"><i class="bx bx-package"></i></div><div class="menu-title">2. Productos</div></a></li>@endcan
        @endcanany
        <li class="menu-label">Administración</li>
        @can('ver-usuario')<li><a href="{{ route('users') }}"><div class="parent-icon"><i class="bx bx-user"></i></div><div class="menu-title">Usuarios</div></a></li>@endcan
        @can('ver-rol')<li><a href="{{ route('roles') }}"><div class="parent-icon"><i class="bx bx-shield-quarter"></i></div><div class="menu-title">Roles y permisos</div></a></li>@endcan
        @can('ver-log')<li><a href="{{ route('logs') }}"><div class="parent-icon"><i class="bx bx-history"></i></div><div class="menu-title">Logs</div></a></li>@endcan
        @can('ver-backup')<li><a href="{{ route('backups') }}"><div class="parent-icon"><i class="bx bx-data"></i></div><div class="menu-title">Backups</div></a></li>@endcan
    </ul>
</div>

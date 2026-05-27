@php
    $userBranch = session('branch_user_id', auth()->check() ? auth()->user()->branch_id : null);
    $sidebarCache = cache()->remember('sidebar_branch_' . $userBranch, 60 * 60 * 24, function () use ($userBranch) {
        $setting = $userBranch ? \DB::table('settings')->where('branch_id', $userBranch)->first() : \DB::table('settings')->first();
        $branch = $userBranch ? \DB::table('branches')->where('id', $userBranch)->first() : null;
        return [
            'logoImage' => (!empty($setting) && !empty($setting->image)) ? 'storage/' . $setting->image : 'assets/images/login.png',
            'businessName' => (!empty($setting) && !empty($setting->business)) ? $setting->business : 'MASTEC POS',
            'enableSizeColor' => $branch ? $branch->enable_size_color : 0,
            'invoiceType' => $branch ? $branch->invoice_type : 'ninguno',
        ];
    });

    $logoImage = asset($sidebarCache['logoImage']);
    $businessName = $sidebarCache['businessName'];
    $enableSizeColor = $sidebarCache['enableSizeColor'];
    $invoiceType = $sidebarCache['invoiceType'];
@endphp
<div class="sidebar-wrapper" data-simplebar="true" x-data="{
    enableSizeColor: {{ $enableSizeColor }},
    invoiceType: '{{ $invoiceType }}',
    businessName: '{{ addslashes($businessName) }}',
    logoImage: '{{ $logoImage }}'
}" @update-sidebar.window="
    let data = Array.isArray($event.detail) ? $event.detail[0] : $event.detail;
    if(data.enableSizeColor !== undefined) enableSizeColor = data.enableSizeColor;
    if(data.invoiceType !== undefined) invoiceType = data.invoiceType;
    if(data.businessName !== undefined) businessName = data.businessName;
    if(data.logoImage !== undefined) logoImage = data.logoImage;
">
    <div class="sidebar-header">
        <div>
            <img :src="logoImage" src="{{ $logoImage }}" class="logo-icon" alt="logo icon"
                onerror="this.src='{{ asset('assets/images/login.png') }}'">
        </div>
        <div style="flex: 1; min-width: 0; padding-right: 5px; overflow: hidden;">
            <h4 class="logo-text" x-text="businessName"
                style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; width: 100%;">
                {{ $businessName }}
            </h4>
        </div>
        <div class="toggle-icon ms-auto"><i class='bx bx-menu-alt-right'></i>
        </div>
    </div>
    <ul class="metismenu" id="menu">
        <li>
            <a href="{{ url('home') }}">
                <div class="parent-icon"><i class='bx bx-home-alt'></i></div>
                <div class="menu-title">Dashboard</div>
            </a>
        </li>

        @canany(['ver-usuario', 'ver-rol', 'ver-ajustes', 'ver-log', 'ver-sucursales', 'ver-planillas'])
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="bx bx-cog"></i></div>
                    <div class="menu-title">Administracion</div>
                </a>
                <ul>
                    @can('ver-usuario')
                        <li><a href="{{ url('users') }}"><i class="bx bx-radio-circle"></i> Usuarios</a></li>
                    @endcan
                    @can('ver-rol')
                        <li><a href="{{ url('roles') }}"><i class="bx bx-radio-circle"></i> Roles</a></li>
                    @endcan
                    @can('ver-ajustes')
                        <li><a href="{{ url('settings') }}"><i class="bx bx-radio-circle"></i> Ajustes</a></li>
                    @endcan
                    @can('ver-sucursales')
                        <li><a href="{{ url('branches') }}"><i class="bx bx-radio-circle"></i> Sucursales</a></li>
                        <li><a href="{{ url('warehouses') }}"><i class="bx bx-radio-circle"></i> Almacenes</a></li>
                    @endcan
                    @can('ver-log')
                        <li><a href="{{ url('logs') }}"><i class="bx bx-radio-circle"></i> Log de Acceso</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        @canany(['ver-productos', 'ver-categorias', 'ver-marcas', 'ver-unidades', 'ver-variantes', 'ver-adicionales', 'importar-productos'])
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="bx bx-package"></i></div>
                    <div class="menu-title">Productos</div>
                </a>
                <ul>
                    @can('ver-productos')
                        <li><a href="{{ url('products') }}"><i class="bx bx-radio-circle"></i> Productos</a></li>
                    @endcan
                    @can('ver-categorias')
                        <li><a href="{{ url('categories') }}"><i class="bx bx-radio-circle"></i> Categorías</a></li>
                    @endcan
                    @can('ver-unidades')
                        <li><a href="{{ url('units') }}"><i class="bx bx-radio-circle"></i> Unidad de Medida</a></li>
                    @endcan
                    @can('ver-marcas')
                        <li><a href="{{ url('brands') }}"><i class="bx bx-radio-circle"></i> Marcas</a></li>
                    @endcan
                    @can('ver-tallas')
                        <li><a href="{{ url('sizes') }}"><i class="bx bx-radio-circle"></i> Tallas</a></li>
                    @endcan
                    @can('ver-colores')
                        <li><a href="{{ url('colors') }}"><i class="bx bx-radio-circle"></i> Colores</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        @can('ver-trabajadores')
            <li>
                <a href="{{ url('workers') }}">
                    <div class="parent-icon"><i class="bx bx-group"></i></div>
                    <div class="menu-title">Trabajadores</div>
                </a>
            </li>
        @endcan
       

        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bx bx-cart"></i></div>
                <div class="menu-title">Entregas</div>
            </a>
            <ul>
                <li><a href="{{ url('deliveries') }}"><i class="bx bx-radio-circle"></i> Nueva Entrega</a></li>
                <li><a href="{{ url('delivery_lists') }}"><i class="bx bx-radio-circle"></i> Listar Entregas</a></li>
            </ul>
        </li>

        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bx bx-receipt"></i></div>
                <div class="menu-title">Remitos</div>
            </a>
            <ul>
                <li><a href="{{ url('remitos') }}"><i class="bx bx-radio-circle"></i> Nuevo Remito</a></li>
                <li><a href="{{ url('remito_lists') }}"><i class="bx bx-radio-circle"></i> Listar Remitos</a></li>
            </ul>
        </li>

        @canany(['ver-stock', 'ver-kardex'])
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="bx bx-store"></i></div>
                    <div class="menu-title">Inventario</div>
                </a>
                <ul>
                    @can('ver-stock')
                        <li><a href="{{ url('inventories') }}"><i class="bx bx-radio-circle"></i> Stock</a></li>
                    @endcan
                    @can('ver-kardex')
                        <li><a href="{{ url('kardexs') }}"><i class="bx bx-radio-circle"></i> Kardex</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        @canany(['ver-reporteventa', 'ver-reportecompra', 'ver-reporteingreso', 'ver-reporteganancias', 'ver-reporteestado', 'ver-reportevencimiento', 'ver-reportestockmin', 'ver-reportecomision'])
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="bx bx-bar-chart"></i></div>
                    <div class="menu-title">Reportes</div>
                </a>
                <ul>
                    <li><a href="{{ url('delivery_reports') }}"><i class="bx bx-radio-circle"></i> Reporte de Entregas</a>
                    </li>

                </ul>
            </li>
        @endcanany
    </ul>
</div>
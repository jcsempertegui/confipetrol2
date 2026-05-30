<?php

use App\Http\Controllers\BranchController;
use App\Livewire\ColorsController;
use App\Livewire\HomeController;
use App\Livewire\KardexsController;
use App\Livewire\ProfilesController;
use App\Livewire\LogsController;
use App\Livewire\RolesController;
use App\Livewire\SizesController;
use App\Livewire\UsersController;
use App\Livewire\SettingsController;
use App\Livewire\BranchesController;
use App\Livewire\WarehousesController;
use App\Livewire\BrandsController;
use App\Livewire\CategoriesController;
use App\Livewire\UnitsController;
use App\Livewire\ProductsController;
use App\Livewire\WorkersController;
use App\Livewire\DeliveriesController;
use App\Livewire\DeliveryListsController;
use App\Livewire\RemitosController;
use App\Livewire\RemitoListsController;
use App\Livewire\InventoriesController;
use App\Livewire\BackupsController;
use App\Livewire\Reports\DeliveryReportsController;
use App\Livewire\Reports\RemitoReportsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/branch/switch', [BranchController::class, 'switchBranch'])->name('branch.switch');
    Route::get('/branch/current', [BranchController::class, 'getCurrentBranch'])->name('branch.current');
    Route::get('home', HomeController::class)->name('home');
    Route::get('users', UsersController::class)->middleware('permission:ver-usuario')->name('users');
    Route::get('roles', RolesController::class)->middleware('permission:ver-rol')->name('roles');
    Route::get('profiles', ProfilesController::class)->name('profiles');
    Route::get('logs', LogsController::class)->middleware('permission:ver-log')->name('logs');
    Route::get('settings', SettingsController::class)->middleware('permission:ver-ajustes')->name('settings');
    Route::get('branches', BranchesController::class)->middleware('permission:ver-sucursales')->name('branches');
    Route::get('warehouses', WarehousesController::class)->name('warehouses');
    Route::get('categories', CategoriesController::class)->middleware('permission:ver-categorias')->name('categories');
    Route::get('units', UnitsController::class)->middleware('permission:ver-unidades')->name('units');

    Route::get('brands', BrandsController::class)->middleware('permission:ver-marcas')->name('brands');
    Route::get('products', ProductsController::class)->middleware('permission:ver-productos')->name('products');
    Route::get('inventoryPdf/{branch_id}', [ProductsController::class, 'inventoryPdf'])->name('inventoryPdf');
    Route::get('workers', WorkersController::class)->middleware('permission:ver-trabajadores')->name('workers');

    Route::get('deliveries', DeliveriesController::class)->name('deliveries');
    Route::get('delivery_lists', DeliveryListsController::class)->name('delivery_lists');
    Route::get('delivery_reports', DeliveryReportsController::class)->name('delivery_reports');
    Route::get('/delivery_reports/deliveryReportPdf/{fromDate}/{toDate}/{branch_id}/{user_id}', [DeliveryReportsController::class, 'deliveryReportPdf'])->name('delivery_reports.deliveryReportPdf');

    Route::get('remitos', RemitosController::class)->name('remitos');
    Route::get('remito_lists', RemitoListsController::class)->name('remito_lists');
    Route::get('remito_reports', RemitoReportsController::class)->name('remito_reports');
    Route::get('/remito_reports/remitoReportPdf/{fromDate}/{toDate}/{branch_id}/{user_id}/{tipo}', [RemitoReportsController::class, 'remitoReportPdf'])->name('remito_reports.remitoReportPdf');


    Route::get('inventories', InventoriesController::class)->middleware('permission:ver-stock')->name('inventories');
    Route::get('kardexs', KardexsController::class)->middleware('permission:ver-kardex')->name('kardexs');

    /////////////TALLAS Y COLORES
    Route::get('sizes', SizesController::class)->middleware('permission:ver-tallas')->name('sizes');
    Route::get('colors', ColorsController::class)->middleware('permission:ver-colores')->name('colors');

    /////////////BACKUPS
    Route::get('backups', BackupsController::class)->middleware('permission:ver-ajustes')->name('backups');
    Route::get('backup/download/{filename}', [BackupsController::class, 'download'])->middleware('permission:ver-ajustes')->name('backup.download');
});

Route::get('/401', function () {
    return view('errors.401');
});

require __DIR__ . '/auth.php';
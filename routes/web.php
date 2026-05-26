<?php

use App\Http\Controllers\BranchController;
use App\Livewire\ColorsController;
use App\Livewire\HomeController;
use App\Livewire\KardexsController;
use App\Livewire\ProfilesController;
use App\Livewire\LogsController;
use App\Livewire\Reports\CashTransactionReportsController;
use App\Livewire\Reports\CommissionReportsController;
use App\Livewire\Reports\IncomeStatementReportsController;
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
use App\Livewire\SuppliersController;
use App\Livewire\CustomersController;
use App\Livewire\WorkersController;
use App\Livewire\SalesController;
use App\Livewire\SaleListsController;
use App\Livewire\DeliveriesController;
use App\Livewire\DeliveryListsController;
use App\Livewire\InventoriesController;
use App\Livewire\Reports\ExpirationReportsController;
use App\Livewire\Reports\SaleReportsController;
use App\Livewire\Reports\GlobalEarningReportsController;
use App\Livewire\Reports\OrderReportsController;
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
    Route::get('suppliers', SuppliersController::class)->middleware('permission:ver-proveedores')->name('suppliers');
    Route::get('customers', CustomersController::class)->middleware('permission:ver-clientes')->name('customers');
    Route::get('workers', WorkersController::class)->middleware('permission:ver-trabajadores')->name('workers');
    Route::get('sales/edit/{sale_id}', SalesController::class)->name('sales.edit');
    Route::get('sales', SalesController::class)->name('sales');
    Route::get('sales_lists', SaleListsController::class)->name('sales_lists');

    Route::get('deliveries', DeliveriesController::class)->name('deliveries');
    Route::get('delivery_lists', DeliveryListsController::class)->name('delivery_lists');


    Route::get('inventories', InventoriesController::class)->middleware('permission:ver-stock')->name('inventories');
    Route::get('kardexs', KardexsController::class)->middleware('permission:ver-kardex')->name('kardexs');
    Route::get('/sale_lists/salePdf/{id},{branch_id}', [SaleListsController::class, 'salePdf'])->name('sale_lists.salePdf');


    Route::get('expiration_reports', ExpirationReportsController::class)->middleware('permission:ver-reportevencimiento')->name('expiration_reports');
    Route::get('sale_reports', SaleReportsController::class)->middleware('permission:ver-reporteventa')->name('sale_reports');
    Route::get('global_earnings_reports', GlobalEarningReportsController::class)->middleware('permission:ver-reporteganancias')->name('global_earnings_reports');

    Route::get('/global_earnings_reports/exportToPdf/{fromDate}/{toDate}', [GlobalEarningReportsController::class, 'exportToPdf'])->name('global_earnings_reports.exportToPdf');

    Route::get('order_reports', OrderReportsController::class)->name('order_reports');
    Route::get('income_statement_reports', IncomeStatementReportsController::class)->middleware('permission:ver-reporteestado')->name('income_statement_reports');
    Route::get('/income_statement_reports/incomeStatementPdf/{fromDate}/{toDate}/{branch_id}', [IncomeStatementReportsController::class, 'incomeStatementPdf'])->name('income_statement_reports.incomeStatementPdf');

    Route::get('/sale_reports/saleReportPdf/{fromDate}/{toDate}/{branch_id}/{user_id}', [SaleReportsController::class, 'saleReportPdf'])->name('sale_reports.saleReportPdf');
    Route::get('/order_reports/orderReportPdf/{fromDate}/{toDate}/{branch_id}/{user_id}', [OrderReportsController::class, 'orderReportPdf'])->name('order_reports.orderReportPdf');

    Route::get('commission_reports', CommissionReportsController::class)->middleware('permission:ver-reportecomision')->name('commission_reports');
    Route::get('/commission_reports/pdf/{fromDate}/{toDate}/{branch_id}/{user_id}', [CommissionReportsController::class, 'commissionReportPdf'])->name('commission_reports.pdf');

    Route::get('cash_transaction_reports', CashTransactionReportsController::class)->name('cash_transaction_reports');
    Route::get('/cash_transaction_reports/cashTransactionReportPdf/{fromDate}/{toDate}/{branch_id}/{user_id}/{type}', [CashTransactionReportsController::class, 'cashTransactionReportPdf'])->name('cash_transaction_reports.cashTransactionReportPdf');

    /////////////TALLAS Y COLORES
    Route::get('sizes', SizesController::class)->middleware('permission:ver-tallas')->name('sizes');
    Route::get('colors', ColorsController::class)->middleware('permission:ver-colores')->name('colors');
});

Route::get('/401', function () {
    return view('errors.401');
});

require __DIR__ . '/auth.php';
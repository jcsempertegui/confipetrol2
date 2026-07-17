<?php

use App\Livewire\BackupsController;
use App\Livewire\CategoriesController;
use App\Livewire\HomeController;
use App\Livewire\LogsController;
use App\Livewire\ProductsController;
use App\Livewire\ProfileController;
use App\Livewire\RolesController;
use App\Livewire\UsersController;
use App\Livewire\WorkersController;
use App\Livewire\DispatchNotesController;
use App\Livewire\DeliveriesController;
use App\Livewire\InventoryController;
use App\Livewire\ReportsController;
use App\Http\Controllers\DocumentPrintController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::get('home', HomeController::class)->name('home');
    Route::get('users', UsersController::class)->middleware('permission:ver-usuario')->name('users');
    Route::get('roles', RolesController::class)->middleware('permission:ver-rol')->name('roles');
    Route::get('logs', LogsController::class)->middleware('permission:ver-log')->name('logs');
    Route::get('backups', BackupsController::class)->middleware(['permission:ver-backup', 'password.confirm'])->name('backups');
    Route::get('categories', CategoriesController::class)->middleware('permission:ver-categoria')->name('categories');
    Route::get('products', ProductsController::class)->middleware('permission:ver-producto')->name('products');
    Route::get('workers', WorkersController::class)->middleware('permission:ver-trabajador')->name('workers');
    Route::get('dispatch-notes', DispatchNotesController::class)->middleware('permission:ver-remito')->name('dispatch-notes');
    Route::get('deliveries', DeliveriesController::class)->middleware('permission:ver-entrega')->name('deliveries');
    Route::get('inventory', InventoryController::class)->middleware('permission:ver-inventario')->name('inventory');
    Route::get('reports', ReportsController::class)->middleware('permission:ver-reporte')->name('reports');
    Route::get('dispatch-notes/{dispatchNote}/print', [DocumentPrintController::class, 'dispatchNote'])->middleware('permission:ver-remito')->name('dispatch-notes.print');
    Route::get('deliveries/{delivery}/print', [DocumentPrintController::class, 'delivery'])->middleware('permission:ver-entrega')->name('deliveries.print');
    Route::get('profile', ProfileController::class)->name('profile');
    Route::get('backup/download/{filename}', [BackupsController::class, 'download'])
        ->middleware(['permission:ver-backup', 'password.confirm'])
        ->name('backup.download');
});

Route::view('/401', 'errors.401');

require __DIR__.'/auth.php';

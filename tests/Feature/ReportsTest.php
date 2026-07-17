<?php

use App\Livewire\ReportsController;
use App\Models\Category;
use App\Models\DispatchNote;
use App\Models\Product;
use App\Models\User;
use App\Models\Worker;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'REPORTES TEST', 'guard_name' => 'web']);
    $role->syncPermissions(['ver-reporte', 'exportar-reporte']);
    $this->user = User::factory()->create();
    $this->user->assignRole($role);
    $this->actingAs($this->user);

    $category = Category::create(['name' => 'EPP', 'code' => 'EPP']);
    $product = Product::create(['category_id' => $category->id, 'code' => 'EPP-0001', 'name' => 'Guantes', 'tracking_type' => 'bulk']);
    $this->variant = $product->variants()->create(['sku' => 'EPP-0001-8', 'name' => 'Talla 8', 'minimum_stock' => 10]);
    $entry = DispatchNote::create(['type' => 'entry', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $this->user->id]);
    $entry->items()->create(['product_variant_id' => $this->variant->id, 'quantity' => 5]);
    app(InventoryService::class)->confirm($entry, $this->user->id);
});

it('shows inventory reports and low stock alerts', function () {
    Livewire::test(ReportsController::class)
        ->assertSee('Guantes')
        ->assertSee('5.000')
        ->assertSee('Stock bajo')
        ->set('stockStatus', 'low')
        ->assertSee('EPP-0001-8');
});

it('filters movements by type source and quick periods', function () {
    Livewire::test(ReportsController::class)
        ->set('reportType', 'movements')
        ->set('movementType', 'dispatch_entry')
        ->set('documentSource', 'dispatch')
        ->call('setPeriod', 'today')
        ->assertSee('Ingreso')
        ->assertSee('EPP-0001-8');
});

it('filters delivery-related reports by a specific worker', function () {
    $worker = Worker::create(['document' => 'CI-7788', 'name' => 'Ana', 'lastname' => 'Pérez', 'status' => true]);
    Livewire::test(ReportsController::class)
        ->set('reportType', 'deliveries')
        ->set('workerSearch', '7788')
        ->assertSee('Ana')
        ->call('selectWorker', $worker->id)
        ->assertSet('workerFilter', $worker->id)
        ->assertSee('CI-7788')
        ->call('clearWorker')
        ->assertSet('workerFilter', '');
});

it('exports a csv and records the action in the audit log', function () {
    Livewire::test(ReportsController::class)->call('exportCsv')->assertFileDownloaded();
    $this->assertDatabaseHas('logs', ['modulo' => 'REPORTES', 'accion' => 'EXPORTAR', 'user_id' => $this->user->id]);
});

it('denies report access without its permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('reports'))->assertForbidden();
});

it('rejects an inverted date range before exporting', function () {
    Livewire::test(ReportsController::class)
        ->set('reportType', 'movements')
        ->set('fromDate', '2026-07-16')
        ->set('toDate', '2026-07-15')
        ->assertHasErrors('toDate')
        ->call('exportCsv')
        ->assertHasErrors('toDate');
});

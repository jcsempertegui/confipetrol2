<?php

use App\Models\Category;
use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Models\Worker;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'TABLERO DE ALMACÉN', 'guard_name' => 'web']);
    $role->syncPermissions(['ver-inventario', 'ver-kardex', 'ver-remito', 'ver-entrega', 'ver-reporte']);
    $this->user = User::factory()->create(['name' => 'Responsable de almacén']);
    $this->user->assignRole($role);
});

it('shows actionable warehouse metrics documents movements and expiry alerts', function () {
    $category = Category::create(['name' => 'Medicamentos', 'code' => 'MED']);
    $expiration = ProductAttribute::create([
        'name' => 'Fecha de vencimiento',
        'code' => 'med-vencimiento',
        'type' => 'date',
        'scope' => 'product',
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'code' => 'MED-001-RGD',
        'name' => 'Medicamento de prueba',
        'unit' => 'caja',
        'tracking_type' => 'bulk',
    ]);
    $product->attributeValues()->create([
        'product_attribute_id' => $expiration->id,
        'value' => now()->addDays(10)->toDateString(),
    ]);
    $variant = $product->variants()->create([
        'sku' => 'MED-001-01-RGD',
        'minimum_stock' => 5,
    ]);

    $outProduct = Product::create([
        'category_id' => $category->id,
        'code' => 'MED-002-RGD',
        'name' => 'Producto agotado de prueba',
        'unit' => 'unidad',
        'tracking_type' => 'bulk',
    ]);
    $outProduct->variants()->create(['sku' => 'MED-002-01-RGD', 'minimum_stock' => 2]);

    $entry = DispatchNote::create([
        'type' => 'entry',
        'document_date' => today(),
        'counterparty' => 'Proveedor del tablero',
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $entry->items()->create(['product_variant_id' => $variant->id, 'quantity' => 3]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $worker = Worker::create([
        'code' => 'MTTO-ELEC-01-RGD',
        'document' => 'TABLERO-01',
        'name' => 'Juan',
        'lastname' => 'Pérez',
        'position' => 'Eléctrico',
        'area' => 'Mantenimiento',
    ]);
    $delivery = Delivery::create([
        'worker_id' => $worker->id,
        'delivery_date' => today(),
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $delivery->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);
    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);

    $this->actingAs($this->user)->get('/home')->assertOk()
        ->assertSee('Estado del inventario')
        ->assertSee('Productos agotados')
        ->assertSee('Stock bajo')
        ->assertSee('Producto agotado de prueba')
        ->assertSee('Medicamento de prueba')
        ->assertSee('Control de vencimientos')
        ->assertSee('10 días')
        ->assertSee('Ingresos de hoy')
        ->assertSee('Entregas de hoy')
        ->assertSee('Movimientos de hoy')
        ->assertSee($entry->fresh()->number)
        ->assertSee($delivery->fresh()->number)
        ->assertSee('Juan Pérez');
});

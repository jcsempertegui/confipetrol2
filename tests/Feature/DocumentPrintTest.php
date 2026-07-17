<?php

use App\Models\Category;
use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Models\Product;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'IMPRESION TEST', 'guard_name' => 'web']);
    $role->syncPermissions(['ver-remito', 'ver-entrega']);
    $this->user = User::factory()->create();
    $this->user->assignRole($role);
    $this->actingAs($this->user);

    $category = Category::create(['name' => 'EPP', 'code' => 'EPP']);
    $product = Product::create(['category_id' => $category->id, 'code' => 'EPP-PRINT', 'name' => 'Guante', 'unit' => 'par']);
    $this->variant = $product->variants()->create(['sku' => 'EPP-PRINT-8', 'name' => 'Talla 8']);
});

it('prints and audits a dispatch note', function () {
    $note = DispatchNote::create(['number' => 'ING001160726', 'type' => 'entry', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'confirmed', 'created_by' => $this->user->id]);
    $note->items()->create(['product_variant_id' => $this->variant->id, 'quantity' => 2]);

    $this->get(route('dispatch-notes.print', $note))->assertOk()->assertSee('ING001160726')->assertSee('Guante');
    $this->assertDatabaseHas('logs', ['modulo' => 'REMITOS', 'accion' => 'IMPRIMIR', 'modelo_id' => $note->id]);
});

it('prints and audits a worker delivery', function () {
    $worker = Worker::create(['document' => 'CI-9090', 'name' => 'Ana', 'lastname' => 'Pérez', 'status' => true]);
    $delivery = Delivery::create(['number' => 'ENT001160726', 'worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'confirmed', 'created_by' => $this->user->id]);
    $delivery->items()->create(['product_variant_id' => $this->variant->id, 'quantity' => 1]);

    $this->get(route('deliveries.print', $delivery))->assertOk()->assertSee('ENT001160726')->assertSee('Ana Pérez');
    $this->assertDatabaseHas('logs', ['modulo' => 'ENTREGAS', 'accion' => 'IMPRIMIR', 'modelo_id' => $delivery->id]);
});

it('denies document printing without its permission', function () {
    $note = DispatchNote::create(['type' => 'entry', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $this->user->id]);
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dispatch-notes.print', $note))->assertForbidden();
});

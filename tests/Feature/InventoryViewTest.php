<?php

use App\Livewire\InventoryController;
use App\Models\Category;
use App\Models\DispatchNote;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'INVENTARIO TEST', 'guard_name' => 'web']);
    $role->syncPermissions(['ver-inventario', 'ver-kardex']);
    $this->user = User::factory()->create();
    $this->user->assignRole($role);
    $this->actingAs($this->user);
    $category = Category::create(['name' => 'EPP', 'code' => 'EPP']);
    $product = Product::create(['category_id' => $category->id, 'code' => 'EPP-0001', 'name' => 'Guantes', 'tracking_type' => 'bulk']);
    $this->variant = $product->variants()->create(['sku' => 'EPP-0001-8', 'name' => 'Talla 8']);
});

it('shows stock and a kardex with running balances', function () {
    $entry = DispatchNote::create(['type' => 'entry', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $this->user->id]);
    $entry->items()->create(['product_variant_id' => $this->variant->id, 'quantity' => 10]);
    app(InventoryService::class)->confirm($entry, $this->user->id);
    $exit = DispatchNote::create(['type' => 'exit', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $this->user->id]);
    $exit->items()->create(['product_variant_id' => $this->variant->id, 'quantity' => 3]);
    app(InventoryService::class)->confirm($exit, $this->user->id);

    Livewire::test(InventoryController::class)->assertSee('Guantes')->assertSee('7')->assertDontSee('7.000')
        ->call('viewKardex', $this->variant->id)->assertSeeHtml('aria-modal="true"')->assertSee('REM-01-'.now()->format('dmY').'-RGD')->assertSee('REM-02-'.now()->format('dmY').'-RGD')
        ->assertSee('10')->assertSee('3')->assertSee('7')->assertDontSee('.000');
});

it('protects kardex independently from stock visibility', function () {
    $role = Role::create(['name' => 'SOLO STOCK', 'guard_name' => 'web']);
    $role->givePermissionTo('ver-inventario');
    $user = User::factory()->create();
    $user->assignRole($role);
    Livewire::actingAs($user)->test(InventoryController::class)->call('viewKardex', $this->variant->id)->assertForbidden();
});

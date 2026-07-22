<?php

use App\Livewire\DispatchNotesController;
use App\Models\Category;
use App\Models\DispatchNote;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('does not keep lot tables or columns in the inventory schema', function () {
    expect(Schema::hasTable('inventory_lots'))->toBeFalse()
        ->and(Schema::hasTable('inventory_lot_allocations'))->toBeFalse()
        ->and(Schema::hasColumn('inventory_movements', 'inventory_lot_id'))->toBeFalse()
        ->and(Schema::hasColumn('dispatch_note_items', 'inventory_lot_id'))->toBeFalse()
        ->and(Schema::hasColumn('dispatch_note_items', 'lot_number'))->toBeFalse()
        ->and(Schema::hasColumn('dispatch_note_items', 'expiration_date'))->toBeFalse();
});

it('accepts a medication entry without requesting lot information', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Medicamentos', 'code' => 'MED-SIN-LOTES']);
    $expiration = ProductAttribute::create([
        'name' => 'Vencimiento',
        'code' => 'med-vencimiento-catalogo',
        'type' => 'date',
        'scope' => 'product',
    ]);
    $category->attributes()->attach($expiration, ['required' => true]);
    $product = Product::create([
        'category_id' => $category->id,
        'code' => 'MED-001-01-RGD',
        'name' => 'Paracetamol 500 mg',
        'unit' => 'caja',
    ]);
    $product->attributeValues()->create([
        'product_attribute_id' => $expiration->id,
        'value' => '2027-12-31',
    ]);
    $variant = $product->variants()->create(['sku' => 'MED-001-01-RGD']);
    $note = DispatchNote::create([
        'type' => 'entry',
        'document_date' => now(),
        'counterparty' => 'Proveedor médico',
        'status' => 'draft',
        'created_by' => $user->id,
    ]);
    $note->items()->create(['product_variant_id' => $variant->id, 'quantity' => 5]);

    app(InventoryService::class)->confirm($note, $user->id);

    expect((float) InventoryMovement::where('product_variant_id', $variant->id)->sum('quantity'))->toBe(5.0)
        ->and($note->fresh()->status)->toBe('confirmed');
});

it('does not display lot controls in the dispatch note form', function () {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $role = Role::create(['name' => 'REMITOS SIN LOTES', 'guard_name' => 'web']);
    $role->givePermissionTo('ver-remito');
    $user->assignRole($role);

    Livewire::actingAs($user)->test(DispatchNotesController::class)
        ->assertDontSee('Número de lote')
        ->assertDontSee('SIN-LOTE')
        ->assertDontSee('FEFO');
});

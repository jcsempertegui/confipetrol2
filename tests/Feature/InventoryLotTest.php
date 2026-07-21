<?php

use App\Livewire\DispatchNotesController;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Models\Worker;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-07-20 10:00:00'));
    $this->user = User::factory()->create();
    $this->category = Category::create(['name' => 'Medicamentos', 'code' => 'MED']);
    $this->expiration = ProductAttribute::create([
        'name' => 'Vencimiento',
        'code' => 'med-vencimiento',
        'type' => 'date',
        'scope' => 'variant',
        'status' => true,
    ]);
    $this->category->attributes()->attach($this->expiration->id, ['required' => true, 'position' => 1]);
    $this->product = Product::create([
        'category_id' => $this->category->id,
        'code' => 'MED-001-RGD',
        'name' => 'Paracetamol 500 mg',
        'unit' => 'tableta',
        'tracking_type' => 'bulk',
    ]);
    $this->variant = $this->product->variants()->create(['sku' => 'MED-001-01-RGD', 'name' => 'Caja']);
    $this->variant->attributeValues()->create([
        'product_attribute_id' => $this->expiration->id,
        'value' => '2026-12-31',
    ]);
});

function lotEntry($test, string $lot, string $expiration, float $quantity): DispatchNote
{
    $note = DispatchNote::create([
        'type' => 'entry',
        'document_date' => '2026-07-20',
        'counterparty' => 'Proveedor médico',
        'status' => 'draft',
        'created_by' => $test->user->id,
    ]);
    $note->items()->create([
        'product_variant_id' => $test->variant->id,
        'quantity' => $quantity,
        'lot_number' => $lot,
        'expiration_date' => $expiration,
    ]);
    app(InventoryService::class)->confirm($note, $test->user->id);

    return $note;
}

it('preserves the catalog expiration attribute while receiving stock in independent lots', function () {
    lotEntry($this, 'LOT-A', '2026-10-15', 5);
    lotEntry($this, 'LOT-B', '2027-02-10', 8);

    expect(InventoryLot::where('product_variant_id', $this->variant->id)->count())->toBe(2)
        ->and((float) InventoryMovement::where('product_variant_id', $this->variant->id)->sum('quantity'))->toBe(13.0)
        ->and($this->variant->attributeValues()->where('product_attribute_id', $this->expiration->id)->value('value'))->toBe('2026-12-31')
        ->and($this->expiration->fresh())->not->toBeNull();
});

it('delivers stock using FEFO and records every lot allocation', function () {
    lotEntry($this, 'LOT-LATE', '2027-02-10', 8);
    lotEntry($this, 'LOT-FIRST', '2026-08-05', 4);

    $worker = Worker::create(['code' => 'ALMA-TEST-01-RGD', 'document' => 'LOT-001', 'name' => 'Ana', 'lastname' => 'Rojas']);
    $delivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => '2026-07-20', 'status' => 'draft', 'created_by' => $this->user->id]);
    $item = $delivery->items()->create(['product_variant_id' => $this->variant->id, 'quantity' => 6]);

    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);

    $allocations = $item->fresh()->lotAllocations()->with('lot')->get();
    expect($allocations)->toHaveCount(2)
        ->and((float) $allocations->firstWhere('lot.lot_number', 'LOT-FIRST')->quantity)->toBe(4.0)
        ->and((float) $allocations->firstWhere('lot.lot_number', 'LOT-LATE')->quantity)->toBe(2.0)
        ->and((float) InventoryMovement::whereHas('inventoryLot', fn ($query) => $query->where('lot_number', 'LOT-FIRST'))->sum('quantity'))->toBe(0.0)
        ->and((float) InventoryMovement::whereHas('inventoryLot', fn ($query) => $query->where('lot_number', 'LOT-LATE'))->sum('quantity'))->toBe(6.0);
});

it('blocks expired lots from worker deliveries but allows their controlled warehouse exit', function () {
    $entry = DispatchNote::create([
        'type' => 'entry', 'document_date' => '2026-07-01', 'counterparty' => 'Proveedor médico',
        'status' => 'draft', 'created_by' => $this->user->id,
    ]);
    $entry->items()->create([
        'product_variant_id' => $this->variant->id, 'quantity' => 3,
        'lot_number' => 'LOT-EXPIRED', 'expiration_date' => '2026-07-10',
    ]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $worker = Worker::create(['code' => 'ALMA-TEST-02-RGD', 'document' => 'LOT-002', 'name' => 'Luis', 'lastname' => 'Pérez']);
    $delivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => '2026-07-20', 'status' => 'draft', 'created_by' => $this->user->id]);
    $delivery->items()->create(['product_variant_id' => $this->variant->id, 'quantity' => 1]);
    expect(fn () => app(InventoryService::class)->confirmDelivery($delivery, $this->user->id))->toThrow(ValidationException::class);

    $exit = DispatchNote::create([
        'type' => 'exit', 'document_date' => '2026-07-20', 'counterparty' => 'Gestor autorizado',
        'reason' => 'Baja por vencimiento', 'status' => 'draft', 'created_by' => $this->user->id,
    ]);
    $exitItem = $exit->items()->create(['product_variant_id' => $this->variant->id, 'quantity' => 3]);
    app(InventoryService::class)->confirm($exit, $this->user->id);

    expect($exitItem->fresh()->lotAllocations()->whereHas('lot', fn ($query) => $query->where('lot_number', 'LOT-EXPIRED'))->exists())->toBeTrue()
        ->and((float) InventoryMovement::where('product_variant_id', $this->variant->id)->sum('quantity'))->toBe(0.0);
});

it('rejects reusing a lot number with a different expiration date', function () {
    lotEntry($this, 'LOT-UNIQUE', '2026-11-10', 2);

    expect(fn () => lotEntry($this, 'lot-unique', '2026-12-10', 1))->toThrow(ValidationException::class)
        ->and(InventoryLot::where('product_variant_id', $this->variant->id)->where('lot_number', 'LOT-UNIQUE')->count())->toBe(1);
});

it('keeps the existing expiration as a suggestion and requires lot data in the remito form', function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'RECEPCIÓN MEDICAMENTOS', 'guard_name' => 'web']);
    $role->givePermissionTo('crear-remito');
    $this->user->assignRole($role);

    $component = Livewire::actingAs($this->user)->test(DispatchNotesController::class)
        ->call('addProduct', $this->variant->id)
        ->assertSet('items.0.expiration_date', '2026-12-31')
        ->set('counterparty', 'Proveedor médico')
        ->call('save')
        ->assertHasErrors('items.0.lot_number')
        ->set('items.0.lot_number', 'lot-form-01')
        ->set('items.0.expiration_date', '2027-01-15')
        ->call('save')
        ->assertHasNoErrors();

    $item = DispatchNote::latest('id')->firstOrFail()->items()->firstOrFail();
    expect($item->lot_number)->toBe('LOT-FORM-01')
        ->and($item->expiration_date->format('Y-m-d'))->toBe('2027-01-15');
});

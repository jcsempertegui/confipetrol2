<?php

use App\Livewire\DeliveriesController;
use App\Livewire\DispatchNotesController;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Models\InventoryMovement;
use App\Models\Log;
use App\Models\Product;
use App\Models\User;
use App\Models\Worker;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->user = User::factory()->create();
    $category = Category::create(['name' => 'EPP', 'code' => 'EPP']);
    $this->bulkProduct = Product::create(['category_id' => $category->id, 'code' => 'EPP-0001', 'name' => 'Guante', 'tracking_type' => 'bulk']);
    $this->bulkVariant = $this->bulkProduct->variants()->create(['sku' => 'EPP-0001-7', 'name' => 'Talla 7']);
    $this->serialProduct = Product::create(['category_id' => $category->id, 'code' => 'EPP-0002', 'name' => 'Laptop', 'tracking_type' => 'serialized']);
    $this->serialVariant = $this->serialProduct->variants()->create(['sku' => 'EPP-0002-UNICA', 'name' => 'Única']);
    $this->serial = $this->serialVariant->serializedItems()->create(['serial_number' => 'SER-001', 'status' => 'available']);
});

function inventoryNote(string $type, User $user): DispatchNote
{
    return DispatchNote::create(['type' => $type, 'document_date' => now(), 'counterparty' => 'Proveedor de prueba', 'status' => 'draft', 'created_by' => $user->id]);
}

it('confirms entries and exits with an immutable inventory ledger', function () {
    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 10]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $exit = inventoryNote('exit', $this->user);
    $exit->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 3]);
    app(InventoryService::class)->confirm($exit, $this->user->id);

    expect($entry->fresh()->number)->toBe('ING001'.now()->format('dmy'))->and($exit->fresh()->number)->toBe('SAL001'.now()->format('dmy'))
        ->and((float) InventoryMovement::where('product_variant_id', $this->bulkVariant->id)->sum('quantity'))->toBe(7.0)
        ->and(InventoryMovement::count())->toBe(2);
});

it('prevents confirming the same document twice', function () {
    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 4]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    expect(fn () => app(InventoryService::class)->confirm($entry, $this->user->id))->toThrow(ValidationException::class)
        ->and(InventoryMovement::where('dispatch_note_id', $entry->id)->count())->toBe(1);
});

it('keeps the original dispatch note active until its correction is confirmed', function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'CORRECTOR REMITOS', 'guard_name' => 'web']);
    $role->givePermissionTo(['editar-remito', 'confirmar-remito']);
    $this->user->assignRole($role);
    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 4]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    Livewire::actingAs($this->user)->test(DispatchNotesController::class)->call('correct', $entry->id)->assertHasNoErrors();

    $correction = DispatchNote::where('corrected_from_id', $entry->id)->firstOrFail();
    expect($entry->fresh()->status)->toBe('confirmed')
        ->and($correction->status)->toBe('draft')
        ->and((float) InventoryMovement::where('product_variant_id', $this->bulkVariant->id)->sum('quantity'))->toBe(4.0);

    Livewire::actingAs($this->user)->test(DispatchNotesController::class)->call('confirm', $correction->id)->assertHasNoErrors();

    expect($entry->fresh()->status)->toBe('annulled')
        ->and($correction->fresh()->status)->toBe('confirmed')
        ->and($correction->fresh()->corrected_from_id)->toBe($entry->id)
        ->and((float) InventoryMovement::where('product_variant_id', $this->bulkVariant->id)->sum('quantity'))->toBe(4.0)
        ->and(InventoryMovement::where('dispatch_note_id', $correction->id)->count())->toBe(0)
        ->and(Log::where('modulo', 'REMITOS')->where('accion', 'CONFIRMAR_CORRECCION')->where('modelo_id', $correction->id)->exists())->toBeTrue();
});

it('applies only the safe bulk delta when correcting a partially consumed entry', function () {
    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 10]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $worker = Worker::create(['code' => 'TRB-DELTA-1', 'document' => 'DELTA-1', 'name' => 'Ana', 'lastname' => 'Pérez']);
    $delivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'draft', 'created_by' => $this->user->id]);
    $delivery->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 4]);
    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);

    $correction = DispatchNote::create([
        'corrected_from_id' => $entry->id,
        'type' => 'entry',
        'document_date' => now(),
        'counterparty' => 'Proveedor corregido',
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $correction->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 8]);

    app(InventoryService::class)->confirm($correction, $this->user->id);

    expect($entry->fresh()->status)->toBe('annulled')
        ->and($correction->fresh()->status)->toBe('confirmed')
        ->and((float) InventoryMovement::where('product_variant_id', $this->bulkVariant->id)->sum('quantity'))->toBe(4.0)
        ->and((float) InventoryMovement::where('dispatch_note_id', $correction->id)->sum('quantity'))->toBe(-2.0)
        ->and(InventoryMovement::where('dispatch_note_id', $entry->id)->count())->toBe(1);
});

it('rejects a correction atomically when its delta would leave negative stock', function () {
    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 10]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $worker = Worker::create(['code' => 'TRB-DELTA-2', 'document' => 'DELTA-2', 'name' => 'Luis', 'lastname' => 'Rojas']);
    $delivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'draft', 'created_by' => $this->user->id]);
    $delivery->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 4]);
    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);

    $correction = DispatchNote::create([
        'corrected_from_id' => $entry->id,
        'type' => 'entry',
        'document_date' => now(),
        'counterparty' => 'Proveedor corregido',
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $correction->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 3]);

    expect(fn () => app(InventoryService::class)->confirm($correction, $this->user->id))
        ->toThrow(ValidationException::class);

    expect($entry->fresh()->status)->toBe('confirmed')
        ->and($correction->fresh()->status)->toBe('draft')
        ->and((float) InventoryMovement::where('product_variant_id', $this->bulkVariant->id)->sum('quantity'))->toBe(6.0)
        ->and(InventoryMovement::where('dispatch_note_id', $correction->id)->count())->toBe(0);
});

it('preserves manual document codes and skips them in automatic sequences', function () {
    $manual = inventoryNote('entry', $this->user);
    $manual->update(['number' => 'ING001'.now()->format('dmy')]);
    $manual->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 2]);
    app(InventoryService::class)->confirm($manual, $this->user->id);
    $automatic = inventoryNote('entry', $this->user);
    $automatic->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 2]);
    app(InventoryService::class)->confirm($automatic, $this->user->id);

    expect($manual->fresh()->number)->toBe('ING001'.now()->format('dmy'))->and($automatic->fresh()->number)->toBe('ING002'.now()->format('dmy'));
});

it('restarts the automatic sequence for each document date', function () {
    $yesterday = DispatchNote::create(['type' => 'entry', 'document_date' => now()->subDay(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $this->user->id]);
    $yesterday->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 1]);
    app(InventoryService::class)->confirm($yesterday, $this->user->id);
    $today = inventoryNote('entry', $this->user);
    $today->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 1]);
    app(InventoryService::class)->confirm($today, $this->user->id);

    expect($yesterday->fresh()->number)->toBe('ING001'.now()->subDay()->format('dmy'))
        ->and($today->fresh()->number)->toBe('ING001'.now()->format('dmy'));
});

it('rejects an exit when bulk stock is insufficient', function () {
    $exit = inventoryNote('exit', $this->user);
    $exit->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 1]);

    expect(fn () => app(InventoryService::class)->confirm($exit, $this->user->id))->toThrow(ValidationException::class);
    expect($exit->fresh()->status)->toBe('draft')->and(InventoryMovement::count())->toBe(0);
});

it('tracks each serialized unit and prevents duplicate entry', function () {
    $entry = inventoryNote('entry', $this->user);
    $item = $entry->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $item->serializedItems()->attach($this->serial);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $duplicate = inventoryNote('entry', $this->user);
    $duplicateItem = $duplicate->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $duplicateItem->serializedItems()->attach($this->serial);

    expect(fn () => app(InventoryService::class)->confirm($duplicate, $this->user->id))->toThrow(ValidationException::class);
    expect((float) InventoryMovement::where('serialized_item_id', $this->serial->id)->sum('quantity'))->toBe(1.0);
});

it('annuls by adding inverse movements without deleting history', function () {
    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 5]);
    app(InventoryService::class)->confirm($entry, $this->user->id);
    app(InventoryService::class)->annul($entry, $this->user->id, 'Error de carga documentado');

    expect($entry->fresh()->status)->toBe('annulled')->and($entry->fresh()->annul_reason)->toBe('Error de carga documentado')
        ->and(InventoryMovement::count())->toBe(2)
        ->and((float) InventoryMovement::where('product_variant_id', $this->bulkVariant->id)->sum('quantity'))->toBe(0.0);
});

it('does not annul an entry whose stock was already consumed', function () {
    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 5]);
    app(InventoryService::class)->confirm($entry, $this->user->id);
    $exit = inventoryNote('exit', $this->user);
    $exit->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 2]);
    app(InventoryService::class)->confirm($exit, $this->user->id);

    expect(fn () => app(InventoryService::class)->annul($entry, $this->user->id, 'Intento inválido'))->toThrow(ValidationException::class);
    expect($entry->fresh()->status)->toBe('confirmed');
});

it('delivers stock to a worker and returns it through an audited reversal', function () {
    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 5]);
    app(InventoryService::class)->confirm($entry, $this->user->id);
    $worker = Worker::create(['code' => 'TRB-000001', 'document' => '123', 'name' => 'Ana', 'lastname' => 'Pérez']);
    $delivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'draft', 'created_by' => $this->user->id]);
    $delivery->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 2]);

    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);
    expect($delivery->fresh()->number)->toBe('ENT001'.now()->format('dmy'))->and((float) $this->bulkVariant->inventoryMovements()->sum('quantity'))->toBe(3.0);
    app(InventoryService::class)->annulDelivery($delivery, $this->user->id, 'Entrega registrada por error');
    expect($delivery->fresh()->status)->toBe('annulled')->and((float) $this->bulkVariant->inventoryMovements()->sum('quantity'))->toBe(5.0);
});

it('keeps the original delivery active until its correction is confirmed', function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'CORRECTOR ENTREGAS', 'guard_name' => 'web']);
    $role->givePermissionTo(['editar-entrega', 'confirmar-entrega']);
    $this->user->assignRole($role);

    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 5]);
    app(InventoryService::class)->confirm($entry, $this->user->id);
    $worker = Worker::create(['code' => 'TRB-000002', 'document' => '456', 'name' => 'Luis', 'lastname' => 'Rojas']);
    $delivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'draft', 'created_by' => $this->user->id]);
    $delivery->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 2]);
    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);

    Livewire::actingAs($this->user)->test(DeliveriesController::class)->call('correct', $delivery->id)->assertHasNoErrors();

    $correction = Delivery::where('corrected_from_id', $delivery->id)->firstOrFail();
    expect($delivery->fresh()->status)->toBe('confirmed')
        ->and($correction->status)->toBe('draft')
        ->and((float) InventoryMovement::where('product_variant_id', $this->bulkVariant->id)->sum('quantity'))->toBe(3.0);

    $correction->items()->firstOrFail()->update(['quantity' => 1]);
    Livewire::actingAs($this->user)->test(DeliveriesController::class)->call('confirm', $correction->id)->assertHasNoErrors();

    expect($delivery->fresh()->status)->toBe('annulled')
        ->and($correction->fresh()->status)->toBe('confirmed')
        ->and($correction->fresh()->corrected_from_id)->toBe($delivery->id)
        ->and((float) InventoryMovement::where('product_variant_id', $this->bulkVariant->id)->sum('quantity'))->toBe(4.0)
        ->and((float) InventoryMovement::where('delivery_id', $correction->id)->sum('quantity'))->toBe(1.0)
        ->and(Log::where('modulo', 'ENTREGAS')->where('accion', 'CONFIRMAR_CORRECCION')->where('modelo_id', $correction->id)->exists())->toBeTrue();
});

it('moves serialized assignments safely when confirming a delivery correction', function () {
    $secondSerial = $this->serialVariant->serializedItems()->create([
        'serial_number' => 'SER-002',
        'status' => 'available',
    ]);
    $entry = inventoryNote('entry', $this->user);
    $entryItem = $entry->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 2]);
    $entryItem->serializedItems()->attach([$this->serial->id, $secondSerial->id]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $worker = Worker::create(['code' => 'TRB-SERIAL-1', 'document' => 'SERIAL-1', 'name' => 'Marta', 'lastname' => 'Flores']);
    $delivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'draft', 'created_by' => $this->user->id]);
    $deliveryItem = $delivery->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $deliveryItem->serializedItems()->attach($this->serial);
    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);

    $correction = Delivery::create([
        'corrected_from_id' => $delivery->id,
        'worker_id' => $worker->id,
        'delivery_date' => now(),
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $correctionItem = $correction->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $correctionItem->serializedItems()->attach($secondSerial);

    app(InventoryService::class)->confirmDelivery($correction, $this->user->id);

    expect($delivery->fresh()->status)->toBe('annulled')
        ->and($correction->fresh()->status)->toBe('confirmed')
        ->and((float) InventoryMovement::where('serialized_item_id', $this->serial->id)->sum('quantity'))->toBe(1.0)
        ->and($this->serial->fresh()->status)->toBe('available')
        ->and((float) InventoryMovement::where('serialized_item_id', $secondSerial->id)->sum('quantity'))->toBe(0.0)
        ->and($secondSerial->fresh()->status)->toBe('assigned');

    app(InventoryService::class)->annulDelivery($correction, $this->user->id, 'Corrección anulada de forma controlada');

    expect($correction->fresh()->status)->toBe('annulled')
        ->and((float) InventoryMovement::where('product_variant_id', $this->serialVariant->id)->sum('quantity'))->toBe(2.0)
        ->and($secondSerial->fresh()->status)->toBe('available');
});

it('rejects removing an already delivered serial from an entry correction', function () {
    $secondSerial = $this->serialVariant->serializedItems()->create([
        'serial_number' => 'SER-003',
        'status' => 'available',
    ]);
    $entry = inventoryNote('entry', $this->user);
    $entryItem = $entry->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 2]);
    $entryItem->serializedItems()->attach([$this->serial->id, $secondSerial->id]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $worker = Worker::create(['code' => 'TRB-SERIAL-2', 'document' => 'SERIAL-2', 'name' => 'Carlos', 'lastname' => 'López']);
    $delivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'draft', 'created_by' => $this->user->id]);
    $deliveryItem = $delivery->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $deliveryItem->serializedItems()->attach($this->serial);
    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);

    $correction = DispatchNote::create([
        'corrected_from_id' => $entry->id,
        'type' => 'entry',
        'document_date' => now(),
        'counterparty' => 'Proveedor corregido',
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $correctionItem = $correction->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $correctionItem->serializedItems()->attach($secondSerial);

    expect(fn () => app(InventoryService::class)->confirm($correction, $this->user->id))
        ->toThrow(ValidationException::class);

    expect($entry->fresh()->status)->toBe('confirmed')
        ->and($correction->fresh()->status)->toBe('draft')
        ->and((float) InventoryMovement::where('serialized_item_id', $this->serial->id)->sum('quantity'))->toBe(0.0)
        ->and($this->serial->fresh()->status)->toBe('assigned')
        ->and(InventoryMovement::where('dispatch_note_id', $correction->id)->count())->toBe(0);
});

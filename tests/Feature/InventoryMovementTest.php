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

    expect($entry->fresh()->number)->toBe('REM-01-'.now()->format('dmY').'-RGD')->and($exit->fresh()->number)->toBe('REM-02-'.now()->format('dmY').'-RGD')
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
    $manual->update(['number' => 'REM-01-'.now()->format('dmY').'-RGD']);
    $manual->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 2]);
    app(InventoryService::class)->confirm($manual, $this->user->id);
    $automatic = inventoryNote('entry', $this->user);
    $automatic->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 2]);
    app(InventoryService::class)->confirm($automatic, $this->user->id);

    expect($manual->fresh()->number)->toBe('REM-01-'.now()->format('dmY').'-RGD')->and($automatic->fresh()->number)->toBe('REM-02-'.now()->format('dmY').'-RGD');
});

it('restarts the automatic sequence for each document date', function () {
    $yesterday = DispatchNote::create(['type' => 'entry', 'document_date' => now()->subDay(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $this->user->id]);
    $yesterday->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 1]);
    app(InventoryService::class)->confirm($yesterday, $this->user->id);
    $today = inventoryNote('entry', $this->user);
    $today->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 1]);
    app(InventoryService::class)->confirm($today, $this->user->id);

    expect($yesterday->fresh()->number)->toBe('REM-01-'.now()->subDay()->format('dmY').'-RGD')
        ->and($today->fresh()->number)->toBe('REM-01-'.now()->format('dmY').'-RGD');
});

it('normalizes manual remito and delivery codes with the Rio Grande suffix', function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'CODIFICACIÓN DOCUMENTOS', 'guard_name' => 'web']);
    $role->givePermissionTo(['crear-remito', 'crear-entrega']);
    $this->user->assignRole($role);

    Livewire::actingAs($this->user)->test(DispatchNotesController::class)
        ->set('number', 'REM-77-18072026-RDG')
        ->set('type', 'entry')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('counterparty', 'Proveedor de prueba')
        ->set('items', [['variant_id' => $this->bulkVariant->id, 'quantity' => 1, 'serial_ids' => [], 'notes' => '']])
        ->call('save')->assertHasNoErrors();

    $worker = Worker::create(['code' => 'TRB-COD-1', 'document' => 'COD-1', 'name' => 'Ana', 'lastname' => 'Rojas']);
    Livewire::actingAs($this->user)->test(DeliveriesController::class)
        ->set('number', 'ENT-08-18072026')
        ->set('worker_id', $worker->id)
        ->set('delivery_date', now()->format('Y-m-d'))
        ->set('items', [['variant_id' => $this->bulkVariant->id, 'quantity' => 1, 'serial_ids' => [], 'notes' => '']])
        ->call('save')->assertHasNoErrors();

    expect(DispatchNote::where('number', 'REM-77-18072026-RGD')->exists())->toBeTrue()
        ->and(Delivery::where('number', 'ENT-08-18072026-RGD')->exists())->toBeTrue();
});

it('deletes remito and delivery drafts while preserving their complete audit trail', function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'ELIMINADOR DE BORRADORES', 'guard_name' => 'web']);
    $role->givePermissionTo(['eliminar-remito', 'eliminar-entrega']);
    $this->user->assignRole($role);

    $note = inventoryNote('entry', $this->user);
    $note->update(['number' => 'REM-BORRADOR-RGD']);
    $noteItem = $note->items()->create([
        'product_variant_id' => $this->bulkVariant->id,
        'quantity' => 4,
        'notes' => 'Detalle antes de eliminar',
    ]);

    $worker = Worker::create([
        'code' => 'TRB-BORRADOR',
        'document' => 'BORRADOR-1',
        'name' => 'María',
        'lastname' => 'Flores',
    ]);
    $delivery = Delivery::create([
        'number' => 'ENT-BORRADOR-RGD',
        'worker_id' => $worker->id,
        'delivery_date' => now(),
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $deliveryItem = $delivery->items()->create([
        'product_variant_id' => $this->bulkVariant->id,
        'quantity' => 2,
        'notes' => 'Entrega pendiente',
    ]);

    Livewire::actingAs($this->user)->test(DispatchNotesController::class)
        ->call('deleteDraft', $note->id)
        ->assertHasNoErrors();
    Livewire::actingAs($this->user)->test(DeliveriesController::class)
        ->call('deleteDraft', $delivery->id)
        ->assertHasNoErrors();

    expect(DispatchNote::find($note->id))->toBeNull()
        ->and($noteItem->fresh())->toBeNull()
        ->and(Delivery::find($delivery->id))->toBeNull()
        ->and($deliveryItem->fresh())->toBeNull()
        ->and(InventoryMovement::count())->toBe(0);

    $noteLog = Log::where('modulo', 'REMITOS')->where('accion', 'ELIMINAR_BORRADOR')->where('modelo_id', $note->id)->firstOrFail();
    $deliveryLog = Log::where('modulo', 'ENTREGAS')->where('accion', 'ELIMINAR_BORRADOR')->where('modelo_id', $delivery->id)->firstOrFail();

    expect($noteLog->valores_anteriores['número'])->toBe('REM-BORRADOR-RGD')
        ->and($noteLog->valores_anteriores['detalles'][0]['cantidad'])->toEqual(4.0)
        ->and($noteLog->valores_anteriores['detalles'][0]['observaciones'])->toBe('Detalle antes de eliminar')
        ->and($noteLog->valores_nuevos)->toBe(['eliminado' => true])
        ->and($deliveryLog->valores_anteriores['número'])->toBe('ENT-BORRADOR-RGD')
        ->and($deliveryLog->valores_anteriores['trabajador'])->toBe($worker->full_name)
        ->and($deliveryLog->valores_anteriores['detalles'][0]['cantidad'])->toEqual(2.0)
        ->and($deliveryLog->valores_anteriores['detalles'][0]['observaciones'])->toBe('Entrega pendiente')
        ->and($deliveryLog->valores_nuevos)->toBe(['eliminado' => true]);
});

it('never deletes confirmed remitos or deliveries even with draft deletion permission', function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'CONTROL DE BORRADORES', 'guard_name' => 'web']);
    $role->givePermissionTo(['eliminar-remito', 'eliminar-entrega']);
    $this->user->assignRole($role);

    $entry = inventoryNote('entry', $this->user);
    $entry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 5]);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    $worker = Worker::create([
        'code' => 'TRB-CONFIRMADO',
        'document' => 'CONFIRMADO-1',
        'name' => 'Luis',
        'lastname' => 'Mamani',
    ]);
    $delivery = Delivery::create([
        'worker_id' => $worker->id,
        'delivery_date' => now(),
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $delivery->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 1]);
    app(InventoryService::class)->confirmDelivery($delivery, $this->user->id);

    Livewire::actingAs($this->user)->test(DispatchNotesController::class)
        ->call('deleteDraft', $entry->id)
        ->assertHasErrors('document');
    Livewire::actingAs($this->user)->test(DeliveriesController::class)
        ->call('deleteDraft', $delivery->id)
        ->assertHasErrors('document');

    expect($entry->fresh()->status)->toBe('confirmed')
        ->and($delivery->fresh()->status)->toBe('confirmed')
        ->and(InventoryMovement::where('dispatch_note_id', $entry->id)->exists())->toBeTrue()
        ->and(InventoryMovement::where('delivery_id', $delivery->id)->exists())->toBeTrue()
        ->and(Log::where('accion', 'ELIMINAR_BORRADOR')->exists())->toBeFalse();
});

it('requires the specific permission to delete document drafts', function () {
    $note = inventoryNote('entry', $this->user);
    $worker = Worker::create([
        'code' => 'TRB-SIN-PERMISO',
        'document' => 'SIN-PERMISO-1',
        'name' => 'Eva',
        'lastname' => 'Rojas',
    ]);
    $delivery = Delivery::create([
        'worker_id' => $worker->id,
        'delivery_date' => now(),
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);

    Livewire::actingAs($this->user)->test(DispatchNotesController::class)
        ->call('deleteDraft', $note->id)
        ->assertForbidden();
    Livewire::actingAs($this->user)->test(DeliveriesController::class)
        ->call('deleteDraft', $delivery->id)
        ->assertForbidden();

    expect($note->fresh())->not->toBeNull()
        ->and($delivery->fresh())->not->toBeNull();
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

it('shows only serialized units with real stock when preparing a delivery', function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'CREADOR ENTREGAS', 'guard_name' => 'web']);
    $role->givePermissionTo('crear-entrega');
    $this->user->assignRole($role);
    $unreceived = $this->serialVariant->serializedItems()->create([
        'serial_number' => 'SER-SIN-INGRESO',
        'status' => 'available',
    ]);
    $entry = inventoryNote('entry', $this->user);
    $item = $entry->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $item->serializedItems()->attach($this->serial);
    app(InventoryService::class)->confirm($entry, $this->user->id);

    Livewire::actingAs($this->user)->test(DeliveriesController::class)
        ->call('addProduct', $this->serialVariant->id)
        ->assertSee('Número de serie disponible')
        ->assertSee('SER-001')
        ->assertDontSee($unreceived->serial_number)
        ->set('items.0.serial_ids', [$this->serial->id])
        ->assertSet('items.0.serial_ids', [$this->serial->id]);
});

it('rejects invalid or inactive serial selections when confirming a delivery', function () {
    $bulkEntry = inventoryNote('entry', $this->user);
    $bulkEntry->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 2]);
    app(InventoryService::class)->confirm($bulkEntry, $this->user->id);

    $worker = Worker::create(['code' => 'TRB-VALIDA-SER', 'document' => 'VALIDA-SER', 'name' => 'Julia', 'lastname' => 'Rojas']);
    $invalidBulkDelivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'draft', 'created_by' => $this->user->id]);
    $bulkItem = $invalidBulkDelivery->items()->create(['product_variant_id' => $this->bulkVariant->id, 'quantity' => 1]);
    $bulkItem->serializedItems()->attach($this->serial);

    expect(fn () => app(InventoryService::class)->confirmDelivery($invalidBulkDelivery, $this->user->id))
        ->toThrow(ValidationException::class);

    $serialEntry = inventoryNote('entry', $this->user);
    $serialItem = $serialEntry->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $serialItem->serializedItems()->attach($this->serial);
    app(InventoryService::class)->confirm($serialEntry, $this->user->id);
    $this->serial->update(['status' => 'inactive']);

    $inactiveSerialDelivery = Delivery::create(['worker_id' => $worker->id, 'delivery_date' => now(), 'status' => 'draft', 'created_by' => $this->user->id]);
    $inactiveItem = $inactiveSerialDelivery->items()->create(['product_variant_id' => $this->serialVariant->id, 'quantity' => 1]);
    $inactiveItem->serializedItems()->attach($this->serial);

    expect(fn () => app(InventoryService::class)->confirmDelivery($inactiveSerialDelivery, $this->user->id))
        ->toThrow(ValidationException::class)
        ->and(InventoryMovement::whereNotNull('delivery_id')->count())->toBe(0);
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
    expect($delivery->fresh()->number)->toBe('ENT-01-'.now()->format('dmY').'-RGD')->and((float) $this->bulkVariant->inventoryMovements()->sum('quantity'))->toBe(3.0);
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

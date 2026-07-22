<?php

use App\Livewire\BackupsController;
use App\Livewire\ProductsController;
use App\Livewire\UsersController;
use App\Models\Category;
use App\Models\DispatchNote;
use App\Models\InventoryMovement;
use App\Models\Log;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\SerializedItem;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('adds defensive browser headers to every web response', function () {
    $this->get('/login')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

it('makes inventory movements and audit records immutable in the model and database', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'EPP', 'code' => 'SEC-EPP']);
    $product = Product::create(['category_id' => $category->id, 'code' => 'SEC-001-RGD', 'name' => 'Guante']);
    $variant = $product->variants()->create(['sku' => 'SEC-001-01-RGD']);
    $note = DispatchNote::create(['type' => 'entry', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $user->id]);
    $note->items()->create(['product_variant_id' => $variant->id, 'quantity' => 2]);
    app(InventoryService::class)->confirm($note, $user->id);

    $movement = InventoryMovement::firstOrFail();
    $audit = Log::create(['user_id' => $user->id, 'actor_login' => $user->login, 'modulo' => 'PRUEBA', 'accion' => 'CREAR']);

    expect(fn () => $movement->update(['quantity' => 99]))->toThrow(LogicException::class)
        ->and(fn () => $movement->delete())->toThrow(LogicException::class)
        ->and(fn () => DB::table('inventory_movements')->where('id', $movement->id)->update(['quantity' => 99]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('inventory_movements')->where('id', $movement->id)->delete())->toThrow(QueryException::class)
        ->and(fn () => DB::table('logs')->where('id', $audit->id)->update(['accion' => 'ALTERAR']))->toThrow(QueryException::class)
        ->and(fn () => DB::table('logs')->where('id', $audit->id)->delete())->toThrow(QueryException::class);

    expect((float) $movement->fresh()->quantity)->toBe(2.0)
        ->and($audit->fresh()->accion)->toBe('CREAR');
});

it('enforces critical inventory checks at database level', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Herramientas', 'code' => 'SEC-HER']);
    $product = Product::create(['category_id' => $category->id, 'code' => 'SEC-002-RGD', 'name' => 'Llave']);
    $variant = $product->variants()->create(['sku' => 'SEC-002-01-RGD']);
    $note = DispatchNote::create(['type' => 'entry', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $user->id]);

    expect(fn () => DB::table('inventory_movements')->insert([
        'product_variant_id' => $variant->id,
        'dispatch_note_id' => $note->id,
        'movement_type' => 'dispatch_entry',
        'quantity' => 0,
        'occurred_at' => now(),
        'created_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rejects inactive products even when a crafted draft references them', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'EPP', 'code' => 'SEC-INACT']);
    $product = Product::create(['category_id' => $category->id, 'code' => 'SEC-003-RGD', 'name' => 'Casco']);
    $variant = $product->variants()->create(['sku' => 'SEC-003-01-RGD', 'status' => false]);
    $note = DispatchNote::create(['type' => 'entry', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $user->id]);
    $note->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

    expect(fn () => app(InventoryService::class)->confirm($note, $user->id))
        ->toThrow(ValidationException::class)
        ->and(InventoryMovement::count())->toBe(0);
});

it('does not allow a serialized unit with history to be removed or moved while editing', function () {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $role = Role::create(['name' => 'GESTOR SEGURO', 'guard_name' => 'web']);
    $role->givePermissionTo('editar-producto');
    $user->assignRole($role);

    $category = Category::create(['name' => 'Activos', 'code' => 'SEC-ACT']);
    $identifier = ProductAttribute::create(['name' => 'Número de serie', 'code' => 'sec-serie', 'type' => 'text', 'scope' => 'unit']);
    $category->attributes()->attach($identifier, ['required' => true]);
    $product = Product::create(['category_id' => $category->id, 'code' => 'SEC-004-RGD', 'name' => 'Detector', 'tracking_type' => 'serialized']);
    $variant = $product->variants()->create(['sku' => 'SEC-004-01-RGD']);
    $serial = $variant->serializedItems()->create(['serial_number' => 'SEC-SER-001', 'status' => 'available']);
    $note = DispatchNote::create(['type' => 'entry', 'document_date' => now(), 'counterparty' => 'Proveedor', 'status' => 'draft', 'created_by' => $user->id]);
    $item = $note->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);
    $item->serializedItems()->attach($serial);
    app(InventoryService::class)->confirm($note, $user->id);

    Livewire::actingAs($user)->test(ProductsController::class)
        ->call('edit', $product->id)
        ->set('variants.0.serials', 'SEC-SER-002')
        ->call('save')
        ->assertHasErrors('variants.0.serials');

    expect($serial->fresh()->status)->toBe('available')
        ->and($serial->fresh()->product_variant_id)->toBe($variant->id)
        ->and(SerializedItem::where('serial_number', 'SEC-SER-002')->exists())->toBeFalse();
});

it('rejects inactive roles and weak passwords when creating users', function () {
    $this->seed(PermissionSeeder::class);
    $manager = User::factory()->create();
    $managerRole = Role::create(['name' => 'GESTOR USUARIOS SEGURO', 'guard_name' => 'web']);
    $managerRole->givePermissionTo('crear-usuario');
    $manager->assignRole($managerRole);
    $inactiveRole = Role::create(['name' => 'ROL INACTIVO SEGURO', 'guard_name' => 'web']);
    $inactiveRole->update(['status' => false]);

    Livewire::actingAs($manager)->test(UsersController::class)
        ->set('login', 'usuario.seguro')
        ->set('name', 'Usuario')
        ->set('lastname', 'Seguro')
        ->set('document', 'SEG-001')
        ->set('email', 'seguro@example.com')
        ->set('role', $inactiveRole->name)
        ->set('password', 'corta')
        ->set('password_confirmation', 'corta')
        ->call('save')
        ->assertHasErrors(['role', 'password']);

    expect(User::where('login', 'usuario.seguro')->exists())->toBeFalse();
});

it('scans the complete restore file for dangerous SQL instructions', function () {
    $path = tempnam(sys_get_temp_dir(), 'confipetrol-security-');
    file_put_contents($path, str_repeat('-- contenido de respaldo seguro'.PHP_EOL, 40000).PHP_EOL.'DROP DATABASE `confipetrol2`;');

    $validator = new ReflectionMethod(BackupsController::class, 'validateRestoreFile');
    $validator->setAccessible(true);

    try {
        expect(fn () => $validator->invoke(new BackupsController, $path))
            ->toThrow(RuntimeException::class, 'instrucciones SQL no permitidas');
    } finally {
        @unlink($path);
    }
});

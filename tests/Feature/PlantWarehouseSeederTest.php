<?php

use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Models\InventoryMovement;
use App\Models\Log;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SerializedItem;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlantWarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function plantWarehouseSeederSnapshot(): array
{
    $tables = [
        'categories',
        'product_attributes',
        'category_product_attribute',
        'products',
        'product_attribute_values',
        'product_variants',
        'variant_attribute_values',
        'serialized_items',
        'serialized_item_attribute_values',
        'workers',
        'users',
        'roles',
        'role_has_permissions',
        'model_has_roles',
        'dispatch_notes',
        'dispatch_note_items',
        'dispatch_note_serialized_items',
        'deliveries',
        'delivery_items',
        'delivery_serialized_items',
        'inventory_movements',
        'document_sequences',
        'logs',
    ];

    return collect($tables)->mapWithKeys(function (string $table): array {
        $rows = DB::table($table)->get()
            ->map(fn (object $row): array => (array) $row)
            ->sortBy(fn (array $row): string => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->values()
            ->all();

        return [$table => $rows];
    })->all();
}

it('loads a coherent plant warehouse dataset without changing it on a second run', function () {
    $this->travelTo(now()->startOfDay()->addHours(8));
    $this->seed(PermissionSeeder::class);

    $superAdmin = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);
    $superAdmin->syncPermissions(Permission::all());

    $admin = User::factory()->create([
        'login' => 'admin',
        'name' => 'Administrador original',
        'lastname' => 'Sistema',
        'document' => 'ADMIN-ORIGINAL',
        'email' => 'admin-original@example.com',
        'phone' => '70000000',
        'password' => Hash::make('AdminOriginal!2026'),
        'status' => true,
        'max_sessions' => 2,
    ]);
    $admin->assignRole($superAdmin);

    $adminBefore = $admin->fresh()->getRawOriginal();
    $adminRolesBefore = $admin->roles()->orderBy('name')->pluck('name')->all();

    $this->seed(PlantWarehouseSeeder::class);

    expect($admin->fresh()->getRawOriginal())->toBe($adminBefore)
        ->and($admin->fresh()->roles()->orderBy('name')->pluck('name')->all())->toBe($adminRolesBefore);

    expect(Product::count())->toBe(25)
        ->and(Product::where('code', 'like', 'PSL%')->count())->toBe(25)
        ->and(ProductVariant::count())->toBe(37)
        ->and(SerializedItem::count())->toBe(6)
        ->and(SerializedItem::where('status', 'assigned')->count())->toBe(5)
        ->and(SerializedItem::where('status', 'available')->count())->toBe(1)
        ->and(Worker::count())->toBe(10)
        ->and(Worker::where('code', 'MTTO-ELEC-01-RGD')->exists())->toBeTrue()
        ->and(Worker::where('code', 'not like', '%-RGD')->count())->toBe(0);

    expect(User::count())->toBe(5)
        ->and(User::where('id', '!=', $admin->id)->count())->toBe(4)
        ->and(User::where('id', '!=', $admin->id)->where('status', false)->count())->toBe(4)
        ->and($admin->fresh()->status)->toBeTrue();

    expect(DispatchNote::count())->toBe(5)
        ->and(DispatchNote::where('status', 'confirmed')->count())->toBe(5)
        ->and(DispatchNote::whereNull('confirmed_at')->count())->toBe(0)
        ->and(DispatchNote::whereDoesntHave('movements')->count())->toBe(0)
        ->and(Delivery::count())->toBe(6)
        ->and(Delivery::where('status', 'confirmed')->count())->toBe(6)
        ->and(Delivery::whereNull('confirmed_at')->count())->toBe(0)
        ->and(Delivery::whereDoesntHave('movements')->count())->toBe(0);

    $negativeStock = ProductVariant::query()
        ->withSum('inventoryMovements as current_stock', 'quantity')
        ->get()
        ->contains(fn (ProductVariant $variant): bool => (float) ($variant->current_stock ?? 0) < -0.0005);

    expect($negativeStock)->toBeFalse();

    SerializedItem::query()->each(function (SerializedItem $serial): void {
        $balance = (float) InventoryMovement::where('serialized_item_id', $serial->id)->sum('quantity');

        expect($balance)->toBe($serial->status === 'available' ? 1.0 : 0.0);
    });

    expect(Log::where('accion', 'CARGA_PLANTA_V1')->count())->toBe(1)
        ->and(Log::latest('id')->value('accion'))->toBe('CARGA_PLANTA_V1');

    $afterFirstRun = plantWarehouseSeederSnapshot();

    $this->travel(1)->minute();
    $this->seed(PlantWarehouseSeeder::class);

    expect(plantWarehouseSeederSnapshot())->toBe($afterFirstRun)
        ->and($admin->fresh()->getRawOriginal())->toBe($adminBefore)
        ->and(Log::where('accion', 'CARGA_PLANTA_V1')->count())->toBe(1);
});

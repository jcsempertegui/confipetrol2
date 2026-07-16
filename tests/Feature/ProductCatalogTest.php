<?php

use App\Livewire\CategoriesController;
use App\Livewire\ProductsController;
use App\Models\Category;
use App\Models\Log;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\SerializedItem;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'CATALOGO', 'guard_name' => 'web']);
    $role->syncPermissions(['ver-categoria', 'crear-categoria', 'editar-categoria', 'eliminar-categoria', 'gestionar-atributos', 'ver-producto', 'crear-producto', 'editar-producto', 'eliminar-producto']);
    $this->user = User::factory()->create(['status' => true]);
    $this->user->assignRole($role);
    $this->actingAs($this->user);
});

it('configures category attributes at different levels', function () {
    Livewire::test(CategoriesController::class)->set('name', 'EPP')->set('code', 'EPP')
        ->call('saveCategory')->assertHasNoErrors();
    $category = Category::first();
    Livewire::test(CategoriesController::class)
        ->set('selectedCategoryId', $category->id)
        ->set('attributeName', 'Talla')->set('attributeCode', 'talla')->set('attributeType', 'select')
        ->set('attributeScope', 'variant')->set('attributeOptions', '7, 8, 9')->call('saveAttribute')->assertHasNoErrors();
    expect((bool) Category::first()->attributes->first()->pivot->required)->toBeTrue();
});

it('creates a simple asset without exposing variants', function () {
    $category = Category::create(['name' => 'Activo', 'code' => 'ACT', 'status' => true]);
    foreach (['Marca', 'Modelo', 'Número de serie'] as $name) {
        $attribute = ProductAttribute::create(['name' => $name, 'code' => 'act-'.str($name)->slug(), 'type' => 'text', 'scope' => 'product', 'status' => true]);
        $category->attributes()->attach($attribute, ['required' => true]);
    }
    $values = $category->attributes->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['Toshiba', 'Satellite', 'SN-001'][$i]])->all();
    Livewire::test(ProductsController::class)->set('category_id', $category->id)->set('name', 'Laptop Toshiba')
        ->set('productValues', $values)->call('save')->assertHasNoErrors();
    expect(Product::first()->code)->toBe('ACT-0001')->and(Product::first()->variants)->toHaveCount(1);
    $log = Log::where('modulo', 'PRODUCTOS')->latest()->first();
    expect($log->actor_login)->toBe($this->user->login)
        ->and(collect($log->changes())->pluck('field'))->toContain('nombre', 'atributos › Marca', 'atributos › Modelo', 'atributos › Número de serie');
});

it('creates one product with multiple size variants', function () {
    $category = Category::create(['name' => 'EPP', 'code' => 'EPP', 'status' => true]);
    $size = ProductAttribute::create(['name' => 'Talla', 'code' => 'talla', 'type' => 'select', 'scope' => 'variant', 'options' => ['7', '8'], 'status' => true]);
    $category->attributes()->attach($size, ['required' => true]);
    $prefix = 'GUA-'.str()->random(6);
    Livewire::test(ProductsController::class)->set('category_id', $category->id)->set('code', $prefix)->set('name', 'Guante anticorte')
        ->set('variants', [
            ['id' => null, 'sku' => '', 'name' => 'Talla 7', 'values' => [$size->id => '7'], 'serials' => ''],
            ['id' => null, 'sku' => '', 'name' => 'Talla 8', 'values' => [$size->id => '8'], 'serials' => ''],
        ])->call('save')->assertHasNoErrors();
    expect(Product::first()->variants)->toHaveCount(2)
        ->and(Product::first()->variants->pluck('sku')->all())->toBe([$prefix.'-7', $prefix.'-8']);
});

it('enforces globally unique serial numbers', function () {
    $category = Category::create(['name' => 'Activos', 'code' => 'ACT', 'status' => true]);
    $serial = ProductAttribute::create(['name' => 'Número de serie', 'code' => 'act-serial', 'type' => 'text', 'scope' => 'unit', 'status' => true]);
    $category->attributes()->attach($serial, ['required' => true]);

    Livewire::test(ProductsController::class)->set('category_id', $category->id)->set('name', 'Laptop Toshiba')
        ->set('variants.0.serials', 'SN-001')->call('save')->assertHasNoErrors();

    expect(Product::first()->tracking_type)->toBe('serialized')
        ->and(SerializedItem::where('serial_number', 'SN-001')->exists())->toBeTrue();

    Livewire::test(ProductsController::class)->set('category_id', $category->id)->set('name', 'Laptop Lenovo')
        ->set('variants.0.serials', 'SN-001')->call('save')->assertHasErrors('variants.0.serials');
});

it('deactivates removed variants without deleting their history', function () {
    $category = Category::create(['name' => 'EPP', 'code' => 'EPP', 'status' => true]);
    $size = ProductAttribute::create(['name' => 'Talla', 'code' => 'epp-talla', 'type' => 'select', 'scope' => 'variant', 'options' => ['7', '8'], 'status' => true]);
    $category->attributes()->attach($size, ['required' => true]);
    $product = Product::create(['category_id' => $category->id, 'code' => 'EPP-0001', 'name' => 'Guantes', 'status' => true]);
    $first = $product->variants()->create(['sku' => 'EPP-0001-7', 'name' => 'Talla 7', 'status' => true]);
    $second = $product->variants()->create(['sku' => 'EPP-0001-8', 'name' => 'Talla 8', 'status' => true]);
    $first->attributeValues()->create(['product_attribute_id' => $size->id, 'value' => '7']);
    $second->attributeValues()->create(['product_attribute_id' => $size->id, 'value' => '8']);

    Livewire::test(ProductsController::class)->call('edit', $product->id)
        ->set('variants', [['id' => $first->id, 'sku' => $first->sku, 'name' => $first->name, 'values' => [$size->id => '7'], 'serials' => '']])
        ->call('save')->assertHasNoErrors();

    expect($second->fresh())->not->toBeNull()->and($second->fresh()->status)->toBeFalse();
});

it('reserves sequential product codes per category', function () {
    $category = Category::create(['name' => 'Herramientas', 'code' => 'HER', 'status' => true]);
    foreach (['Taladro', 'Amoladora'] as $name) {
        Livewire::test(ProductsController::class)->set('category_id', $category->id)->set('name', $name)->call('save')->assertHasNoErrors();
    }

    expect(Product::where('category_id', $category->id)->orderBy('id')->pluck('code')->all())->toBe(['HER-0001', 'HER-0002'])
        ->and($category->fresh()->next_product_number)->toBe(3);
});

it('rejects invalid dynamic values and category changes', function () {
    $category = Category::create(['name' => 'EPP', 'code' => 'EPP', 'status' => true]);
    $other = Category::create(['name' => 'Activos', 'code' => 'ACT', 'status' => true]);
    $size = ProductAttribute::create(['name' => 'Talla', 'code' => 'epp-size', 'type' => 'select', 'scope' => 'variant', 'options' => ['7', '8'], 'status' => true]);
    $category->attributes()->attach($size, ['required' => true]);

    Livewire::test(ProductsController::class)->set('category_id', $category->id)->set('name', 'Guante')
        ->set('variants.0.values.'.$size->id, '99')->call('save')->assertHasErrors('variants.0.values.'.$size->id);

    $product = Product::create(['category_id' => $category->id, 'code' => 'EPP-0001', 'name' => 'Guante', 'status' => true]);
    Livewire::test(ProductsController::class)->call('edit', $product->id)->set('category_id', $other->id)
        ->call('save')->assertHasErrors('category_id');
});

it('does not detach attributes that already contain product history', function () {
    $category = Category::create(['name' => 'Activos', 'code' => 'ACT', 'status' => true]);
    $brand = ProductAttribute::create(['name' => 'Marca', 'code' => 'act-brand', 'type' => 'text', 'scope' => 'product', 'status' => true]);
    $category->attributes()->attach($brand, ['required' => true]);
    $product = Product::create(['category_id' => $category->id, 'code' => 'ACT-0001', 'name' => 'Laptop', 'status' => true]);
    $product->attributeValues()->create(['product_attribute_id' => $brand->id, 'value' => 'Toshiba']);

    Livewire::test(CategoriesController::class)->set('selectedCategoryId', $category->id)->call('removeAttribute', $brand->id);
    expect($category->fresh()->attributes()->whereKey($brand->id)->exists())->toBeTrue();
});

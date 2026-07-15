<?php

use App\Livewire\CategoriesController;
use App\Livewire\ProductsController;
use App\Models\Category;
use App\Models\Log;
use App\Models\Product;
use App\Models\ProductAttribute;
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

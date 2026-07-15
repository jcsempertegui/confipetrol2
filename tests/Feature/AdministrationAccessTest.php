<?php

use App\Livewire\LogsController;
use App\Models\Log;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('guests are redirected to login from administration', function () {
    foreach (['home', 'users', 'roles', 'logs', 'backups'] as $uri) {
        $this->get('/'.$uri)->assertRedirect('/login');
    }
});

test('an administrator can render every retained module', function () {
    $role = Role::create(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::all());
    $user = User::factory()->create();
    $user->assignRole($role);

    foreach (['home', 'users', 'roles', 'logs', 'backups'] as $uri) {
        $this->actingAs($user)->get('/'.$uri)->assertOk();
    }
});

test('users without permissions cannot enter protected modules', function () {
    $user = User::factory()->create();

    foreach (['users', 'roles', 'logs', 'backups'] as $uri) {
        $this->actingAs($user)->get('/'.$uri)->assertForbidden();
    }
});

test('an auditor can expand and see every changed field', function () {
    $role = Role::create(['name' => 'AUDITOR', 'guard_name' => 'web']);
    $role->givePermissionTo('ver-log');
    $user = User::factory()->create();
    $user->assignRole($role);
    $log = Log::create([
        'user_id' => $user->id, 'actor_login' => $user->login, 'modulo' => 'PRODUCTOS', 'accion' => 'EDITAR',
        'descripcion' => 'Producto Laptop', 'valores_anteriores' => ['marca' => 'Toshiba', 'modelo' => 'A1'],
        'valores_nuevos' => ['marca' => 'Lenovo', 'modelo' => 'T14'],
    ]);

    Livewire::actingAs($user)->test(LogsController::class)
        ->assertSee('+ 1 cambio(s) adicional(es)')
        ->call('toggleDetails', $log->id)
        ->assertSet("expandedLogs.$log->id", true)
        ->assertSee('Toshiba')->assertSee('Lenovo')->assertSee('A1')->assertSee('T14');
});

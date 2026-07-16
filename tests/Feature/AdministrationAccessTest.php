<?php

use App\Livewire\BackupsController;
use App\Livewire\LogsController;
use App\Livewire\UsersController;
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
        ->assertSee('Ver todos los cambios (2)')
        ->call('toggleDetails', $log->id)
        ->assertSet("expandedLogs.$log->id", true)
        ->assertSee('Toshiba')->assertSee('Lenovo')->assertSee('A1')->assertSee('T14');
});

test('only super admin can start a database restore', function () {
    $role = Role::create(['name' => 'OPERADOR BACKUP', 'guard_name' => 'web']);
    $role->syncPermissions(['ver-backup', 'crear-backup', 'restaurar-backup']);
    $user = User::factory()->create();
    $user->assignRole($role);

    Livewire::actingAs($user)->test(BackupsController::class)
        ->call('confirmRestoreFromList', 'backup_fake.sql')->assertForbidden();
});

test('authenticated users can open their profile', function () {
    $this->actingAs(User::factory()->create())->get('/profile')->assertOk()->assertSee('Mi perfil');
});

test('a user manager cannot assign the super admin role', function () {
    Role::create(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);
    $managerRole = Role::create(['name' => 'GESTOR', 'guard_name' => 'web']);
    $managerRole->syncPermissions(['ver-usuario', 'crear-usuario']);
    $manager = User::factory()->create();
    $manager->assignRole($managerRole);

    Livewire::actingAs($manager)->test(UsersController::class)
        ->set('role', 'SUPER ADMIN')->call('save')->assertForbidden();
});

test('inactive users and inactive roles lose access immediately', function () {
    $user = User::factory()->create(['status' => false]);
    $this->actingAs($user)->get('/home')->assertRedirect(route('login'));

    $role = Role::create(['name' => 'INACTIVO', 'guard_name' => 'web', 'status' => false]);
    $activeUser = User::factory()->create(['status' => true]);
    $activeUser->assignRole($role);
    $this->actingAs($activeUser)->get('/home')->assertRedirect(route('login'));
});

test('dashboard hides administrative metrics without permissions', function () {
    $this->actingAs(User::factory()->create())->get('/home')->assertOk()
        ->assertDontSee('Usuarios activos')->assertDontSee('Actividad reciente');
});

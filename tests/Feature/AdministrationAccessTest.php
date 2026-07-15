<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
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

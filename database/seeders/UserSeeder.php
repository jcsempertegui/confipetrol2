<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::all());
        $password = env('ADMIN_PASSWORD') ?: Str::password(16);
        $user = User::updateOrCreate(['login' => env('ADMIN_LOGIN', 'admin')], ['name' => env('ADMIN_NAME', 'Administrador'), 'document' => env('ADMIN_DOCUMENT', 'ADMIN-001'), 'email' => env('ADMIN_EMAIL', 'admin@example.com'), 'password' => Hash::make($password), 'status' => true]);
        $user->syncRoles([$role]);
        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn('Contraseña inicial del administrador: '.$password);
        }
    }
}

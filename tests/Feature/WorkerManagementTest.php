<?php

use App\Livewire\WorkersController;
use App\Models\Log;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $role = Role::create(['name' => 'GESTOR MOVIMIENTOS', 'guard_name' => 'web']);
    $role->syncPermissions(['ver-trabajador', 'crear-trabajador', 'editar-trabajador', 'eliminar-trabajador', 'restaurar-trabajador']);
    $this->user = User::factory()->create();
    $this->user->assignRole($role);
    $this->actingAs($this->user);
});

it('creates a normalized worker and audits every stored value', function () {
    Livewire::test(WorkersController::class)
        ->set('document', '  ci-123  ')->set('name', '  Ana  María ')->set('lastname', ' Pérez ')
        ->set('area', ' Operaciones ')->set('position', ' Técnica ')->set('email', ' ANA@EXAMPLE.COM ')
        ->set('start_date', now()->format('Y-m-d'))->call('save')->assertHasNoErrors();

    $worker = Worker::sole();
    expect($worker->code)->toBe('TRB-'.str_pad((string) $worker->id, 6, '0', STR_PAD_LEFT))->and($worker->document)->toBe('CI-123')
        ->and($worker->name)->toBe('Ana María')->and($worker->email)->toBe('ana@example.com');
    $log = Log::where('modulo', 'TRABAJADORES')->where('accion', 'CREAR')->sole();
    expect($log->valores_nuevos['documento'])->toBe('CI-123')
        ->and($log->valores_nuevos['área'])->toBe('Operaciones');
});

it('validates duplicate identity contact and future dates', function () {
    Worker::create(['code' => 'TRB-000001', 'document' => '123', 'name' => 'Juan', 'lastname' => 'Pérez', 'email' => 'juan@example.com']);

    Livewire::test(WorkersController::class)
        ->set('document', '123')->set('name', 'Otro')->set('lastname', 'Trabajador')
        ->set('email', 'juan@example.com')->set('start_date', now()->addDay()->format('Y-m-d'))
        ->call('save')->assertHasErrors(['document', 'email', 'start_date']);
});

it('deactivates workers without deleting them and records the change', function () {
    $worker = Worker::create(['code' => 'TRB-000001', 'document' => '123', 'name' => 'Juan', 'lastname' => 'Pérez']);

    Livewire::test(WorkersController::class)->call('toggleStatus', $worker->id);

    expect($worker->fresh()->status)->toBeFalse()->and(Worker::count())->toBe(1);
    $log = Log::where('modulo', 'TRABAJADORES')->where('accion', 'ELIMINAR')->sole();
    expect($log->valores_anteriores['estado'])->toBeTrue()->and($log->valores_nuevos['estado'])->toBeFalse();
});

it('prevents users without worker permissions from entering', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/workers')->assertForbidden();
});

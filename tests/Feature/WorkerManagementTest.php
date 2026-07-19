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
        ->set('area', ' Mantenimiento ')->set('position', ' Técnico Mtto. Eléctrico ')->set('email', ' ANA@EXAMPLE.COM ')
        ->set('start_date', now()->format('Y-m-d'))->call('save')->assertHasNoErrors();

    $worker = Worker::sole();
    expect($worker->code)->toBe('MTTO-ELEC-01-RGD')->and($worker->document)->toBe('CI-123')
        ->and($worker->name)->toBe('Ana María')->and($worker->email)->toBe('ana@example.com');
    $log = Log::where('modulo', 'TRABAJADORES')->where('accion', 'CREAR')->sole();
    expect($log->valores_nuevos['código'])->toBe('MTTO-ELEC-01-RGD')
        ->and($log->valores_nuevos['documento'])->toBe('CI-123')
        ->and($log->valores_nuevos['área'])->toBe('Mantenimiento');
});

it('uses independent worker sequences for each occupational specialty', function () {
    foreach ([
        ['document' => 'ELEC-01', 'name' => 'Ana', 'position' => 'Técnico Mtto. Eléctrico'],
        ['document' => 'ELEC-02', 'name' => 'Luis', 'position' => 'Técnica Electricista'],
        ['document' => 'MECA-01', 'name' => 'Eva', 'position' => 'Técnico Mecánico'],
        ['document' => 'INFR-01', 'name' => 'José', 'position' => 'Infraestructura'],
    ] as $worker) {
        Livewire::test(WorkersController::class)
            ->set('document', $worker['document'])
            ->set('name', $worker['name'])
            ->set('lastname', 'Prueba')
            ->set('position', $worker['position'])
            ->set('area', 'Mantenimiento')
            ->call('save')
            ->assertHasNoErrors();
    }

    expect(Worker::where('document', 'ELEC-01')->value('code'))->toBe('MTTO-ELEC-01-RGD')
        ->and(Worker::where('document', 'ELEC-02')->value('code'))->toBe('MTTO-ELEC-02-RGD')
        ->and(Worker::where('document', 'MECA-01')->value('code'))->toBe('MTTO-MECA-01-RGD')
        ->and(Worker::where('document', 'INFR-01')->value('code'))->toBe('MTTO-INFR-01-RGD');
});

it('normalizes manual worker codes and preserves unchanged historical codes', function () {
    Livewire::test(WorkersController::class)
        ->set('code', ' mtto-meca-77-rdg ')
        ->set('document', 'MANUAL-77')
        ->set('name', 'Mario')
        ->set('lastname', 'Rojas')
        ->set('position', 'Técnico Mecánico')
        ->call('save')
        ->assertHasNoErrors();

    expect(Worker::where('document', 'MANUAL-77')->value('code'))->toBe('MTTO-MECA-77-RGD');

    $legacy = Worker::create([
        'code' => 'PSL-TR-001',
        'document' => 'LEGACY-01',
        'name' => 'Registro',
        'lastname' => 'Histórico',
        'position' => 'Operador de Planta',
    ]);

    Livewire::test(WorkersController::class)
        ->call('edit', $legacy->id)
        ->set('phone', '70000001')
        ->call('save')
        ->assertHasNoErrors();

    expect($legacy->fresh()->code)->toBe('PSL-TR-001')
        ->and($legacy->fresh()->phone)->toBe('70000001');
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

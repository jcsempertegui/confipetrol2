<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';
    public $searchTerm, $searchPermission;

    public $name, $estado, $permisosSelected = [], $search, $selected_id;
    public $componentKey = 0;

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function getPermisosExcluidos()
    {
        $posType = auth()->user()->branche ? (int) auth()->user()->branche->pos_type : null;
        $esRestaurante = in_array($posType, [0, 4]);

        $permisosExcluidos = $esRestaurante ? [] : [
            'ver-variantes', 'crear-variantes', 'editar-variantes', 'eliminar-variantes', 'restaurar-variantes',
            'ver-adicionales', 'crear-adicionales', 'editar-adicionales', 'eliminar-adicionales', 'restaurar-adicionales',
        ];

        if (auth()->id() !== 1) {
            $permisosExcluidos = array_merge($permisosExcluidos, [
                'ver-ajustesadicionales',
                'ver-sucursales',
                'crear-sucursal',
                'editar-sucursal',
                'eliminar-sucursal'
            ]);
        }

        return $permisosExcluidos;
    }

    public function getGruposExcluidos()
    {
        $posType = auth()->user()->branche ? (int) auth()->user()->branche->pos_type : null;
        return in_array($posType, [0, 4]) ? [] : ['Zonas'];
    }

    public function render()
    {
        $roles = Role::where('name', 'like', '%' . $this->searchTerm . '%')
            ->where('id', '!=', 1)
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        $gruposExcluidos = $this->getGruposExcluidos();
        $permisosExcluidos = $this->getPermisosExcluidos();

        $permisos = Permission::when($this->searchPermission, function ($query) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchPermission . '%')
                    ->orWhere('grupo', 'like', '%' . $this->searchPermission . '%');
            });
        })
            ->when(!empty($gruposExcluidos), function ($query) use ($gruposExcluidos) {
                $query->whereNotIn('grupo', $gruposExcluidos);
            })
            ->when(!empty($permisosExcluidos), function ($query) use ($permisosExcluidos) {
                $query->whereNotIn('name', $permisosExcluidos);
            })
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.roles.roles', [
            'roles' => $roles,
            'permisos' => $permisos,
            'startCount' => $roles->total() - ($roles->currentPage() - 1) * $roles->perPage()
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function Create()
    {
        $rules = [
            'name' => 'required|unique:roles|min:2',
            'permisosSelected' => 'required|array'
        ];
        $message = [
            'name.required' => 'Nombre de el rol es requerido',
            'name.unique' => 'Ya existe el rol',
            'name.min' => 'El nombre del rol debe tener almenos 2 caracteres',
            'permisosSelected.required' => 'Debe seleccionar al menos un permiso',
            'permisosSelected.array' => 'Los permisos deben ser proporcionados en forma de matriz',
        ];

        $this->validate($rules, $message);

        $role = Role::create(['name' => $this->name]);
        $role->syncPermissions($this->permisosSelected);

        $this->logActivity(
            'ROLES', 'CREAR',
            "Creó rol: {$role->name} (" . count($this->permisosSelected) . " permisos)",
            $role->id,
            null,
            ['name' => $role->name, 'permisos' => count($this->permisosSelected)]
        );

        $this->dispatch('role-added', ['ROL CREADO CON ÉXITO.']);
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $this->resetInputFields();
        $role = Role::findOrFail($id);

        $this->selected_id = $role->id;
        $this->name = $role->name;

        $this->permisosSelected = $role->permissions->pluck('name')->toArray();
        $this->componentKey++;

        $this->resetValidation();
    }

    public function Update()
    {
        $rules = [
            'name' => "required|unique:roles,name,{$this->selected_id}|min:2",
            'permisosSelected' => 'required|array'
        ];

        $message = [
            'name.required' => 'Nombre de el rol es requerido',
            'name.unique' => 'Ya existe el rol',
            'name.min' => 'El nombre del rol debe tener al menos 2 caracteres',
            'permisosSelected.required' => 'Debe seleccionar al menos un permiso',
            'permisosSelected.array' => 'Los permisos deben ser proporcionados en forma de matriz',
        ];

        $this->validate($rules, $message);

        $role = Role::find($this->selected_id);
        $role->name = $this->name;
        $role->save();
        $role->syncPermissions($this->permisosSelected);

        $this->logActivity(
            'ROLES', 'EDITAR',
            "Editó rol: {$role->name} (" . count($this->permisosSelected) . " permisos)",
            $role->id,
            null,
            ['name' => $role->name, 'permisos' => count($this->permisosSelected)]
        );

        $this->dispatch('role-updated', 'ROL ACTUALIZADO EXITOSAMENTE.');
        $this->resetInputFields();
    }

    public function Destroy($id)
    {
        if ($id) {
            $role = Role::find($id);
            if ($role) {
                $newEstado = $role->status == 1 ? 0 : 1;
                $role->update(['status' => $newEstado]);
                $this->logActivity(
                    'ROLES', $newEstado == 1 ? 'RESTAURAR' : 'ELIMINAR',
                    ($newEstado == 1 ? 'Activó' : 'Desactivó') . " rol: {$role->name}",
                    $role->id
                );
                $message = $newEstado == 1 ? 'ROL ACTIVADO EXITOSAMENTE.' : 'ROL DESACTIVADO EXITOSAMENTE.';
                $this->dispatch('role-deleted', $message);
            }
        }
    }

    public function AsignarRoles($rolesList)
    {
        if ($this->userSelected > 0) {
            $user = User::find($this->userSelected);
            if ($user) {
                $this->dispatch('msg-ok', 'Roles asignados correctamente');
                $this->resetInputFields();
            }
        }
    }

    public function isGroupSelected($grupo)
    {
        $permisosGrupo = Permission::where('grupo', $grupo)
            ->whereNotIn('name', $this->getPermisosExcluidos())
            ->pluck('name')->toArray();
            
        if (empty($permisosGrupo)) {
            return false;
        }
        
        return collect($permisosGrupo)->every(fn($permiso) => in_array($permiso, $this->permisosSelected));
    }

    public function toggleGroup($grupo, $checked)
    {
        $permisosGrupo = Permission::where('grupo', $grupo)
            ->whereNotIn('name', $this->getPermisosExcluidos())
            ->pluck('name')->toArray();

        if ($checked) {
            $this->permisosSelected = array_values(array_unique(array_merge($this->permisosSelected, $permisosGrupo)));
        } else {
            $this->permisosSelected = array_values(array_diff($this->permisosSelected, $permisosGrupo));
        }
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->searchPermission = '';
        $this->selected_id = 0;
        $this->permisosSelected = [];
        $this->componentKey++;
        $this->resetValidation();
    }
}
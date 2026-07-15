<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\AuditLog;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersController extends Component
{
    use AuditLog, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $user_id;

    public $login = '';

    public $name = '';

    public $lastname = '';

    public $document = '';

    public $email = '';

    public $phone = '';

    public $password = '';

    public $password_confirmation = '';

    public $role = '';

    public $status = 1;

    public $searchTerm = '';

    public $perPage = 20;

    public $isEditMode = false;

    public function render()
    {
        $users = User::with('roles')->when($this->searchTerm, function ($query) {
            $term = '%'.$this->searchTerm.'%';
            $query->where(fn ($q) => $q->where('login', 'like', $term)->orWhere('name', 'like', $term)
                ->orWhere('lastname', 'like', $term)->orWhere('email', 'like', $term)->orWhere('document', 'like', $term));
        })->latest('id')->paginate($this->perPage);

        return view('livewire.users.users', ['users' => $users, 'roles' => Role::orderBy('name')->get()])
            ->extends('layouts.theme.app');
    }

    public function updatedSearchTerm(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        abort_unless(auth()->user()->can('crear-usuario'), 403);
        $this->resetForm();
        $this->dispatch('show-user-modal');
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('editar-usuario'), 403);
        $user = User::findOrFail($id);
        foreach (['login', 'name', 'lastname', 'document', 'email', 'phone', 'status'] as $field) {
            $this->{$field} = $user->{$field};
        }
        $this->user_id = $user->id;
        $this->role = $user->roles->first()?->name ?? '';
        $this->isEditMode = true;
        $this->resetValidation();
        $this->dispatch('show-user-modal');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->isEditMode ? 'editar-usuario' : 'crear-usuario'), 403);
        $id = $this->user_id;
        $this->validate([
            'login' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($id)],
            'name' => ['required', 'string', 'max:150'], 'lastname' => ['nullable', 'string', 'max:150'],
            'document' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30'], 'role' => ['required', Rule::exists('roles', 'name')],
            'status' => ['required', 'boolean'], 'password' => [$this->isEditMode ? 'nullable' : 'required', 'confirmed', 'min:8'],
        ]);
        $user = $this->isEditMode ? User::findOrFail($id) : new User;
        $before = $user->exists ? array_merge($user->only(['login', 'name', 'lastname', 'document', 'email', 'phone', 'status']), ['rol' => $user->roles->pluck('name')->all()]) : null;
        $user->fill(['login' => $this->login, 'name' => $this->name, 'lastname' => $this->lastname ?: null,
            'document' => $this->document, 'email' => $this->email, 'phone' => $this->phone ?: null, 'status' => $this->status]);
        if ($this->password !== '') {
            $user->password = $this->password;
        }
        $user->save();
        $user->syncRoles([$this->role]);
        $after = array_merge($user->fresh()->only(['login', 'name', 'lastname', 'document', 'email', 'phone', 'status']), ['rol' => $user->fresh()->roles->pluck('name')->all()]);
        $this->logActivity('USUARIOS', $this->isEditMode ? 'EDITAR' : 'CREAR', 'Usuario '.$user->login, $user->id, $before, $after);
        $this->dispatch('hide-user-modal');
        $this->dispatch('alert', 'Usuario guardado correctamente.', 'success');
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(auth()->user()->can('eliminar-usuario'), 403);
        abort_if($id === auth()->id(), 422, 'No puede desactivar su propia cuenta.');
        $user = User::findOrFail($id);
        $before = ['status' => (bool) $user->status];
        $user->update(['status' => $user->status ? 0 : 1]);
        $this->logActivity('USUARIOS', $user->status ? 'RESTAURAR' : 'ELIMINAR', 'Cambio de estado de '.$user->login, $user->id, $before, ['status' => (bool) $user->status]);
    }

    private function resetForm(): void
    {
        $this->reset(['user_id', 'login', 'name', 'lastname', 'document', 'email', 'phone', 'password', 'password_confirmation', 'role', 'isEditMode']);
        $this->status = 1;
        $this->resetValidation();
    }
}

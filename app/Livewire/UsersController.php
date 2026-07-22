<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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

        $roles = Role::where('status', true)
            ->when(! auth()->user()->hasRole('SUPER ADMIN'), fn ($query) => $query->where('name', '!=', 'SUPER ADMIN'))
            ->orderBy('name')->get();

        return view('livewire.users.users', ['users' => $users, 'roles' => $roles])
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
        abort_if($user->hasRole('SUPER ADMIN') && ! auth()->user()->hasRole('SUPER ADMIN'), 403);
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
        $this->login = Str::lower(trim($this->login));
        $this->name = preg_replace('/\s+/u', ' ', trim($this->name));
        $this->lastname = preg_replace('/\s+/u', ' ', trim($this->lastname));
        $this->document = Str::upper(trim($this->document));
        $this->email = Str::lower(trim($this->email));
        $this->phone = trim($this->phone);
        abort_if($this->role === 'SUPER ADMIN' && ! auth()->user()->hasRole('SUPER ADMIN'), 403);
        $passwordRules = [$this->isEditMode ? 'nullable' : 'required', 'string', 'max:255', 'confirmed', Password::defaults()];
        $this->validate([
            'login' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users')->ignore($id)],
            'name' => ['required', 'string', 'max:150', "regex:/^[\pL\pM '\-.]+$/u"],
            'lastname' => ['nullable', 'string', 'max:150', "regex:/^[\pL\pM '\-.]+$/u"],
            'document' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9.\-]+$/', Rule::unique('users')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\- ]+$/'],
            'role' => ['required', Rule::exists('roles', 'name')->where('status', true)],
            'status' => ['required', 'boolean'], 'password' => $passwordRules,
        ]);
        $user = $this->isEditMode ? User::findOrFail($id) : new User;

        if ($user->exists && $user->id === auth()->id() && ! $this->status) {
            throw ValidationException::withMessages(['status' => 'No puede desactivar su propia cuenta.']);
        }
        if ($user->exists && $user->hasRole('SUPER ADMIN') && ($this->role !== 'SUPER ADMIN' || ! $this->status)) {
            throw ValidationException::withMessages(['role' => 'La cuenta SUPER ADMIN no puede desactivarse ni perder su rol principal.']);
        }

        $before = $user->exists ? array_merge($user->only(['login', 'name', 'lastname', 'document', 'email', 'phone', 'status']), ['rol' => $user->roles->pluck('name')->all()]) : null;
        DB::transaction(function () use ($user, $before) {
            $user->fill(['login' => $this->login, 'name' => $this->name, 'lastname' => $this->lastname ?: null,
                'document' => $this->document, 'email' => $this->email, 'phone' => $this->phone ?: null, 'status' => $this->status]);
            if ($this->password !== '') {
                $user->password = $this->password;
            }
            $user->save();
            $user->syncRoles([$this->role]);
            $freshUser = $user->fresh('roles');
            $after = array_merge($freshUser->only(['login', 'name', 'lastname', 'document', 'email', 'phone', 'status']), ['rol' => $freshUser->roles->pluck('name')->all()]);
            $this->logActivity('USUARIOS', $this->isEditMode ? 'EDITAR' : 'CREAR', 'Usuario '.$user->login, $user->id, $before, $after);
        });
        $this->dispatch('hide-user-modal');
        $this->dispatch('alert', 'Usuario guardado correctamente.', 'success');
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        $user = User::findOrFail($id);
        $permission = $user->status ? 'eliminar-usuario' : 'restaurar-usuario';
        abort_unless(auth()->user()->can($permission), 403);
        abort_if($id === auth()->id(), 422, 'No puede desactivar su propia cuenta.');
        abort_if($user->hasRole('SUPER ADMIN'), 403, 'La cuenta SUPER ADMIN no puede desactivarse.');
        $before = ['status' => (bool) $user->status];
        $user->update(['status' => $user->status ? 0 : 1]);
        if (! $user->status) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $this->logActivity('USUARIOS', $user->status ? 'RESTAURAR' : 'ELIMINAR', 'Cambio de estado de '.$user->login, $user->id, $before, ['status' => (bool) $user->status]);
    }

    private function resetForm(): void
    {
        $this->reset(['user_id', 'login', 'name', 'lastname', 'document', 'email', 'phone', 'password', 'password_confirmation', 'role', 'isEditMode']);
        $this->status = 1;
        $this->resetValidation();
    }
}

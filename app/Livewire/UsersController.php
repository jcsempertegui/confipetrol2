<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Branche;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsersController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $login, $name, $lastname, $document, $email, $phone, $role, $status, $user_id, $branch_id;
    public $isEditMode = false;
    public $searchTerm;
    public $roles;
    public $branches; // Faltaba definir esta propiedad pública

    // Variables para cambio de contraseña
    public $password_user_id;
    public $password_name;
    public $password_lastname;
    public $password_login;
    public $new_password;

    protected $listeners = ['delete'];

    ///////// ----------- Pagination------------- ////////////
    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }
    ///////// ----------- Fin------------- ////////////


    public function mount()
    {
        $this->roles = Role::where('status', 1)
            ->where('id', '!=', 1)
            ->orderByDesc('id')
            ->get();

        $this->branches = Branche::where('status', 1)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function render()
    {
        $users = User::with('branche')
            ->where('id', '!=', 1)
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('login', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('document', 'like', '%' . $this->searchTerm . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.users.users', [
            'users' => $users,
            'roles' => Role::orderBy('name', 'asc')->get(),
            'startCount' => $users->total() - ($users->currentPage() - 1) * $users->perPage()

        ])
            ->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->login = '';
        $this->name = '';
        $this->lastname = '';
        $this->document = '';
        $this->email = '';
        $this->phone = '';
        $this->role = '';
        $this->branch_id = '';
        $this->user_id = '';
        $this->isEditMode = false;
    }

    public function resetPasswordFields()
    {
        $this->resetValidation();
        $this->password_user_id = '';
        $this->password_name = '';
        $this->password_lastname = '';
        $this->password_login = '';
        $this->new_password = '';
    }

    public function storeOrUpdate()
    {
        $rules = [
            'login' => 'required|unique:users,login,' . ($this->isEditMode ? $this->user_id : ''),
            'email' => 'required|email|unique:users,email,' . ($this->isEditMode ? $this->user_id : ''),
            'name' => 'required|min:3',
            'lastname' => 'required|min:3',
            'document' => 'required|numeric|digits_between:7,12|unique:users,document,' . ($this->isEditMode ? $this->user_id : ''),
            'phone' => 'nullable|numeric|digits_between:7,8',
            'role' => 'required',
            'branch_id' => 'required',
        ];

        $messages = [
            'login.required' => 'El login es requerido',
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El correo electrónico debe tener un formato válido',
            'email.unique' => 'El correo electrónico ya está en uso',
            'name.required' => 'El nombre es requerido',
            'name.min' => 'El nombre debe tener al menos 3 caracteres',
            'lastname.required' => 'El apellido es requerido',
            'lastname.min' => 'El apellido debe tener al menos 3 caracteres',
            'document.required' => 'La cédula de identidad es requerida',
            'document.digits_between' => 'La cédula de identidad debe tener al menos entre 7 y 9 dígitos.',
            'document.numeric' => 'La cédula de identidad  debe contener solo números.',
            'document.unique' => 'La cédula de identidad ya está en uso',
            'phone.numeric' => 'El teléfono debe contener solo números.',
            'phone.digits_between' => 'El teléfono debe tener entre 7 y 8 dígitos.',
            'role.required' => 'El rol es requerido',
            'branch_id.required' => 'La sucursal es requerido'
        ];

        // VALIDAR DATOS
        $this->validate($rules, $messages);

        // =================================================================
        // INICIO LÓGICA DE LÍMITE DE USUARIOS (MAX_USERS)
        // =================================================================

        // Solo verificamos si estamos CREANDO un usuario nuevo (no editando)
        if (!$this->isEditMode) {
            $branch = Branche::find($this->branch_id);

            if ($branch) {
                // Contamos cuántos usuarios tiene actualmente esa sucursal
                $currentUsersCount = User::where('branch_id', $this->branch_id)->count();
                $limit = $branch->max_users; // Este dato viene de settings->adicionales

                // Si ya alcanzamos o superamos el límite, detenemos y mostramos error
                if ($currentUsersCount >= $limit) {
                    $this->dispatch('userStoreOrUpdate', "Error: Esta sucursal ha alcanzado el límite de {$limit} usuarios permitidos.", 'error');
                    return; // Detiene la ejecución, no crea el usuario
                }
            }
        }
        // =================================================================
        // FIN LÓGICA
        // =================================================================

        $userData = [
            'login' => $this->login,
            'name' => $this->name,
            'lastname' => $this->lastname,
            'document' => $this->document,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'branch_id' => $this->branch_id,
        ];
        if (!$this->isEditMode) {
            $userData['password'] = $this->document; // Contraseña por defecto el documento

            // También asignamos el límite de sesiones por defecto que tenga la sucursal configurada
            // Esto es opcional, pero útil si quieres que herede la config actual
            $branchConfig = Branche::find($this->branch_id);
            // Asumimos que guardaste branch_max_sessions en la tabla branches o settings, 
            // Si no tienes el campo directo en branches, usa un default:
            // $userData['max_sessions'] = $branchConfig->max_sessions ?? 1; 
        }

        $user = User::updateOrCreate(
            ['id' => $this->user_id],
            $userData
        );
        $user->syncRoles([$this->role]);

        $message = $this->isEditMode ? 'USUARIO ACTUALIZADO EXITOSAMENTE.' : 'USUARIO CREADO CON ÉXITO.';

        $this->resetInputFields();
        // Pasamos el tipo 'success' explícitamente
        $this->dispatch('userStoreOrUpdate', $message, 'success');
    }

    public function openPasswordModal($id)
    {
        $this->resetPasswordFields();

        $user = User::findOrFail($id);
        $this->password_user_id = $id;
        $this->password_name = $user->name;
        $this->password_lastname = $user->lastname;
        $this->password_login = $user->login;
    }

    public function changePassword()
    {
        $this->validate([
            'new_password' => 'required|min:2'
        ], [
            'new_password.required' => 'La contraseña es requerida',
            'new_password.min' => 'La contraseña debe tener al menos 2 caracteres'
        ]);

        $user = User::findOrFail($this->password_user_id);
        $user->update([
            'password' => Hash::make($this->new_password)
        ]);

        $this->resetPasswordFields();
        $this->dispatch('passwordChanged', 'CONTRASEÑA ACTUALIZADA EXITOSAMENTE.', 'success');
    }

    public function updatedName($value)
    {
        $this->generateLogin();
    }

    public function updatedLastname($value)
    {
        $this->generateLogin();
    }

    private function generateLogin()
    {
        if ($this->isEditMode) {
            return;
        }
        $firstName = explode(' ', $this->name)[0] ?? '';
        $lastName = explode(' ', $this->lastname)[0] ?? '';
        $baseLogin = strtolower($firstName . '.' . $lastName);

        $existingUser = User::where('login', $baseLogin)->exists();

        if (!$existingUser) {
            $this->login = $baseLogin;
        } else {
            $suffix = 1;
            while (User::where('login', $baseLogin . $suffix)->exists()) {
                $suffix++;
            }
            $this->login = $baseLogin . $suffix;
        }
    }

    public function edit($id)
    {
        $this->resetValidation();

        $user = User::findOrFail($id);
        $this->user_id = $id;
        $this->login = $user->login;
        $this->name = $user->name;
        $this->lastname = $user->lastname;
        $this->document = $user->document;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->role = $user->roles->pluck('name')->toArray();
        $this->branch_id = $user->branch_id;
        $this->status = $user->status;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $user = User::find($id);

        if ($user) {
            $newEstado = $user->status == 1 ? 0 : 1;
            $user->update([
                'status' => $newEstado
            ]);
            $message = $newEstado == 1 ? 'USUARIO RESTAURADO EXITOSAMENTE.' : 'USUARIO ELIMINADO EXITOSAMENTE.';
            $this->dispatch('userDeleted', $message, 'success');
        } else {
            session()->flash('message', 'USUARIO NO ENCONTRADO.');
        }
    }
}
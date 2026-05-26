<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;



class ProfilesController extends Component
{
    use WithFileUploads;

    public $name, $lastname, $document, $email, $password, $password_confirm,$image,$photo, $selected_id;

    public function render()
    {
        $user = User::find(Auth::user()->id);
        $this->selected_id = $user->id;
        $this->name = $user->name;
        $this->lastname = $user->lastname;
        $this->document = $user->document;
        $this->email = $user->email;
        return view('livewire.profiles.profile', [
            'user' => $user,
            'roles' => Role::orderBy('name','asc')->get()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }
    public function Update()
    {

        $rules = [
            'email' => "required|email|unique:users,email,{$this->selected_id}",
            'name' => 'required|min:3',
            'lastname' => 'required|min:3',
            'document' => 'required|min:7',
            'password' => 'same:password_confirm'
        ];

        $message = [
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida',
            'email.unique' => 'El correo electrónico ya está en uso',
            'name.required' => 'El nombre es requerido',
            'name.min' => 'El nombre debe tener al menos :min caracteres',
            'lastname.required' => 'El apellido es requerido',
            'lastname.min' => 'El apellido debe tener al menos :min caracteres',
            'document.required' => 'La cédula de identidad es requerida',
            'document.min' => 'La cédula de identidad debe tener al menos :min caracteres',
            'password.same' => 'La contraseña y su confirmación no coinciden',
        ];
      

        $this->validate($rules, $message);        
        $user = User::find(Auth::user()->id);
        
        if ($this->photo) {
            $imagePath = $this->photo->store('profiles', 'public');
        }
        
        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }     
       
        $user->update([
            'name' => $this->name,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'document' => $this->document,
            'password_confirm' => $this->password,
            'image' => $imagePath ?? $user->image,
        ]);

        $this->resetUI();
        $this->dispatch('profile-updated', 'SE ACTUALIZÓ TU PERFIL CON ÉXITO');
    }
    public function resetUI()
    {
        $this->password = '';
        $this->password_confirm = '';
        $this->photo = null;
        $this->resetValidation();       
    }

}
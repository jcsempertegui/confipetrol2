<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\AuditLog;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProfileController extends Component
{
    use AuditLog;

    public $name = '';

    public $lastname = '';

    public $email = '';

    public $phone = '';

    public function mount(): void
    {
        $this->fill(auth()->user()->only(['name', 'lastname', 'email', 'phone']));
    }

    public function save(): void
    {
        $user = User::findOrFail(auth()->id());
        $data = $this->validate([
            'name' => 'required|string|max:150', 'lastname' => 'nullable|string|max:150',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
        ]);
        $before = $user->only(['name', 'lastname', 'email', 'phone']);
        $user->update($data);
        $this->logActivity('USUARIOS', 'EDITAR_PERFIL', 'Actualización del perfil propio', $user->id, $before, $user->fresh()->only(['name', 'lastname', 'email', 'phone']));
        $this->dispatch('alert', 'Perfil actualizado correctamente.', 'success');
    }

    public function render()
    {
        return view('livewire.profile.profile')->extends('layouts.theme.app');
    }
}

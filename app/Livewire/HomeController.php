<?php

namespace App\Livewire;

use App\Models\Log;
use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class HomeController extends Component
{
    public function render()
    {
        $user = auth()->user();
        $metrics = [];
        if ($user->can('ver-usuario')) {
            $metrics[] = ['Usuarios activos', User::where('status', 1)->count(), 'bx-user-check', 'success'];
            $metrics[] = ['Usuarios inactivos', User::where('status', 0)->count(), 'bx-user-x', 'danger'];
        }
        if ($user->can('ver-rol')) {
            $metrics[] = ['Roles', Role::count(), 'bx-shield-quarter', 'primary'];
        }
        if ($user->can('ver-log')) {
            $metrics[] = ['Acciones de hoy', Log::whereDate('created_at', today())->count(), 'bx-history', 'warning'];
        }

        return view('livewire.home.home', [
            'metrics' => $metrics,
            'recentLogs' => $user->can('ver-log') ? Log::with('user')->latest()->limit(8)->get() : collect(),
            'canViewLogs' => $user->can('ver-log'),
        ])->extends('layouts.theme.app');
    }
}

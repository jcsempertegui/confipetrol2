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
        return view('livewire.home.home', [
            'activeUsers' => User::where('status', 1)->count(),
            'inactiveUsers' => User::where('status', 0)->count(),
            'rolesCount' => Role::count(),
            'todayLogs' => Log::whereDate('created_at', today())->count(),
            'recentLogs' => Log::with('user')->latest()->limit(8)->get(),
        ])->extends('layouts.theme.app');
    }
}

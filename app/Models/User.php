<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'login',
        'name',
        'lastname',
        'document',
        'email',
        'phone',
        'image',
        'password',
        'branch_id',
        'profile',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function branche()
    {
        return $this->belongsTo(Branche::class, 'branch_id');
    }

    protected $appends = ['branch_user_id'];
    
    public function getBranchUserIdAttribute()
    {
        return session('branch_user_id') ?? $this->branch_id;
    }
}
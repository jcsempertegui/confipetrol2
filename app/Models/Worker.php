<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    protected $fillable = [
        'code', 'document', 'name', 'lastname', 'position', 'area', 'phone',
        'email', 'start_date', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'status' => 'boolean'];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->name.' '.$this->lastname);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}

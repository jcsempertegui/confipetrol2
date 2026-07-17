<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = ['corrected_from_id', 'number', 'worker_id', 'delivery_date', 'reason', 'notes', 'status', 'created_by', 'confirmed_by', 'annulled_by', 'confirmed_at', 'annulled_at', 'annul_reason'];

    protected function casts(): array
    {
        return ['delivery_date' => 'date', 'confirmed_at' => 'datetime', 'annulled_at' => 'datetime'];
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function annuller()
    {
        return $this->belongsTo(User::class, 'annulled_by');
    }

    public function correctedFrom()
    {
        return $this->belongsTo(self::class, 'corrected_from_id');
    }

    public function correction()
    {
        return $this->hasOne(self::class, 'corrected_from_id');
    }
}

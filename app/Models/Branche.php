<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branche extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'branch_type',
        'pos_type',
        'has_production_areas',
        'enable_size_color',
        'enable_product_gallery',
        'enable_staff_per_detail',
        'requires_cashbox',
        'name',
        'phone',
        'address',
        'status',
        'license_type',
        'license_duration',
        'license_start_date',
        'license_end_date',
        'max_users',
        'camera_barcode_enabled',
        'loyalty_program',
        'online_orders',
        'advanced_reports',
        'invoice_type',
        'default_tax',
        'default_currency',
        'email_notifications',
        'sms_notifications',
        'low_stock_alerts',
        'ambiente',
        'codigo_sistema',
        'token',
    ];

    public function inventories()
    {
        return $this->hasManyThrough(Inventorie::class, Warehouse::class);
    }
}
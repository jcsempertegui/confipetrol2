<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSetting extends Model
{
    use HasFactory;

    protected $table = 'document_settings';

    protected $fillable = [
        'branch_id',
        'document_type',
        'paper_size',
        'show_logo',
        'show_business_name',
        'show_address',
        'show_phone',
        'show_client',
        'show_cashier',
        'show_unit_price',
        'custom_title',
        'footer_text'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branche::class);
    }
}
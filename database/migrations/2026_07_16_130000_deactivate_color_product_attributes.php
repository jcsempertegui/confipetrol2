<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_attributes')->where(function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['color'])->orWhereRaw('LOWER(code) = ?', ['color'])->orWhereRaw('LOWER(code) LIKE ?', ['%-color']);
        })->update(['status' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // No se reactiva automáticamente para no alterar decisiones posteriores del usuario.
    }
};

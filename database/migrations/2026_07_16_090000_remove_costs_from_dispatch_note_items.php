<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('dispatch_note_items', 'unit_cost')) {
            Schema::table('dispatch_note_items', fn (Blueprint $table) => $table->dropColumn('unit_cost'));
        }
    }

    public function down(): void
    {
        // El sistema no gestiona información económica; el costo no se restaura.
    }
};

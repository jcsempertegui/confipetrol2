<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_notes', function (Blueprint $table) {
            $table->foreignId('corrected_from_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('dispatch_notes')
                ->restrictOnDelete();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('corrected_from_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('deliveries')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corrected_from_id');
        });

        Schema::table('dispatch_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corrected_from_id');
        });
    }
};

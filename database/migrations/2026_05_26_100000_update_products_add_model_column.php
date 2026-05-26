<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // model ya existe en la tabla, solo hacemos categorie_id nullable para tipo ACTIVO
            $table->unsignedBigInteger('categorie_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('categorie_id')->nullable(false)->change();
        });
    }
};

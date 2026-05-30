<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('modulo', 50)->nullable();
            $table->string('accion', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->text('valores_anteriores')->nullable();
            $table->text('valores_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};

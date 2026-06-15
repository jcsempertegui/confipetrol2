<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('remitos', function (Blueprint $table) {
            $table->id();
            $table->string('remito_number', 60)->unique();
            $table->enum('tipo', ['INGRESO', 'EGRESO'])->default('EGRESO');
            $table->string('contrato', 150)->nullable();
            $table->string('senores', 150)->nullable();
            $table->string('atencion', 150)->nullable();
            $table->string('campo', 150)->nullable();
            $table->string('n_orden', 100)->nullable();
            $table->text('observations')->nullable();
            $table->string('despachado_por', 150)->nullable();
            $table->string('transportado_por', 150)->nullable();
            $table->string('placa', 30)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('worker_id')->nullable()->constrained('workers')->nullOnDelete(); // ← AGREGADO
            $table->timestamps();

            $table->index('branch_id');
            $table->index('status');
            $table->index('tipo');
            $table->index('worker_id'); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remitos');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 50)->unique();
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);

            //RESTAURANTES
            $table->string('order_number', 50)->nullable()->unique(); // Número de comanda
            $table->string('order_type')->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('waiter_id')->nullable()->constrained('users')->onDelete('set null');
            //FIN RESTAURANTES

            $table->tinyInteger('status')->default(1);
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices adicionales para consultas frecuentes
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('waiter_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
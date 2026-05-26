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
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->decimal('discount', 10, 2)->default(0);
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            // RESTAURANTE
            $table->text('observations')->nullable();
            $table->unsignedInteger('takeaway_quantity')->default(0);
            // PELUQUERÍAS / SPAS (Asignación por servicio)
            $table->foreignId('employee_id')->nullable()->constrained('users')->onDelete('set null');

            $table->integer('quantity');
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->string('price_type')->default('normal');
            $table->integer('wholesale_min_quantity')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->timestamps();

            // Índices adicionales para optimización
            $table->index('product_id');
            $table->index('sale_id');
            $table->index('price_type');
            $table->index('employee_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->unsignedBigInteger('sku_id')->nullable();
            $table->foreign('sku_id')->references('id')->on('product_skus')->onDelete('set null');
            $table->integer('quantity');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index('delivery_id');
            $table->index('product_id');
            $table->index('sku_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_details');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('remito_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remito_id')->constrained('remitos')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('sku_id')->nullable()->constrained('product_skus')->onDelete('set null');
            $table->integer('quantity');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index('remito_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remito_details');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('size_id')
                ->nullable()
                ->constrained('sizes')
                ->nullOnDelete();
            $table->foreignId('color_id')
                ->nullable()
                ->constrained('colors')
                ->nullOnDelete();
            $table->string('sku');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('stock')->default(1);
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_skus');
    }
};
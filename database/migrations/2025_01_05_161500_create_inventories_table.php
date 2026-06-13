<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->integer('profit')->default(0);
            $table->integer('stock_lot')->default(0);
            $table->integer('stock_nolot')->default(0);
            $table->integer('stock')->virtualAs('stock_lot + stock_nolot');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index('product_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
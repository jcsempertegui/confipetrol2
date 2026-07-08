<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->decimal('previous_stock', 12, 4)->default(0);
            $table->decimal('new_stock', 12, 4)->default(0);
            $table->decimal('difference', 12, 4)->default(0);
            $table->string('reason');
            $table->decimal('cost', 12, 4)->default(0);
            $table->decimal('total', 12, 4)->default(0);
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('lot_id')->nullable()->constrained('lots')->onDelete('set null');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};

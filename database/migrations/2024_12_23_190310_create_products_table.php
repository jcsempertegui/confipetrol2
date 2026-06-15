<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('features')->nullable();
            $table->string('model', 100)->nullable();
            $table->tinyInteger('type')->default(0);
            $table->tinyInteger('has_loyalty')->default(0)->nullable();
            $table->integer('loyalty_req_qty')->default(0)->nullable();
            $table->integer('minimum_stock')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('brand_id')->constrained('brands')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->timestamps();

            $table->index('code');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
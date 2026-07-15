<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('type', 20)->default('text');
            $table->string('scope', 20)->default('product');
            $table->json('options')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('category_product_attribute', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('required')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['category_id', 'product_attribute_id'], 'category_attribute_primary');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('tracking_type', 20)->default('bulk');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->unique(['product_id', 'product_attribute_id'], 'product_attribute_value_unique');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('name')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->unique(['product_variant_id', 'product_attribute_id'], 'variant_attribute_value_unique');
        });

        Schema::create('serialized_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number', 150)->unique();
            $table->string('status', 30)->default('available');
            $table->timestamps();
        });

        Schema::create('serialized_item_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serialized_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->unique(['serialized_item_id', 'product_attribute_id'], 'item_attribute_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serialized_item_attribute_values');
        Schema::dropIfExists('serialized_items');
        Schema::dropIfExists('variant_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('products');
        Schema::dropIfExists('category_product_attribute');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('categories');
    }
};

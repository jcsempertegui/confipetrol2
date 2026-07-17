<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });
        DB::table('document_sequences')->insert([
            ['key' => 'dispatch_entry', 'next_number' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dispatch_exit', 'next_number' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('dispatch_notes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->nullable()->unique();
            $table->enum('type', ['entry', 'exit'])->index();
            $table->date('document_date')->index();
            $table->string('counterparty', 180);
            $table->string('reason', 180)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'annulled'])->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('annulled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('annulled_at')->nullable();
            $table->string('annul_reason', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('dispatch_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->unique(['dispatch_note_id', 'product_variant_id'], 'dispatch_note_variant_unique');
        });

        Schema::create('dispatch_note_serialized_items', function (Blueprint $table) {
            $table->foreignId('dispatch_note_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('serialized_item_id')->constrained()->restrictOnDelete();
            $table->primary(['dispatch_note_item_id', 'serialized_item_id'], 'dispatch_note_serial_primary');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('serialized_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('dispatch_note_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('inventory_movements')->restrictOnDelete();
            $table->string('movement_type', 40);
            $table->decimal('quantity', 14, 3);
            $table->timestamp('occurred_at')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['product_variant_id', 'occurred_at']);
            $table->index(['serialized_item_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('dispatch_note_serialized_items');
        Schema::dropIfExists('dispatch_note_items');
        Schema::dropIfExists('dispatch_notes');
        Schema::dropIfExists('document_sequences');
    }
};

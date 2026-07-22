<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->nullable()->unique();
            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->date('delivery_date')->index();
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
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->unique(['delivery_id', 'product_variant_id'], 'delivery_variant_unique');
        });
        Schema::create('delivery_serialized_items', function (Blueprint $table) {
            $table->foreignId('delivery_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('serialized_item_id')->constrained()->restrictOnDelete();
            $table->primary(['delivery_item_id', 'serialized_item_id'], 'delivery_serial_primary');
        });
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('delivery_id')->nullable()->after('dispatch_note_id')->constrained()->restrictOnDelete();
        });
        DB::table('document_sequences')->insert(['key' => 'delivery', 'next_number' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('inventory_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('delivery_id'));
        Schema::dropIfExists('delivery_serialized_items');
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('deliveries');
        DB::table('document_sequences')->where('key', 'delivery')->delete();
    }
};

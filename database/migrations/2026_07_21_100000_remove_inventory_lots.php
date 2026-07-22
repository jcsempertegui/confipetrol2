<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inventory_lot_allocations');

        if (Schema::hasColumn('inventory_movements', 'inventory_lot_id')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->dropForeign(['inventory_lot_id']);
            });
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->dropIndex('inventory_movement_lot_date_index');
                $table->dropColumn('inventory_lot_id');
            });
        }

        if (Schema::hasColumn('dispatch_note_items', 'inventory_lot_id')) {
            Schema::table('dispatch_note_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('inventory_lot_id');
                $table->dropColumn(['lot_number', 'expiration_date']);
            });
        }

        Schema::dropIfExists('inventory_lots');
    }

    public function down(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('lot_number', 100);
            $table->date('expiration_date')->nullable()->index();
            $table->date('received_at')->nullable();
            $table->boolean('is_legacy')->default(false);
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['product_variant_id', 'lot_number'], 'inventory_lot_variant_number_unique');
            $table->index(['product_variant_id', 'expiration_date'], 'inventory_lot_variant_expiry_index');
        });

        Schema::table('dispatch_note_items', function (Blueprint $table) {
            $table->foreignId('inventory_lot_id')->nullable()->after('product_variant_id')->constrained('inventory_lots')->nullOnDelete();
            $table->string('lot_number', 100)->nullable()->after('quantity');
            $table->date('expiration_date')->nullable()->after('lot_number');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('inventory_lot_id')->nullable()->after('product_variant_id')->constrained('inventory_lots')->restrictOnDelete();
            $table->index(['inventory_lot_id', 'occurred_at'], 'inventory_movement_lot_date_index');
        });

        Schema::create('inventory_lot_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->restrictOnDelete();
            $table->foreignId('dispatch_note_item_id')->nullable()->constrained('dispatch_note_items')->cascadeOnDelete();
            $table->foreignId('delivery_item_id')->nullable()->constrained('delivery_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
            $table->unique(['inventory_lot_id', 'dispatch_note_item_id'], 'lot_dispatch_item_unique');
            $table->unique(['inventory_lot_id', 'delivery_item_id'], 'lot_delivery_item_unique');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

        $this->backfillExistingInventory();
    }

    private function backfillExistingInventory(): void
    {
        DB::table('product_variants')->orderBy('id')->chunkById(250, function ($variants) {
            foreach ($variants as $variant) {
                $hasMovements = DB::table('inventory_movements')->where('product_variant_id', $variant->id)->exists();
                if (! $hasMovements) {
                    continue;
                }

                $expiration = $this->legacyExpirationDate((int) $variant->id, (int) $variant->product_id);
                $firstEntry = DB::table('inventory_movements')
                    ->where('product_variant_id', $variant->id)
                    ->where('quantity', '>', 0)
                    ->min('occurred_at');
                $lotId = DB::table('inventory_lots')->insertGetId([
                    'product_variant_id' => $variant->id,
                    'lot_number' => 'LEGACY-'.$variant->id,
                    'expiration_date' => $expiration,
                    'received_at' => $firstEntry ? Carbon::parse($firstEntry)->toDateString() : null,
                    'is_legacy' => true,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('inventory_movements')->where('product_variant_id', $variant->id)->update(['inventory_lot_id' => $lotId]);
                DB::table('dispatch_note_items')->where('product_variant_id', $variant->id)->whereNull('inventory_lot_id')->update([
                    'inventory_lot_id' => $lotId,
                    'lot_number' => 'LEGACY-'.$variant->id,
                    'expiration_date' => $expiration,
                ]);

                DB::table('dispatch_note_items')->where('product_variant_id', $variant->id)->orderBy('id')->chunkById(250, function ($items) use ($lotId) {
                    foreach ($items as $item) {
                        DB::table('inventory_lot_allocations')->insertOrIgnore([
                            'inventory_lot_id' => $lotId,
                            'dispatch_note_item_id' => $item->id,
                            'quantity' => $item->quantity,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
                DB::table('delivery_items')->where('product_variant_id', $variant->id)->orderBy('id')->chunkById(250, function ($items) use ($lotId) {
                    foreach ($items as $item) {
                        DB::table('inventory_lot_allocations')->insertOrIgnore([
                            'inventory_lot_id' => $lotId,
                            'delivery_item_id' => $item->id,
                            'quantity' => $item->quantity,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
            }
        });
    }

    private function legacyExpirationDate(int $variantId, int $productId): ?string
    {
        $expirationAttribute = static fn ($query) => $query
            ->where('pa.type', 'date')
            ->where(function ($identity) {
                $identity->whereRaw("LOWER(pa.code) LIKE '%venc%'")
                    ->orWhereRaw("LOWER(pa.name) LIKE '%venc%'")
                    ->orWhereRaw("LOWER(pa.code) LIKE '%caduc%'")
                    ->orWhereRaw("LOWER(pa.name) LIKE '%caduc%'")
                    ->orWhereRaw("LOWER(pa.code) LIKE '%expir%'")
                    ->orWhereRaw("LOWER(pa.name) LIKE '%expir%'");
            });

        $value = DB::table('variant_attribute_values as values')
            ->join('product_attributes as pa', 'pa.id', '=', 'values.product_attribute_id')
            ->where('values.product_variant_id', $variantId)
            ->where($expirationAttribute)
            ->value('values.value');
        $value ??= DB::table('product_attribute_values as values')
            ->join('product_attributes as pa', 'pa.id', '=', 'values.product_attribute_id')
            ->where('values.product_id', $productId)
            ->where($expirationAttribute)
            ->value('values.value');

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lot_allocations');
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inventory_movement_lot_date_index');
            $table->dropConstrainedForeignId('inventory_lot_id');
        });
        Schema::table('dispatch_note_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_lot_id');
            $table->dropColumn(['lot_number', 'expiration_date']);
        });
        Schema::dropIfExists('inventory_lots');
    }
};

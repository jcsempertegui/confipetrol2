<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('next_product_number')->default(1)->after('status');
        });

        foreach (DB::table('categories')->get() as $category) {
            $maximum = DB::table('products')->where('category_id', $category->id)->pluck('code')
                ->map(fn ($code) => preg_match('/^'.preg_quote($category->code, '/').'-(\d+)$/i', $code, $matches) ? (int) $matches[1] : 0)
                ->max() ?? 0;
            DB::table('categories')->where('id', $category->id)->update(['next_product_number' => $maximum + 1]);
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('serialized_items', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('serialized_items', fn (Blueprint $table) => $table->dropIndex(['status']));
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropIndex(['status']));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('next_product_number'));
    }
};

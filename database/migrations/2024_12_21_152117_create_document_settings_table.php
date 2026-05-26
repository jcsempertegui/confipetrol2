<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('document_type');
            $table->string('paper_size')->default('80');
            $table->tinyInteger('show_logo')->default(1);
            $table->tinyInteger('show_business_name')->default(1);
            $table->tinyInteger('show_address')->default(1);
            $table->tinyInteger('show_phone')->default(1);
            $table->tinyInteger('show_client')->default(1);
            $table->tinyInteger('show_cashier')->default(1);
            $table->tinyInteger('show_unit_price')->default(0);
            $table->string('custom_title')->nullable();
            $table->text('footer_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_settings');
    }
};
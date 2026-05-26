<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('branch_type');
            $table->tinyInteger('has_production_areas')->default(0);
            $table->tinyInteger('enable_size_color')->default(0);
            $table->tinyInteger('enable_product_gallery')->default(0);
            $table->tinyInteger('enable_staff_per_detail')->default(0);
            $table->tinyInteger('pos_type')->default(1);
            $table->tinyInteger('requires_cashbox')->default(1);
            $table->string('license_type')->nullable();
            $table->integer('license_duration')->nullable();
            $table->date('license_start_date')->nullable();
            $table->date('license_end_date')->nullable();
            $table->integer('max_users')->default(7);
            $table->tinyInteger('camera_barcode_enabled')->default(0);
            $table->tinyInteger('loyalty_program')->default(0);
            $table->tinyInteger('online_orders')->default(0);
            $table->tinyInteger('advanced_reports')->default(0);
            $table->string('invoice_type')->nullable();
            $table->decimal('default_tax', 5, 2)->default(0);
            $table->string('default_currency')->default('BOB');
            $table->tinyInteger('ambiente')->nullable();
            $table->string('codigo_sistema')->nullable();
            $table->text('token')->nullable();
            $table->tinyInteger('email_notifications')->default(0);
            $table->tinyInteger('sms_notifications')->default(0);
            $table->tinyInteger('low_stock_alerts')->default(0);
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
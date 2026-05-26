<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('business');
            $table->string('owner');
            $table->string('nit')->nullable();
            // $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            // $table->text('address')->nullable();
            $table->string('image')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            
            // ============ CAMPOS DE LICENCIA (GENERAL) ============
            $table->string('license_plan', 50)->default('estandar');
            $table->string('payment_type', 20)->default('mensual'); 
            $table->date('license_start_date')->nullable();
            $table->date('license_end_date')->nullable();
            $table->integer('months_paid')->default(1);
            $table->integer('years_paid')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
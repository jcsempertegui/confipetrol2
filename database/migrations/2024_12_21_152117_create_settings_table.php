<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('business');
            $table->string('owner');
            $table->string('nit')->nullable();
            $table->string('email')->nullable();
            $table->string('image')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

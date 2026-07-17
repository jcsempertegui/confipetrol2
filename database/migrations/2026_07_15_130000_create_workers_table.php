<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique();
            $table->string('document', 40)->unique();
            $table->string('name', 100);
            $table->string('lastname', 100);
            $table->string('position', 120)->nullable();
            $table->string('area', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable()->unique();
            $table->date('start_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();

            $table->index(['lastname', 'name']);
            $table->index('area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};

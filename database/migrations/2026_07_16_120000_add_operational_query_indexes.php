<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index(['movement_type', 'occurred_at'], 'inventory_movement_type_date_idx');
            $table->index(['delivery_id', 'occurred_at'], 'inventory_delivery_date_idx');
            $table->index(['dispatch_note_id', 'occurred_at'], 'inventory_dispatch_date_idx');
        });
        Schema::table('deliveries', function (Blueprint $table) {
            $table->index(['worker_id', 'delivery_date'], 'deliveries_worker_date_idx');
            $table->index(['status', 'delivery_date'], 'deliveries_status_date_idx');
        });
        Schema::table('dispatch_notes', function (Blueprint $table) {
            $table->index(['status', 'document_date'], 'dispatch_status_date_idx');
            $table->index(['type', 'document_date'], 'dispatch_type_date_idx');
        });
        Schema::table('logs', function (Blueprint $table) {
            $table->index(['modulo', 'created_at'], 'logs_module_date_idx');
            $table->index(['accion', 'created_at'], 'logs_action_date_idx');
            $table->index(['user_id', 'created_at'], 'logs_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inventory_movement_type_date_idx');
            $table->dropIndex('inventory_delivery_date_idx');
            $table->dropIndex('inventory_dispatch_date_idx');
        });
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex('deliveries_worker_date_idx');
            $table->dropIndex('deliveries_status_date_idx');
        });
        Schema::table('dispatch_notes', function (Blueprint $table) {
            $table->dropIndex('dispatch_status_date_idx');
            $table->dropIndex('dispatch_type_date_idx');
        });
        Schema::table('logs', function (Blueprint $table) {
            $table->dropIndex('logs_module_date_idx');
            $table->dropIndex('logs_action_date_idx');
            $table->dropIndex('logs_user_date_idx');
        });
    }
};

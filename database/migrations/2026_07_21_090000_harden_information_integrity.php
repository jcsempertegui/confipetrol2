<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $checks = [
        'users' => [
            'chk_users_max_sessions' => 'max_sessions BETWEEN 1 AND 10',
        ],
        'products' => [
            'chk_products_tracking_type' => "tracking_type IN ('bulk', 'serialized')",
        ],
        'serialized_items' => [
            'chk_serialized_items_status' => "status IN ('available', 'assigned', 'out_of_stock', 'inactive')",
        ],
        'dispatch_note_items' => [
            'chk_dispatch_note_items_quantity' => 'quantity > 0',
        ],
        'delivery_items' => [
            'chk_delivery_items_quantity' => 'quantity > 0',
        ],
        'inventory_movements' => [
            'chk_inventory_movements_quantity' => 'quantity <> 0',
            'chk_inventory_movements_source' => '((dispatch_note_id IS NOT NULL) + (delivery_id IS NOT NULL)) = 1',
            'chk_inventory_movements_serial_quantity' => 'serialized_item_id IS NULL OR ABS(quantity) = 1',
        ],
        'dispatch_notes' => [
            'chk_dispatch_notes_workflow' => "
                (status = 'draft' AND confirmed_by IS NULL AND confirmed_at IS NULL AND annulled_by IS NULL AND annulled_at IS NULL AND annul_reason IS NULL)
                OR (status = 'confirmed' AND number IS NOT NULL AND confirmed_by IS NOT NULL AND confirmed_at IS NOT NULL AND annulled_by IS NULL AND annulled_at IS NULL AND annul_reason IS NULL)
                OR (status = 'annulled' AND number IS NOT NULL AND confirmed_by IS NOT NULL AND confirmed_at IS NOT NULL AND annulled_by IS NOT NULL AND annulled_at IS NOT NULL AND CHAR_LENGTH(TRIM(annul_reason)) >= 10)
            ",
        ],
        'deliveries' => [
            'chk_deliveries_workflow' => "
                (status = 'draft' AND confirmed_by IS NULL AND confirmed_at IS NULL AND annulled_by IS NULL AND annulled_at IS NULL AND annul_reason IS NULL)
                OR (status = 'confirmed' AND number IS NOT NULL AND confirmed_by IS NOT NULL AND confirmed_at IS NOT NULL AND annulled_by IS NULL AND annulled_at IS NULL AND annul_reason IS NULL)
                OR (status = 'annulled' AND number IS NOT NULL AND confirmed_by IS NOT NULL AND confirmed_at IS NOT NULL AND annulled_by IS NOT NULL AND annulled_at IS NOT NULL AND CHAR_LENGTH(TRIM(annul_reason)) >= 10)
            ",
        ],
    ];

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ($this->checks as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
            }
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_inventory_movements_no_update`
            BEFORE UPDATE ON `inventory_movements`
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El Kardex es inmutable; registre una correccion o reversion'
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_inventory_movements_no_delete`
            BEFORE DELETE ON `inventory_movements`
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los movimientos del Kardex no pueden eliminarse'
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_logs_no_update`
            BEFORE UPDATE ON `logs`
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los registros de auditoria son inmutables'
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER `trg_logs_no_delete`
            BEFORE DELETE ON `logs`
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los registros de auditoria no pueden eliminarse'
        SQL);
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ([
            'trg_inventory_movements_no_update',
            'trg_inventory_movements_no_delete',
            'trg_logs_no_update',
            'trg_logs_no_delete',
        ] as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
        }

        foreach (array_reverse($this->checks, true) as $table => $constraints) {
            foreach (array_reverse(array_keys($constraints)) as $name) {
                DB::statement("ALTER TABLE `{$table}` DROP CONSTRAINT `{$name}`");
            }
        }
    }
};

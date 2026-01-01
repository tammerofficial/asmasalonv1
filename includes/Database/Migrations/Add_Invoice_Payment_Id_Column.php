<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Add_Invoice_Payment_Id_Column
{
    /**
     * Add payment_id column to asmaa_invoices if missing (idempotent).
     */
    public static function migrate(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'asmaa_invoices';

        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'payment_id'");
        if (!empty($column_exists)) {
            return;
        }

        // Add column + index (safe to ignore failures; will retry on next init).
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN payment_id BIGINT UNSIGNED NULL AFTER wc_order_id");
        $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_payment_id (payment_id)");
    }

    /**
     * Check if migration is needed.
     */
    public static function needs_migration(): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'asmaa_invoices';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'payment_id'");

        return empty($column_exists);
    }
}



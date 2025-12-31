<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Add_WooCommerce_Fields
{
    /**
     * Add WooCommerce integration fields to existing tables
     * This is safe to run multiple times (idempotent)
     */
    public static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Add fields to asmaa_products
        $products_table = $wpdb->prefix . 'asmaa_products';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$products_table} LIKE 'wc_product_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$products_table} ADD COLUMN wc_product_id BIGINT UNSIGNED NULL");
            $wpdb->query("ALTER TABLE {$products_table} ADD COLUMN wc_synced_at DATETIME NULL");
            $wpdb->query("ALTER TABLE {$products_table} ADD INDEX idx_wc_product_id (wc_product_id)");
        }

        // Add fields to asmaa_orders
        $orders_table = $wpdb->prefix . 'asmaa_orders';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$orders_table} LIKE 'wc_order_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$orders_table} ADD COLUMN wc_order_id BIGINT UNSIGNED NULL");
            $wpdb->query("ALTER TABLE {$orders_table} ADD COLUMN wc_synced_at DATETIME NULL");
            $wpdb->query("ALTER TABLE {$orders_table} ADD INDEX idx_wc_order_id (wc_order_id)");
        }

        // Add fields to asmaa_customers
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$customers_table} LIKE 'wc_customer_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$customers_table} ADD COLUMN wc_customer_id BIGINT UNSIGNED NULL");
            $wpdb->query("ALTER TABLE {$customers_table} ADD COLUMN wc_synced_at DATETIME NULL");
            $wpdb->query("ALTER TABLE {$customers_table} ADD INDEX idx_wc_customer_id (wc_customer_id)");
        }

        // Add fields to asmaa_payments
        $payments_table = $wpdb->prefix . 'asmaa_payments';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$payments_table} LIKE 'wc_payment_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$payments_table} ADD COLUMN wc_payment_id BIGINT UNSIGNED NULL");
            $wpdb->query("ALTER TABLE {$payments_table} ADD INDEX idx_wc_payment_id (wc_payment_id)");
        }

        // Add fields to asmaa_invoices
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$invoices_table} LIKE 'wc_order_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$invoices_table} ADD COLUMN wc_order_id BIGINT UNSIGNED NULL");
            $wpdb->query("ALTER TABLE {$invoices_table} ADD INDEX idx_wc_order_id (wc_order_id)");
        }

        // Create sync log table if not exists
        $sync_log_table = $wpdb->prefix . 'asmaa_wc_sync_log';
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$sync_log_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(50) NOT NULL,
            entity_id BIGINT UNSIGNED NULL,
            wc_entity_id BIGINT UNSIGNED NULL,
            sync_direction VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            error_message TEXT NULL,
            synced_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_status (status),
            KEY idx_synced_at (synced_at)
        ) {$charset};";
        
        dbDelta($sql);
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        global $wpdb;
        
        $products_table = $wpdb->prefix . 'asmaa_products';
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$products_table}");
        
        return !in_array('wc_product_id', $columns);
    }
}


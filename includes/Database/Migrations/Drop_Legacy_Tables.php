<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Drop_Legacy_Tables
{
    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        global $wpdb;
        
        // Check if any legacy table exists
        $tables_to_check = [
            'asmaa_orders',
            'asmaa_order_items',
            'asmaa_customers',
            'asmaa_products',
            'asmaa_wc_sync_log',
        ];
        
        foreach ($tables_to_check as $table_name) {
            $table = $wpdb->prefix . $table_name;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            if ($exists) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Drop legacy tables that are no longer needed
     * These tables have been replaced by WooCommerce/WordPress native tables
     */
    public static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tables to drop (in order to handle foreign keys)
        $tables_to_drop = [
            'asmaa_order_items',      // Drop first (has foreign key to asmaa_orders)
            'asmaa_orders',           // Drop second
            'asmaa_customers',        // Drop third
            'asmaa_products',         // Drop fourth
            'asmaa_wc_sync_log',      // Drop last (sync log table)
        ];

        foreach ($tables_to_drop as $table_name) {
            $table = $wpdb->prefix . $table_name;
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}


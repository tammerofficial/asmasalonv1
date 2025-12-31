<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Update_Related_Tables_Columns
{
    /**
     * Update column names in related tables to use wc_customer_id and wp_user_id
     */
    public static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Update bookings table
        $table = $wpdb->prefix . 'asmaa_bookings';
        self::update_column_if_exists($table, 'customer_id', 'wc_customer_id', 'BIGINT UNSIGNED NOT NULL');
        self::update_column_if_exists($table, 'staff_id', 'wp_user_id', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist
        self::add_foreign_key_if_not_exists($table, 'wc_customer_id', $wpdb->users, 'ID', 'CASCADE');
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'SET NULL');

        // 2. Update queue_tickets table
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        self::update_column_if_exists($table, 'customer_id', 'wc_customer_id', 'BIGINT UNSIGNED NULL');
        self::update_column_if_exists($table, 'staff_id', 'wp_user_id', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist
        self::add_foreign_key_if_not_exists($table, 'wc_customer_id', $wpdb->users, 'ID', 'SET NULL');
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'SET NULL');

        // 3. Update loyalty_transactions table
        $table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        self::update_column_if_exists($table, 'customer_id', 'wc_customer_id', 'BIGINT UNSIGNED NOT NULL');
        self::update_column_if_exists($table, 'performed_by', 'wp_user_id', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist
        self::add_foreign_key_if_not_exists($table, 'wc_customer_id', $wpdb->users, 'ID', 'CASCADE');
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'SET NULL');

        // 4. Update customer_memberships table
        $table = $wpdb->prefix . 'asmaa_customer_memberships';
        self::update_column_if_exists($table, 'customer_id', 'wc_customer_id', 'BIGINT UNSIGNED NOT NULL');
        
        // Add foreign key if it doesn't exist
        self::add_foreign_key_if_not_exists($table, 'wc_customer_id', $wpdb->users, 'ID', 'CASCADE');

        // 5. Update staff_commissions table
        $table = $wpdb->prefix . 'asmaa_staff_commissions';
        self::update_column_if_exists($table, 'staff_id', 'wp_user_id', 'BIGINT UNSIGNED NOT NULL');
        self::update_column_if_exists($table, 'approved_by', 'wp_user_id_approved', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'CASCADE');
        self::add_foreign_key_if_not_exists($table, 'wp_user_id_approved', $wpdb->users, 'ID', 'SET NULL');

        // 6. Update staff_ratings table
        $table = $wpdb->prefix . 'asmaa_staff_ratings';
        self::update_column_if_exists($table, 'staff_id', 'wp_user_id', 'BIGINT UNSIGNED NOT NULL');
        self::update_column_if_exists($table, 'customer_id', 'wc_customer_id', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'CASCADE');
        self::add_foreign_key_if_not_exists($table, 'wc_customer_id', $wpdb->users, 'ID', 'SET NULL');

        // 7. Update invoices table
        $table = $wpdb->prefix . 'asmaa_invoices';
        self::update_column_if_exists($table, 'customer_id', 'wc_customer_id', 'BIGINT UNSIGNED NOT NULL');
        
        // Add foreign key if it doesn't exist
        self::add_foreign_key_if_not_exists($table, 'wc_customer_id', $wpdb->users, 'ID', 'CASCADE');

        // 8. Update payments table
        $table = $wpdb->prefix . 'asmaa_payments';
        self::update_column_if_exists($table, 'customer_id', 'wc_customer_id', 'BIGINT UNSIGNED NOT NULL');
        self::update_column_if_exists($table, 'processed_by', 'wp_user_id', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist
        self::add_foreign_key_if_not_exists($table, 'wc_customer_id', $wpdb->users, 'ID', 'CASCADE');
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'SET NULL');

        // 9. Update order_items table
        $table = $wpdb->prefix . 'asmaa_order_items';
        self::update_column_if_exists($table, 'item_id', 'wc_product_id', 'BIGINT UNSIGNED NULL');
        self::update_column_if_exists($table, 'staff_id', 'wp_user_id', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist (only for products, not services)
        // Note: wc_product_id can be NULL for services, so we skip FK for now

        // 10. Update inventory_movements table
        $table = $wpdb->prefix . 'asmaa_inventory_movements';
        self::update_column_if_exists($table, 'product_id', 'wc_product_id', 'BIGINT UNSIGNED NOT NULL');
        self::update_column_if_exists($table, 'performed_by', 'wp_user_id', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist
        self::add_foreign_key_if_not_exists($table, 'wc_product_id', $wpdb->posts, 'ID', 'CASCADE');
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'SET NULL');

        // 11. Update orders table
        $table = $wpdb->prefix . 'asmaa_orders';
        self::update_column_if_exists($table, 'customer_id', 'wc_customer_id', 'BIGINT UNSIGNED NOT NULL');
        self::update_column_if_exists($table, 'staff_id', 'wp_user_id', 'BIGINT UNSIGNED NULL');
        
        // Add foreign keys if they don't exist
        self::add_foreign_key_if_not_exists($table, 'wc_customer_id', $wpdb->users, 'ID', 'CASCADE');
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'SET NULL');

        // 12. Update worker_calls table
        $table = $wpdb->prefix . 'asmaa_worker_calls';
        self::update_column_if_exists($table, 'staff_id', 'wp_user_id', 'BIGINT UNSIGNED NOT NULL');
        
        // Add foreign key if it doesn't exist
        self::add_foreign_key_if_not_exists($table, 'wp_user_id', $wpdb->users, 'ID', 'CASCADE');
    }

    /**
     * Update column name if old column exists
     */
    private static function update_column_if_exists(string $table, string $old_name, string $new_name, string $definition): void
    {
        global $wpdb;
        
        // Check if old column exists
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
        
        if (in_array($old_name, $columns) && !in_array($new_name, $columns)) {
            $wpdb->query("ALTER TABLE {$table} CHANGE COLUMN {$old_name} {$new_name} {$definition}");
        } elseif (!in_array($new_name, $columns)) {
            // If neither exists, add the new column
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$new_name} {$definition}");
        }
    }

    /**
     * Add foreign key if it doesn't exist
     */
    private static function add_foreign_key_if_not_exists(string $table, string $column, string $ref_table, string $ref_column, string $on_delete): void
    {
        global $wpdb;
        
        // Check if foreign key already exists
        $constraints = $wpdb->get_results("SHOW CREATE TABLE {$table}");
        if (empty($constraints)) {
            return;
        }
        
        $create_table = $constraints[0]->{'Create Table'};
        $fk_name = "fk_{$table}_{$column}";
        
        // Check if FK already exists
        if (strpos($create_table, $fk_name) !== false) {
            return;
        }
        
        // Try to add foreign key (may fail if data doesn't match)
        try {
            $wpdb->query("ALTER TABLE {$table} ADD CONSTRAINT {$fk_name} FOREIGN KEY ({$column}) REFERENCES {$ref_table}({$ref_column}) ON DELETE {$on_delete}");
        } catch (\Exception $e) {
            // Log error but don't fail migration
            error_log("Failed to add foreign key {$fk_name}: " . $e->getMessage());
        }
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_bookings';
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
        
        // Check if old column names still exist
        return in_array('customer_id', $columns) || in_array('staff_id', $columns);
    }
}


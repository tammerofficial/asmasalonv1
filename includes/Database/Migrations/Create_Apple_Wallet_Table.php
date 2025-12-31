<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Create_Apple_Wallet_Table
{
    /**
     * Create Apple Wallet passes table
     */
    public static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wc_customer_id BIGINT UNSIGNED NOT NULL,
            pass_type VARCHAR(50) NOT NULL DEFAULT 'loyalty',
            pass_type_identifier VARCHAR(255) NOT NULL,
            serial_number VARCHAR(100) NOT NULL UNIQUE,
            authentication_token VARCHAR(100) NOT NULL UNIQUE,
            pass_data JSON NOT NULL,
            qr_code_data TEXT NOT NULL,
            qr_code_url VARCHAR(500) NULL,
            last_updated DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_serial_number (serial_number),
            UNIQUE KEY idx_auth_token (authentication_token),
            KEY idx_wc_customer_id (wc_customer_id),
            KEY idx_pass_type (pass_type),
            KEY idx_customer_type (wc_customer_id, pass_type),
            KEY idx_last_updated (last_updated),
            FOREIGN KEY (wc_customer_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) ENGINE=InnoDB {$charset};";
        
        dbDelta($sql);
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
        
        if (!$exists) {
            return true;
        }
        
        // Check if pass_type column exists
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$table} LIKE 'pass_type'"
        );
        
        return empty($column_exists);
    }
    
    /**
     * Add pass_type column to existing table
     */
    public static function add_pass_type_column(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        
        // Check if column exists
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$table} LIKE 'pass_type'"
        );
        
        if (empty($column_exists)) {
            // Add pass_type column
            $wpdb->query(
                "ALTER TABLE {$table} 
                 ADD COLUMN pass_type VARCHAR(50) NOT NULL DEFAULT 'loyalty' AFTER wc_customer_id,
                 ADD KEY idx_pass_type (pass_type),
                 ADD KEY idx_customer_type (wc_customer_id, pass_type)"
            );
            
            // Update existing records to have pass_type = 'loyalty'
            $wpdb->query(
                "UPDATE {$table} SET pass_type = 'loyalty' WHERE pass_type IS NULL OR pass_type = ''"
            );
        }
    }
}


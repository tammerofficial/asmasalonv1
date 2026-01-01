<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Create_Extended_Data_Tables
{
    /**
     * Create extended data tables for salon-specific data
     * These tables store only additional data not available in WooCommerce/WordPress
     */
    public static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        // 1. Customer Extended Data
        $table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            wc_customer_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            total_visits INT UNSIGNED NOT NULL DEFAULT 0,
            total_spent DECIMAL(10,3) NOT NULL DEFAULT 0,
            loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
            last_visit_at DATETIME NULL,
            preferred_staff_id BIGINT UNSIGNED NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (wc_customer_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (preferred_staff_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
            KEY idx_loyalty_points (loyalty_points),
            KEY idx_last_visit_at (last_visit_at),
            KEY idx_total_spent (total_spent),
            KEY idx_preferred_staff (preferred_staff_id),
            KEY idx_loyalty_active (loyalty_points, last_visit_at),
            KEY idx_customer_stats (total_visits, total_spent, loyalty_points)
        ) ENGINE=InnoDB {$charset};";
        dbDelta($sql);

        // 2. Staff Extended Data
        $table = $wpdb->prefix . 'asmaa_staff_extended_data';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            wp_user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            position VARCHAR(100) NULL,
            chair_number INT UNSIGNED NULL,
            hire_date DATE NULL,
            salary DECIMAL(10,3) NULL,
            commission_rate DECIMAL(5,2) NULL,
            photo VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            rating DECIMAL(3,2) NOT NULL DEFAULT 0,
            total_ratings INT UNSIGNED NOT NULL DEFAULT 0,
            total_services INT UNSIGNED NOT NULL DEFAULT 0,
            total_revenue DECIMAL(10,3) NOT NULL DEFAULT 0,
            service_ids JSON NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (wp_user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            KEY idx_is_active (is_active),
            KEY idx_rating (rating),
            KEY idx_position (position),
            KEY idx_chair_number (chair_number),
            KEY idx_total_revenue (total_revenue),
            KEY idx_total_services (total_services),
            KEY idx_active_rating (is_active, rating),
            KEY idx_staff_performance (is_active, total_revenue, rating),
            KEY idx_commission_stats (is_active, commission_rate, total_revenue)
        ) ENGINE=InnoDB {$charset};";
        dbDelta($sql);
        
        // Add chair_number column if table exists but column doesn't
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'chair_number'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN chair_number INT UNSIGNED NULL AFTER position");
            $wpdb->query("ALTER TABLE {$table} ADD KEY idx_chair_number (chair_number)");
        }

        // 3. Product Extended Data
        $table = $wpdb->prefix . 'asmaa_product_extended_data';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            wc_product_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            purchase_price DECIMAL(10,3) NOT NULL DEFAULT 0,
            min_stock_level INT NOT NULL DEFAULT 0,
            barcode VARCHAR(100) NULL,
            brand VARCHAR(100) NULL,
            unit VARCHAR(50) NULL,
            service_id BIGINT UNSIGNED NULL,
            is_service TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (wc_product_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE,
            KEY idx_barcode (barcode),
            KEY idx_brand (brand),
            KEY idx_min_stock_level (min_stock_level),
            KEY idx_service_id (service_id),
            KEY idx_brand_barcode (brand, barcode)
        ) ENGINE=InnoDB {$charset};";
        dbDelta($sql);
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
        
        return !$exists;
    }
}


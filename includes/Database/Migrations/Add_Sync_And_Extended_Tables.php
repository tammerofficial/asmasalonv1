<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Add_Sync_And_Extended_Tables
{
    /**
     * Run migration
     */
    public static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $charset_collate = $wpdb->get_charset_collate();

        // 1. جدول تتبع المزامنة (Idempotency)
        $table_sync = $wpdb->prefix . 'asmaa_pending_orders_sync';
        $sql_sync = "CREATE TABLE {$table_sync} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_side_id VARCHAR(100) NOT NULL,
            wc_order_id BIGINT UNSIGNED NOT NULL,
            synced_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_client_side_id (client_side_id),
            KEY idx_wc_order_id (wc_order_id),
            KEY idx_synced_at (synced_at)
        ) {$charset_collate};";
        
        dbDelta($sql_sync);

        // 2. تحديث جدول الطابور لإضافة رابط الحجز (إذا لم يكن موجوداً)
        $table_queue = $wpdb->prefix . 'asmaa_queue_tickets';
        
        // التحقق من وجود العمود قبل إضافته
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'booking_id'",
            DB_NAME,
            $table_queue
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_queue} ADD COLUMN booking_id BIGINT UNSIGNED NULL AFTER wc_customer_id");
            $wpdb->query("ALTER TABLE {$table_queue} ADD INDEX idx_booking_id (booking_id)");
        }
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        global $wpdb;
        
        // التحقق من وجود جدول المزامنة
        $table_sync = $wpdb->prefix . 'asmaa_pending_orders_sync';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables 
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_sync
        ));
        
        if (!$exists) {
            return true;
        }
        
        // التحقق من وجود عمود booking_id في جدول الطابور
        $table_queue = $wpdb->prefix . 'asmaa_queue_tickets';
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'booking_id'",
            DB_NAME,
            $table_queue
        ));
        
        return empty($column_exists);
    }
}


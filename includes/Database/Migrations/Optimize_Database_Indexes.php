<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Optimize_Database_Indexes
{
    /**
     * Add optimized indexes to all tables for high performance
     */
    public static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Bookings table indexes
        $table = $wpdb->prefix . 'asmaa_bookings';
        self::add_index_if_not_exists($table, 'idx_customer_status', 'wc_customer_id, status');
        self::add_index_if_not_exists($table, 'idx_staff_status', 'wp_user_id, status');
        self::add_index_if_not_exists($table, 'idx_date_status', 'booking_date, status');
        self::add_index_if_not_exists($table, 'idx_service_date', 'service_id, booking_date');
        self::add_index_if_not_exists($table, 'idx_completed_at', 'completed_at');
        self::add_index_if_not_exists($table, 'idx_confirmed_at', 'confirmed_at');
        self::add_index_if_not_exists($table, 'idx_customer_date_status', 'wc_customer_id, booking_date, status');
        self::add_index_if_not_exists($table, 'idx_staff_date_status', 'wp_user_id, booking_date, status');

        // 2. Queue tickets table indexes
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        self::add_index_if_not_exists($table, 'idx_customer_status', 'wc_customer_id, status');
        self::add_index_if_not_exists($table, 'idx_staff_status', 'wp_user_id, status');
        self::add_index_if_not_exists($table, 'idx_service_status', 'service_id, status');
        self::add_index_if_not_exists($table, 'idx_created_status', 'created_at, status');
        self::add_index_if_not_exists($table, 'idx_called_at', 'called_at');
        self::add_index_if_not_exists($table, 'idx_completed_at', 'completed_at');
        self::add_index_if_not_exists($table, 'idx_today_status', 'DATE(created_at), status');
        self::add_index_if_not_exists($table, 'idx_customer_today', 'wc_customer_id, DATE(created_at)');

        // 3. Loyalty transactions table indexes
        $table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        self::add_index_if_not_exists($table, 'idx_customer_type', 'wc_customer_id, type');
        self::add_index_if_not_exists($table, 'idx_customer_created', 'wc_customer_id, created_at');
        self::add_index_if_not_exists($table, 'idx_type_created', 'type, created_at');
        self::add_index_if_not_exists($table, 'idx_reference', 'reference_type, reference_id');
        self::add_index_if_not_exists($table, 'idx_customer_type_date', 'wc_customer_id, type, created_at');

        // 4. Customer memberships table indexes
        $table = $wpdb->prefix . 'asmaa_customer_memberships';
        self::add_index_if_not_exists($table, 'idx_customer_status', 'wc_customer_id, status');
        self::add_index_if_not_exists($table, 'idx_plan_status', 'membership_plan_id, status');
        self::add_index_if_not_exists($table, 'idx_end_date', 'end_date');
        self::add_index_if_not_exists($table, 'idx_start_date', 'start_date');
        self::add_index_if_not_exists($table, 'idx_status_end_date', 'status, end_date');
        self::add_index_if_not_exists($table, 'idx_customer_active', 'wc_customer_id, status, end_date');
        self::add_index_if_not_exists($table, 'idx_expiring_soon', 'status, end_date');

        // 5. Staff commissions table indexes
        $table = $wpdb->prefix . 'asmaa_staff_commissions';
        self::add_index_if_not_exists($table, 'idx_staff_status', 'wp_user_id, status');
        self::add_index_if_not_exists($table, 'idx_order_status', 'order_id, status');
        self::add_index_if_not_exists($table, 'idx_booking_status', 'booking_id, status');
        self::add_index_if_not_exists($table, 'idx_status_created', 'status, created_at');
        self::add_index_if_not_exists($table, 'idx_approved_at', 'approved_at');
        self::add_index_if_not_exists($table, 'idx_paid_at', 'paid_at');
        self::add_index_if_not_exists($table, 'idx_staff_status_date', 'wp_user_id, status, created_at');
        self::add_index_if_not_exists($table, 'idx_pending_approval', 'status, created_at');

        // 6. Invoices table indexes
        $table = $wpdb->prefix . 'asmaa_invoices';
        self::add_index_if_not_exists($table, 'idx_customer_status', 'wc_customer_id, status');
        self::add_index_if_not_exists($table, 'idx_order_status', 'wc_order_id, status');
        self::add_index_if_not_exists($table, 'idx_status_due_date', 'status, due_date');
        self::add_index_if_not_exists($table, 'idx_issue_date', 'issue_date');
        self::add_index_if_not_exists($table, 'idx_due_date', 'due_date');
        self::add_index_if_not_exists($table, 'idx_paid_amount', 'paid_amount');
        self::add_index_if_not_exists($table, 'idx_customer_status_date', 'wc_customer_id, status, issue_date');
        self::add_index_if_not_exists($table, 'idx_unpaid_due', 'status, due_date');

        // 7. Payments table indexes
        $table = $wpdb->prefix . 'asmaa_payments';
        self::add_index_if_not_exists($table, 'idx_customer_status', 'wc_customer_id, status');
        self::add_index_if_not_exists($table, 'idx_invoice_status', 'invoice_id, status');
        self::add_index_if_not_exists($table, 'idx_order_status', 'wc_order_id, status');
        self::add_index_if_not_exists($table, 'idx_payment_method', 'payment_method');
        self::add_index_if_not_exists($table, 'idx_payment_date', 'payment_date');
        self::add_index_if_not_exists($table, 'idx_status_date', 'status, payment_date');
        self::add_index_if_not_exists($table, 'idx_customer_date', 'wc_customer_id, payment_date');
        self::add_index_if_not_exists($table, 'idx_method_date', 'payment_method, payment_date');

        // 8. Services table indexes
        $table = $wpdb->prefix . 'asmaa_services';
        self::add_index_if_not_exists($table, 'idx_category_active', 'category, is_active');
        self::add_index_if_not_exists($table, 'idx_price', 'price');
        self::add_index_if_not_exists($table, 'idx_duration', 'duration');
        self::add_index_if_not_exists($table, 'idx_active_category', 'is_active, category');
        self::add_index_if_not_exists($table, 'idx_active_category_price', 'is_active, category, price');

        // 9. Inventory movements table indexes
        $table = $wpdb->prefix . 'asmaa_inventory_movements';
        self::add_index_if_not_exists($table, 'idx_product_type', 'wc_product_id, type');
        self::add_index_if_not_exists($table, 'idx_type_date', 'type, movement_date');
        self::add_index_if_not_exists($table, 'idx_reference', 'reference_type, reference_id');
        self::add_index_if_not_exists($table, 'idx_performed_by', 'performed_by');
        self::add_index_if_not_exists($table, 'idx_movement_date', 'movement_date');
        self::add_index_if_not_exists($table, 'idx_product_type_date', 'wc_product_id, type, movement_date');

        // 10. Order items table indexes
        $table = $wpdb->prefix . 'asmaa_order_items';
        self::add_index_if_not_exists($table, 'idx_order_type', 'order_id, item_type');
        self::add_index_if_not_exists($table, 'idx_product_order', 'wc_product_id, order_id');
        self::add_index_if_not_exists($table, 'idx_staff_order', 'wp_user_id, order_id');

        // 11. Invoice items table indexes
        $table = $wpdb->prefix . 'asmaa_invoice_items';
        self::add_index_if_not_exists($table, 'idx_invoice_total', 'invoice_id, total');

        // 12. Membership service usage table indexes
        $table = $wpdb->prefix . 'asmaa_membership_service_usage';
        self::add_index_if_not_exists($table, 'idx_membership_service', 'customer_membership_id, service_id');
        self::add_index_if_not_exists($table, 'idx_membership_date', 'customer_membership_id, used_at');
        self::add_index_if_not_exists($table, 'idx_service_date', 'service_id, used_at');
    }

    /**
     * Add index if it doesn't exist
     */
    private static function add_index_if_not_exists(string $table, string $index_name, string $columns): void
    {
        global $wpdb;
        
        // Check if index exists
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = '{$index_name}'");
        
        if (empty($indexes)) {
            // For DATE() function in index, we need to use a different approach
            if (strpos($columns, 'DATE(') !== false) {
                // Skip indexes with DATE() function as they require generated columns
                return;
            }
            
            $wpdb->query("ALTER TABLE {$table} ADD KEY {$index_name} ({$columns})");
        }
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_bookings';
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = 'idx_customer_status'");
        
        return empty($indexes);
    }
}


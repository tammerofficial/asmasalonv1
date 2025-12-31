<?php

namespace AsmaaSalon\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Schema
{
    public static function create_core_tables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        // 1. CUSTOMERS
        $table = $wpdb->prefix . 'asmaa_customers';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            email VARCHAR(255) NULL,
            address TEXT NULL,
            city VARCHAR(100) NULL,
            date_of_birth DATE NULL,
            gender VARCHAR(10) NULL,
            notes TEXT NULL,
            total_visits INT UNSIGNED NOT NULL DEFAULT 0,
            total_spent DECIMAL(10,3) NOT NULL DEFAULT 0,
            loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
            last_visit_at DATETIME NULL,
            preferred_staff_id BIGINT UNSIGNED NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            wc_customer_id BIGINT UNSIGNED NULL,
            wc_synced_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_phone (phone),
            KEY idx_name (name),
            KEY idx_email (email),
            KEY idx_is_active (is_active),
            KEY idx_last_visit_at (last_visit_at),
            KEY idx_wc_customer_id (wc_customer_id)
        ) {$charset};";
        dbDelta($sql);

        // 2. SERVICES
        $table = $wpdb->prefix . 'asmaa_services';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            name_ar VARCHAR(255) NULL,
            description TEXT NULL,
            price DECIMAL(10,3) NOT NULL DEFAULT 0,
            duration INT UNSIGNED NOT NULL DEFAULT 0,
            category VARCHAR(100) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            image VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_name (name),
            KEY idx_is_active (is_active),
            KEY idx_category (category)
        ) {$charset};";
        dbDelta($sql);

        // 3. STAFF
        $table = $wpdb->prefix . 'asmaa_staff';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NULL,
            email VARCHAR(255) NULL,
            position VARCHAR(100) NULL,
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
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_name (name),
            KEY idx_is_active (is_active),
            KEY idx_rating (rating)
        ) {$charset};";
        dbDelta($sql);

        // 4. STAFF_RATINGS
        $table = $wpdb->prefix . 'asmaa_staff_ratings';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            booking_id BIGINT UNSIGNED NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comment TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_staff_id (staff_id),
            KEY idx_customer_id (customer_id),
            KEY idx_booking_id (booking_id),
            KEY idx_rating (rating),
            KEY idx_created_at (created_at)
        ) {$charset};";
        dbDelta($sql);

        // 5. BOOKINGS
        $table = $wpdb->prefix . 'asmaa_bookings';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            staff_id BIGINT UNSIGNED NULL,
            service_id BIGINT UNSIGNED NOT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            end_time TIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            price DECIMAL(10,3) NOT NULL DEFAULT 0,
            discount DECIMAL(10,3) NOT NULL DEFAULT 0,
            final_price DECIMAL(10,3) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            reminder_sent TINYINT(1) NOT NULL DEFAULT 0,
            source VARCHAR(20) NULL,
            queue_ticket_id BIGINT UNSIGNED NULL,
            confirmed_at DATETIME NULL,
            completed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_customer_id (customer_id),
            KEY idx_staff_id (staff_id),
            KEY idx_service_id (service_id),
            KEY idx_booking_date (booking_date),
            KEY idx_status (status),
            KEY idx_created_at (created_at),
            KEY idx_booking_date_time (booking_date, booking_time),
            KEY idx_staff_date (staff_id, booking_date),
            KEY idx_queue_ticket_id (queue_ticket_id)
        ) {$charset};";
        dbDelta($sql);

        // 6. QUEUE_TICKETS
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_number VARCHAR(50) NOT NULL,
            wc_customer_id BIGINT UNSIGNED NULL,
            booking_id BIGINT UNSIGNED NULL,
            service_id BIGINT UNSIGNED NULL,
            wp_user_id BIGINT UNSIGNED NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'waiting',
            notes TEXT NULL,
            check_in_at DATETIME NULL,
            called_at DATETIME NULL,
            serving_started_at DATETIME NULL,
            completed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_ticket_number (ticket_number),
            KEY idx_wc_customer_id (wc_customer_id),
            KEY idx_booking_id (booking_id),
            KEY idx_service_id (service_id),
            KEY idx_wp_user_id (wp_user_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) {$charset};";
        dbDelta($sql);

        // 7. WORKER_CALLS
        $table = $wpdb->prefix . 'asmaa_worker_calls';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id BIGINT UNSIGNED NOT NULL,
            ticket_id BIGINT UNSIGNED NULL,
            customer_name VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            called_at DATETIME NULL,
            accepted_at DATETIME NULL,
            completed_at DATETIME NULL,
            postponed_at DATETIME NULL,
            sms_sent TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_staff_id (staff_id),
            KEY idx_ticket_id (ticket_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) {$charset};";
        dbDelta($sql);

        // 8. ORDERS
        $table = $wpdb->prefix . 'asmaa_orders';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            staff_id BIGINT UNSIGNED NULL,
            booking_id BIGINT UNSIGNED NULL,
            order_number VARCHAR(50) NOT NULL,
            subtotal DECIMAL(10,3) NOT NULL DEFAULT 0,
            discount DECIMAL(10,3) NOT NULL DEFAULT 0,
            tax DECIMAL(10,3) NOT NULL DEFAULT 0,
            total DECIMAL(10,3) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
            payment_method VARCHAR(50) NULL,
            notes TEXT NULL,
            wc_order_id BIGINT UNSIGNED NULL,
            wc_synced_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_order_number (order_number),
            KEY idx_customer_id (customer_id),
            KEY idx_staff_id (staff_id),
            KEY idx_booking_id (booking_id),
            KEY idx_status (status),
            KEY idx_payment_status (payment_status),
            KEY idx_created_at (created_at),
            KEY idx_wc_order_id (wc_order_id)
        ) {$charset};";
        dbDelta($sql);

        // 9. ORDER_ITEMS
        $table = $wpdb->prefix . 'asmaa_order_items';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            item_type VARCHAR(20) NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,3) NOT NULL DEFAULT 0,
            discount DECIMAL(10,3) NOT NULL DEFAULT 0,
            total DECIMAL(10,3) NOT NULL DEFAULT 0,
            staff_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order_id (order_id),
            KEY idx_staff_id (staff_id),
            KEY idx_item_type_id (item_type, item_id)
        ) {$charset};";
        dbDelta($sql);

        // 10. INVOICES
        $table = $wpdb->prefix . 'asmaa_invoices';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            invoice_number VARCHAR(50) NOT NULL,
            issue_date DATE NOT NULL,
            due_date DATE NULL,
            subtotal DECIMAL(10,3) NOT NULL DEFAULT 0,
            discount DECIMAL(10,3) NOT NULL DEFAULT 0,
            tax DECIMAL(10,3) NOT NULL DEFAULT 0,
            total DECIMAL(10,3) NOT NULL DEFAULT 0,
            paid_amount DECIMAL(10,3) NOT NULL DEFAULT 0,
            due_amount DECIMAL(10,3) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            notes TEXT NULL,
            wc_order_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_invoice_number (invoice_number),
            KEY idx_order_id (order_id),
            KEY idx_customer_id (customer_id),
            KEY idx_status (status),
            KEY idx_issue_date (issue_date),
            KEY idx_due_date (due_date),
            KEY idx_wc_order_id (wc_order_id)
        ) {$charset};";
        dbDelta($sql);

        // 11. INVOICE_ITEMS
        $table = $wpdb->prefix . 'asmaa_invoice_items';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id BIGINT UNSIGNED NOT NULL,
            description TEXT NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,3) NOT NULL DEFAULT 0,
            total DECIMAL(10,3) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_invoice_id (invoice_id)
        ) {$charset};";
        dbDelta($sql);

        // 12. PAYMENTS
        $table = $wpdb->prefix . 'asmaa_payments';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            payment_number VARCHAR(50) NOT NULL,
            amount DECIMAL(10,3) NOT NULL DEFAULT 0,
            payment_method VARCHAR(50) NOT NULL,
            reference_number VARCHAR(100) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'completed',
            notes TEXT NULL,
            payment_date DATETIME NOT NULL,
            processed_by BIGINT UNSIGNED NULL,
            wc_payment_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_payment_number (payment_number),
            KEY idx_invoice_id (invoice_id),
            KEY idx_customer_id (customer_id),
            KEY idx_order_id (order_id),
            KEY idx_processed_by (processed_by),
            KEY idx_status (status),
            KEY idx_payment_method (payment_method),
            KEY idx_payment_date (payment_date),
            KEY idx_wc_payment_id (wc_payment_id)
        ) {$charset};";
        dbDelta($sql);

        // 13. PRODUCTS
        $table = $wpdb->prefix . 'asmaa_products';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            name_ar VARCHAR(255) NULL,
            sku VARCHAR(100) NOT NULL,
            barcode VARCHAR(100) NULL,
            description TEXT NULL,
            category VARCHAR(100) NULL,
            brand VARCHAR(100) NULL,
            purchase_price DECIMAL(10,3) NOT NULL DEFAULT 0,
            selling_price DECIMAL(10,3) NOT NULL DEFAULT 0,
            stock_quantity INT NOT NULL DEFAULT 0,
            min_stock_level INT NOT NULL DEFAULT 0,
            unit VARCHAR(50) NULL,
            image VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            wc_product_id BIGINT UNSIGNED NULL,
            wc_synced_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_sku (sku),
            KEY idx_barcode (barcode),
            KEY idx_name (name),
            KEY idx_category (category),
            KEY idx_is_active (is_active),
            KEY idx_stock_quantity (stock_quantity),
            KEY idx_wc_product_id (wc_product_id)
        ) {$charset};";
        dbDelta($sql);

        // 14. INVENTORY_MOVEMENTS
        $table = $wpdb->prefix . 'asmaa_inventory_movements';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(20) NOT NULL,
            quantity INT NOT NULL DEFAULT 0,
            before_quantity INT NOT NULL DEFAULT 0,
            after_quantity INT NOT NULL DEFAULT 0,
            unit_cost DECIMAL(10,3) NULL,
            total_cost DECIMAL(10,3) NULL,
            reference_type VARCHAR(50) NULL,
            reference_id BIGINT UNSIGNED NULL,
            notes TEXT NULL,
            performed_by BIGINT UNSIGNED NULL,
            movement_date DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_product_id (product_id),
            KEY idx_performed_by (performed_by),
            KEY idx_type (type),
            KEY idx_movement_date (movement_date),
            KEY idx_created_at (created_at)
        ) {$charset};";
        dbDelta($sql);

        // 15. MEMBERSHIP_PLANS
        $table = $wpdb->prefix . 'asmaa_membership_plans';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            name_ar VARCHAR(255) NULL,
            description TEXT NULL,
            price DECIMAL(10,3) NOT NULL DEFAULT 0,
            duration_months INT UNSIGNED NOT NULL DEFAULT 1,
            discount_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
            free_services_count INT UNSIGNED NOT NULL DEFAULT 0,
            priority_booking TINYINT(1) NOT NULL DEFAULT 0,
            points_multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_is_active (is_active)
        ) {$charset};";
        dbDelta($sql);

        // 16. CUSTOMER_MEMBERSHIPS
        $table = $wpdb->prefix . 'asmaa_customer_memberships';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            membership_plan_id BIGINT UNSIGNED NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            auto_renew TINYINT(1) NOT NULL DEFAULT 0,
            services_used INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_customer_id (customer_id),
            KEY idx_membership_plan_id (membership_plan_id),
            KEY idx_status (status),
            KEY idx_end_date (end_date)
        ) {$charset};";
        dbDelta($sql);

        // 17. MEMBERSHIP_SERVICE_USAGE
        $table = $wpdb->prefix . 'asmaa_membership_service_usage';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_membership_id BIGINT UNSIGNED NOT NULL,
            service_id BIGINT UNSIGNED NOT NULL,
            booking_id BIGINT UNSIGNED NULL,
            used_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_customer_membership_id (customer_membership_id),
            KEY idx_service_id (service_id),
            KEY idx_booking_id (booking_id)
        ) {$charset};";
        dbDelta($sql);

        // 18. MEMBERSHIP_EXTENSIONS
        $table = $wpdb->prefix . 'asmaa_membership_extensions';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_membership_id BIGINT UNSIGNED NOT NULL,
            extended_by_months INT UNSIGNED NOT NULL,
            amount_paid DECIMAL(10,3) NOT NULL DEFAULT 0,
            extended_at DATETIME NOT NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_customer_membership_id (customer_membership_id)
        ) {$charset};";
        dbDelta($sql);

        // 19. LOYALTY_TRANSACTIONS
        $table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(20) NOT NULL,
            points INT NOT NULL DEFAULT 0,
            balance_before INT NOT NULL DEFAULT 0,
            balance_after INT NOT NULL DEFAULT 0,
            reference_type VARCHAR(50) NULL,
            reference_id BIGINT UNSIGNED NULL,
            description TEXT NULL,
            expires_at DATETIME NULL,
            performed_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_customer_id (customer_id),
            KEY idx_performed_by (performed_by),
            KEY idx_type (type),
            KEY idx_created_at (created_at),
            KEY idx_expires_at (expires_at)
        ) {$charset};";
        dbDelta($sql);

        // 20. STAFF_COMMISSIONS
        $table = $wpdb->prefix . 'asmaa_staff_commissions';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            order_item_id BIGINT UNSIGNED NULL,
            booking_id BIGINT UNSIGNED NULL,
            base_amount DECIMAL(10,3) NOT NULL DEFAULT 0,
            commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            commission_amount DECIMAL(10,3) NOT NULL DEFAULT 0,
            rating_bonus DECIMAL(10,3) NOT NULL DEFAULT 0,
            final_amount DECIMAL(10,3) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            approved_by BIGINT UNSIGNED NULL,
            approved_at DATETIME NULL,
            paid_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_staff_id (staff_id),
            KEY idx_order_id (order_id),
            KEY idx_booking_id (booking_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) {$charset};";
        dbDelta($sql);

        // 21. COMMISSION_SETTINGS
        $table = $wpdb->prefix . 'asmaa_commission_settings';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            service_commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            product_commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            reception_share_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
            rating_bonus_enabled TINYINT(1) NOT NULL DEFAULT 1,
            rating_bonus_5_star DECIMAL(10,3) NOT NULL DEFAULT 0,
            rating_bonus_4_star DECIMAL(10,3) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset};";
        dbDelta($sql);

        // 22. POS_SESSIONS
        $table = $wpdb->prefix . 'asmaa_pos_sessions';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            opening_balance DECIMAL(10,3) NOT NULL DEFAULT 0,
            closing_balance DECIMAL(10,3) NULL,
            expected_cash DECIMAL(10,3) NULL,
            actual_cash DECIMAL(10,3) NULL,
            difference DECIMAL(10,3) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            opened_at DATETIME NOT NULL,
            closed_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_opened_at (opened_at),
            KEY idx_closed_at (closed_at)
        ) {$charset};";
        dbDelta($sql);

        // 23. ACTIVITY_LOG
        $table = $wpdb->prefix . 'asmaa_activity_log';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            log_name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            subject_type VARCHAR(255) NULL,
            subject_id BIGINT UNSIGNED NULL,
            causer_type VARCHAR(255) NULL,
            causer_id BIGINT UNSIGNED NULL,
            properties TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_log_name (log_name),
            KEY idx_subject (subject_type, subject_id),
            KEY idx_causer (causer_type, causer_id),
            KEY idx_created_at (created_at)
        ) {$charset};";
        dbDelta($sql);

        // 24. NOTIFICATIONS
        $table = $wpdb->prefix . 'asmaa_notifications';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(255) NOT NULL,
            notifiable_type VARCHAR(255) NOT NULL,
            notifiable_id BIGINT UNSIGNED NOT NULL,
            data TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            channel VARCHAR(50) NULL,
            sent_at DATETIME NULL,
            read_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_notifiable (notifiable_type, notifiable_id),
            KEY idx_type (type),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) {$charset};";
        dbDelta($sql);

        // 25. BOOKING_SETTINGS (for structured settings storage)
        $table = $wpdb->prefix . 'asmaa_booking_settings';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            section VARCHAR(100) NOT NULL,
            setting_key VARCHAR(255) NOT NULL,
            setting_value LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_section_key (section, setting_key),
            KEY idx_section (section)
        ) {$charset};";
        dbDelta($sql);

        // 26. QUEUE (alias table for backward compatibility, if needed)
        // Note: Queue functionality uses asmaa_queue_tickets, but we add this for compatibility
        $table = $wpdb->prefix . 'asmaa_queue';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            service_id BIGINT UNSIGNED NULL,
            staff_id BIGINT UNSIGNED NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'waiting',
            position INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ticket_id (ticket_id),
            KEY idx_customer_id (customer_id),
            KEY idx_status (status),
            KEY idx_position (position)
        ) {$charset};";
        dbDelta($sql);

        // 27. MEMBERSHIPS (alias table for backward compatibility)
        // Note: Main table is asmaa_customer_memberships, but we add this for compatibility
        $table = $wpdb->prefix . 'asmaa_memberships';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            plan_id BIGINT UNSIGNED NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            auto_renew TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_customer_id (customer_id),
            KEY idx_plan_id (plan_id),
            KEY idx_status (status),
            KEY idx_end_date (end_date)
        ) {$charset};";
        dbDelta($sql);

        // 28. COMMISSIONS (alias table for backward compatibility)
        // Note: Main table is asmaa_staff_commissions, but we add this for compatibility
        $table = $wpdb->prefix . 'asmaa_commissions';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            booking_id BIGINT UNSIGNED NULL,
            amount DECIMAL(10,3) NOT NULL DEFAULT 0,
            rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            approved_at DATETIME NULL,
            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_staff_id (staff_id),
            KEY idx_order_id (order_id),
            KEY idx_booking_id (booking_id),
            KEY idx_status (status)
        ) {$charset};";
        dbDelta($sql);

        // 29. WOOCOMMERCE SYNC LOG
        $table = $wpdb->prefix . 'asmaa_wc_sync_log';
        $sql = "CREATE TABLE {$table} (
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
}

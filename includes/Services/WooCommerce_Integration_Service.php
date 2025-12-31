<?php

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class WooCommerce_Integration_Service
{
    /**
     * Check if WooCommerce is active
     */
    public static function is_woocommerce_active(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_get_product');
    }

    /**
     * Get WooCommerce integration settings
     * Always enabled by default for automatic bidirectional sync
     */
    public static function get_settings(): array
    {
        // Always return enabled settings for automatic sync
        return [
            'woocommerce_enabled' => true,
            'sync_products' => true,
            'sync_orders' => true,
            'sync_customers' => true,
            'sync_direction' => 'bidirectional', // Always bidirectional
            'auto_sync' => true,
            'sync_on_create' => true,
            'sync_on_update' => true,
        ];
    }

    /**
     * Update WooCommerce integration settings
     */
    public static function update_settings(array $settings): bool
    {
        $current = self::get_settings();
        $updated = wp_parse_args($settings, $current);
        return update_option('asmaa_salon_wc_settings', $updated);
    }

    /**
     * Check if sync is enabled for entity type
     * Always enabled if WooCommerce is active
     */
    public static function is_sync_enabled(string $entity_type): bool
    {
        // Always enable sync if WooCommerce is active
        if (!self::is_woocommerce_active()) {
            return false;
        }

        // All entity types are enabled by default
        return in_array($entity_type, ['product', 'order', 'customer', 'invoice', 'payment']);
    }

    /**
     * Update WooCommerce product meta from extended data
     * Note: Since we now use WooCommerce products directly, this method updates WooCommerce product
     * with data from extended_data table (e.g. barcode, min_stock_level)
     */
    public static function update_wc_product_meta(int $wc_product_id): ?int
    {
        if (!self::is_sync_enabled('product')) {
            return null;
        }

        // Prevent infinite loop
        if (did_action('asmaa_salon_syncing_product')) {
            return null;
        }
        do_action('asmaa_salon_syncing_product');

        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_product_extended_data';
        $extended = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_product_id = %d", $wc_product_id));

        if (!$extended) {
            // No extended data, nothing to sync
            return $wc_product_id;
        }

        $wc_product = wc_get_product($wc_product_id);
        if (!$wc_product) {
            return null;
        }

        // Update WooCommerce product with extended data
        if ($extended->min_stock_level) {
            $wc_product->set_low_stock_amount((int) $extended->min_stock_level);
        }

        // Save barcode as post meta
        if ($extended->barcode) {
            update_post_meta($wc_product_id, '_barcode', $extended->barcode);
        }

        // Save product
        $wc_product->save();

        return $wc_product_id;
    }



    /**
     * Sync customer from Asmaa Salon to WooCommerce
     */
    public static function sync_customer_to_wc(int $wc_customer_id): ?int
    {
        // Now customers are already WordPress users (WooCommerce customers)
        // This function is kept for backward compatibility but just returns the ID
        if (!self::is_sync_enabled('customer')) {
            return null;
        }

        // Prevent infinite loop
        if (did_action('asmaa_salon_syncing_customer')) {
            return null;
        }
        do_action('asmaa_salon_syncing_customer');

        // Verify customer exists
        $user = get_user_by('ID', $wc_customer_id);
        if (!$user) {
            return null;
        }

        // Customer is already a WooCommerce customer (WordPress user)
        $wc_customer = new \WC_Customer($wc_customer_id);
        if (!$wc_customer || !$wc_customer->get_id()) {
            return null;
        }

        // Get extended data and update WooCommerce customer if needed
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $wc_customer_id)
        );

        if ($extended) {
            // Update WooCommerce customer meta if needed
            // (Most data is already in WordPress user)
        }

        self::log_sync('customer', $wc_customer_id, $wc_customer_id, 'to_wc', 'success');
        return $wc_customer_id;
    }


    /**
     * Get or create customer from WooCommerce customer ID
     */
    protected static function get_or_create_customer_from_wc(int $wc_customer_id): int
    {
        if ($wc_customer_id <= 0) {
            return 0;
        }

        // Verify customer exists
        $user = get_user_by('ID', $wc_customer_id);
        if (!$user) {
            return 0;
        }

        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';

        $existing = $wpdb->get_var($wpdb->prepare("SELECT wc_customer_id FROM {$extended_table} WHERE wc_customer_id = %d", $wc_customer_id));

        if ($existing) {
            return (int) $wc_customer_id;
        }

        // Create extended data if doesn't exist
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $wpdb->insert($extended_table, [
            'wc_customer_id' => $wc_customer_id,
            'total_visits' => 0,
            'total_spent' => 0,
            'loyalty_points' => 0,
        ]);
        return (int) $wc_customer_id;
    }

    /**
     * Sync payment to WooCommerce
     */
    public static function sync_payment_to_wc(int $asmaa_payment_id, ?int $wc_order_id = null): bool
    {
        if (!self::is_woocommerce_active()) {
            return false;
        }

        // Prevent infinite loop
        if (did_action('asmaa_salon_syncing_payment')) {
            return false;
        }
        do_action('asmaa_salon_syncing_payment');

        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_payments';
        $payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $asmaa_payment_id));

        if (!$payment) {
            return false;
        }

        // Get WC order ID from payment's order_id if not provided
        // Note: order_id in asmaa_payments now stores wc_order_id directly
        if (!$wc_order_id && $payment->order_id) {
            $wc_order_id = (int) $payment->order_id;
        }

        // If still no WC order ID, try to get from invoice
        if (!$wc_order_id && $payment->invoice_id) {
            $invoices_table = $wpdb->prefix . 'asmaa_invoices';
            $invoice = $wpdb->get_row($wpdb->prepare("SELECT order_id FROM {$invoices_table} WHERE id = %d", $payment->invoice_id));
            if ($invoice && $invoice->order_id) {
                // order_id in asmaa_invoices now stores wc_order_id directly
                $wc_order_id = (int) $invoice->order_id;
            }
        }

        if (!$wc_order_id) {
            self::log_sync('payment', $asmaa_payment_id, null, 'to_wc', 'failed', 'No WooCommerce order ID found');
            return false;
        }

        $wc_order = wc_get_order($wc_order_id);
        if (!$wc_order) {
            self::log_sync('payment', $asmaa_payment_id, $wc_order_id, 'to_wc', 'failed', 'WooCommerce order not found');
            return false;
        }

        // Update order payment status
        if ($payment->status === 'completed') {
            $wc_order->payment_complete($payment->payment_number);
        }

        // Update payment method
        if (!empty($payment->payment_method)) {
            $wc_order->set_payment_method($payment->payment_method);
            $wc_order->set_payment_method_title($payment->payment_method);
        }

        // Add payment note
        $wc_order->add_order_note(sprintf(
            __('Payment %s: %s KWD via %s', 'asmaa-salon'),
            $payment->payment_number,
            number_format($payment->amount, 3),
            $payment->payment_method
        ));

        $wc_order->save();

        // Update Asmaa payment with WC order ID (we use order ID as payment reference)
        $wpdb->update(
            $table,
            ['wc_payment_id' => $wc_order_id],
            ['id' => $asmaa_payment_id]
        );

        self::log_sync('payment', $asmaa_payment_id, $wc_order_id, 'to_wc', 'success');
        return true;
    }

    /**
     * Sync invoice to WooCommerce (via order)
     * Always sync if WooCommerce is active
     */
    public static function sync_invoice_to_wc(int $asmaa_invoice_id): ?int
    {
        if (!self::is_woocommerce_active()) {
            return null;
        }

        // Prevent infinite loop
        if (did_action('asmaa_salon_syncing_invoice')) {
            return null;
        }
        do_action('asmaa_salon_syncing_invoice');

        global $wpdb;
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$invoices_table} WHERE id = %d AND deleted_at IS NULL", $asmaa_invoice_id));

        if (!$invoice) {
            return null;
        }

        // Invoices are linked to orders - sync via order
        if ($invoice->order_id) {
            $orders_table = $wpdb->prefix . 'asmaa_orders';
            $order = $wpdb->get_row($wpdb->prepare("SELECT wc_order_id FROM {$orders_table} WHERE id = %d", $invoice->order_id));
            
            if ($order && $order->wc_order_id) {
                $wc_order = wc_get_order($order->wc_order_id);
                if ($wc_order) {
                    // Update order with invoice information
                    $wc_order->add_order_note(sprintf(
                        __('Invoice %s: %s KWD (Status: %s)', 'asmaa-salon'),
                        $invoice->invoice_number,
                        number_format($invoice->total, 3),
                        $invoice->status
                    ));

                    // Update order meta with invoice ID
                    $wc_order->update_meta_data('_asmaa_invoice_id', $asmaa_invoice_id);
                    $wc_order->update_meta_data('_asmaa_invoice_number', $invoice->invoice_number);
                    $wc_order->save();

                    // Update invoice with WC order ID
                    $wpdb->update(
                        $invoices_table,
                        ['wc_order_id' => $order->wc_order_id],
                        ['id' => $asmaa_invoice_id]
                    );

                    self::log_sync('invoice', $asmaa_invoice_id, $order->wc_order_id, 'to_wc', 'success');
                    return $order->wc_order_id;
                }
            }
        }

        self::log_sync('invoice', $asmaa_invoice_id, null, 'to_wc', 'failed', 'No associated order found');
        return null;
    }

    /**
     * Sync payment from WooCommerce order payment status
     */
    public static function sync_payment_from_wc(int $wc_order_id): ?int
    {
        if (!self::is_woocommerce_active()) {
            return null;
        }

        // Prevent infinite loop
        if (did_action('asmaa_salon_syncing_payment')) {
            return null;
        }
        do_action('asmaa_salon_syncing_payment');

        $wc_order = wc_get_order($wc_order_id);
        if (!$wc_order) {
            return null;
        }

        global $wpdb;
        $payments_table = $wpdb->prefix . 'asmaa_payments';

        // Check if payment already exists
        // Note: order_id in asmaa_payments now stores wc_order_id directly
        $existing_payment = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$payments_table} WHERE order_id = %d AND wc_payment_id = %d AND deleted_at IS NULL",
            $wc_order_id,
            $wc_order_id
        ));

        $is_paid = $wc_order->is_paid();
        $payment_method = $wc_order->get_payment_method();
        $total = (float) $wc_order->get_total();

        if ($is_paid && $total > 0) {
            if ($existing_payment) {
                // Update existing payment
                $wpdb->update(
                    $payments_table,
                    [
                        'amount' => $total,
                        'payment_method' => $payment_method ?: 'woocommerce',
                        'status' => 'completed',
                        'payment_date' => $wc_order->get_date_paid() ? $wc_order->get_date_paid()->date('Y-m-d H:i:s') : current_time('mysql'),
                    ],
                    ['id' => $existing_payment->id]
                );
                $payment_id = $existing_payment->id;
            } else {
                // Create new payment
                $payment_number = 'PAY-WC-' . date('Ymd') . '-' . str_pad($wc_order_id, 4, '0', STR_PAD_LEFT);
                
                $wpdb->insert($payments_table, [
                    'payment_number' => $payment_number,
                    'order_id' => $wc_order_id, // Now stores wc_order_id directly
                    'wc_customer_id' => (int) $wc_order->get_customer_id(),
                    'amount' => $total,
                    'payment_method' => $payment_method ?: 'woocommerce',
                    'status' => 'completed',
                    'payment_date' => $wc_order->get_date_paid() ? $wc_order->get_date_paid()->date('Y-m-d H:i:s') : current_time('mysql'),
                    'notes' => 'Payment synced from WooCommerce',
                    'wc_payment_id' => $wc_order_id,
                    'processed_by' => 0,
                ]);
                $payment_id = $wpdb->insert_id;
            }

            if ($payment_id) {
                self::log_sync('payment', $payment_id, $wc_order_id, 'from_wc', 'success');
                return $payment_id;
            }
        }

        return null;
    }

    /**
     * Map order status from Asmaa Salon to WooCommerce
     */
    protected static function map_order_status_to_wc(string $status): string
    {
        return match ($status) {
            'pending' => 'wc-pending',
            'completed' => 'wc-completed',
            'cancelled' => 'wc-cancelled',
            'refunded' => 'wc-refunded',
            default => 'wc-pending',
        };
    }

    /**
     * Map order status from WooCommerce to Asmaa Salon
     */
    protected static function map_order_status_from_wc(string $status): string
    {
        $status = str_replace('wc-', '', $status);
        return match ($status) {
            'pending' => 'pending',
            'processing' => 'pending',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Get attachment ID from URL
     */
    protected static function get_attachment_id_from_url(string $url): ?int
    {
        global $wpdb;
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE guid = %s",
            $url
        ));

        return $attachment_id ? (int) $attachment_id : null;
    }


    /**
     * Log sync operation
     */
    protected static function log_sync(
        string $entity_type,
        ?int $entity_id,
        ?int $wc_entity_id,
        string $direction,
        string $status,
        ?string $error_message = null
    ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_wc_sync_log';

        $wpdb->insert($table, [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'wc_entity_id' => $wc_entity_id,
            'sync_direction' => $direction,
            'status' => $status,
            'error_message' => $error_message,
            'synced_at' => current_time('mysql'),
        ]);
    }
}


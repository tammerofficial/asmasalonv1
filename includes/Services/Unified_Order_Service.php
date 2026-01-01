<?php

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class Unified_Order_Service
{
    /**
     * Cache table columns to support mixed/legacy schemas without hard failures.
     *
     * @var array<string, array<string, bool>>
     */
    private static array $columns_cache = [];

    /**
     * Return a fast lookup map of columns for a table.
     *
     * @param string $table Fully qualified table name (with prefix)
     * @return array<string, bool>
     */
    private static function get_table_columns_map(string $table): array
    {
        if (isset(self::$columns_cache[$table])) {
            return self::$columns_cache[$table];
        }

        global $wpdb;

        $cols = [];
        $results = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
        if (is_array($results)) {
            foreach ($results as $col) {
                $cols[strtolower((string) $col)] = true;
            }
        }

        self::$columns_cache[$table] = $cols;
        return $cols;
    }

    private static function table_has_column(string $table, string $column): bool
    {
        $map = self::get_table_columns_map($table);
        return isset($map[strtolower($column)]);
    }

    /**
     * Pick the first existing column name from candidates (in order).
     *
     * @param string $table
     * @param string[] $candidates
     * @return string|null
     */
    private static function pick_column(string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if (self::table_has_column($table, $col)) {
                return $col;
            }
        }
        return null;
    }

    /**
     * Process a complete order from any source (POS, Booking, Queue)
     * This is the central hub for all order processing
     * 
     * @param array $params {
     *     @type int $customer_id WooCommerce customer ID (required)
     *     @type array $items Array of items with structure: ['product_id'|'service_id', 'quantity', 'unit_price', 'name', 'staff_id']
     *     @type string $payment_method Payment method (default: 'cash')
     *     @type float $discount Discount amount (default: 0)
     *     @type int|null $booking_id Optional booking ID
     *     @type int|null $queue_ticket_id Optional queue ticket ID
     *     @type string $source Source of order: 'pos', 'booking', 'queue' (default: 'pos')
     * }
     * @return array {
     *     @type int $wc_order_id WooCommerce order ID
     *     @type string $order_number Order number
     *     @type int $invoice_id Invoice ID
     *     @type string $invoice_number Invoice number
     *     @type int $payment_id Payment ID
     *     @type string $payment_number Payment number
     *     @type float $total Order total
     *     @type float $prepaid_amount Prepaid amount used
     * }
     * @throws \Exception
     */
    public static function process_order(array $params): array
    {
        if (!class_exists('WooCommerce')) {
            throw new \Exception(__('WooCommerce is required', 'asmaa-salon'));
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            // Validate required parameters
            $customer_id = !empty($params['customer_id']) ? (int) $params['customer_id'] : null;
            $items = $params['items'] ?? [];
            $payment_method = sanitize_text_field($params['payment_method'] ?? 'cash');
            $discount = (float) ($params['discount'] ?? 0);
            $booking_id = !empty($params['booking_id']) ? (int) $params['booking_id'] : null;
            $queue_ticket_id = !empty($params['queue_ticket_id']) ? (int) $params['queue_ticket_id'] : null;
            $source = sanitize_text_field($params['source'] ?? 'pos');

            if (empty($customer_id)) {
                throw new \Exception(__('Customer is required', 'asmaa-salon'));
            }

            if (empty($items) || !is_array($items)) {
                throw new \Exception(__('Items are required', 'asmaa-salon'));
            }

            // Calculate subtotal
            $subtotal = 0;
            foreach ($items as $item) {
                if (empty($item['product_id']) && empty($item['service_id'])) {
                    throw new \Exception(__('Each item must have product_id or service_id', 'asmaa-salon'));
                }
                $subtotal += (float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 1);
            }

            // Check for prepaid amounts (deposits) from previous orders
            $prepaid_amount = 0.0;
            $prepaid_orders = [];
            if ($customer_id) {
                $prepaid_orders = wc_get_orders([
                    'customer_id' => $customer_id,
                    'status' => ['processing', 'on-hold'],
                    'limit' => 10,
                ]);
                
                foreach ($prepaid_orders as $prepaid_order) {
                    $prepaid_amount += self::get_total_paid_for_order($prepaid_order->get_id());
                }
            }

            $total = max(0, $subtotal - $discount - $prepaid_amount);

            // Create WooCommerce Order
            $wc_order = wc_create_order([
                'customer_id' => $customer_id,
                // WooCommerce expects statuses without the "wc-" prefix.
                'status' => 'completed',
            ]);

            if (is_wp_error($wc_order)) {
                throw new \Exception($wc_order->get_error_message());
            }

            $wc_order_id = $wc_order->get_id();
            $order_number = $wc_order->get_order_number();
            $inventory_movements_table = $wpdb->prefix . 'asmaa_inventory_movements';
            $created_order_items = [];

            // Add items to WooCommerce order
            foreach ($items as $item) {
                $item_type = !empty($item['product_id']) ? 'product' : 'service';
                $item_id = !empty($item['product_id']) ? (int) $item['product_id'] : (int) $item['service_id'];
                $quantity = (int) ($item['quantity'] ?? 1);
                $unit_price = (float) ($item['unit_price'] ?? 0);
                $item_total = $unit_price * $quantity;
                $staff_id = !empty($item['staff_id']) ? (int) $item['staff_id'] : null;
                $item_name = sanitize_text_field($item['name'] ?? 'Item');

                if ($item_type === 'product') {
                    // Handle product
                    // Lookup WooCommerce Product ID if System ID was provided
                    $actual_wc_id = $item_id;
                    $product_row = $wpdb->get_row($wpdb->prepare(
                        "SELECT wc_product_id FROM {$wpdb->prefix}asmaa_products WHERE id = %d",
                        $item_id
                    ));
                    if ($product_row && !empty($product_row->wc_product_id)) {
                        $actual_wc_id = (int) $product_row->wc_product_id;
                    }

                    $wc_product = wc_get_product($actual_wc_id);
                    if (!$wc_product) {
                        throw new \Exception(__('Product not found', 'asmaa-salon'));
                    }

                    // Check and update stock
                    $current_stock = $wc_product->get_stock_quantity();
                    $before_quantity = (int) ($current_stock ?? 0);
                    $after_quantity = max(0, $before_quantity - $quantity);

                    if ($before_quantity < $quantity) {
                        throw new \Exception(__('Insufficient stock', 'asmaa-salon'));
                    }

                    // Add product to order
                    $wc_order->add_product($wc_product, $quantity, [
                        'subtotal' => $item_total,
                        'total' => $item_total,
                    ]);
                    
                    // Save staff_id in item meta
                    $order_items = $wc_order->get_items();
                    $last_item = end($order_items);
                    if ($last_item && $staff_id) {
                        $last_item->add_meta_data('_asmaa_staff_id', $staff_id);
                        $last_item->save();
                    }

                    // Update stock
                    $wc_product->set_stock_quantity($after_quantity);
                    $wc_product->save();

                    // Check for low stock and send notification
                    $extended_table = $wpdb->prefix . 'asmaa_product_extended_data';
                    $extended = $wpdb->get_row(
                        $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_product_id = %d", $actual_wc_id)
                    );
                    
                    $min_stock = (int) ($extended->min_stock_level ?? 0);
                    if ($extended && $after_quantity <= $min_stock && $before_quantity > $min_stock) {
                        NotificationDispatcher::low_stock_alert($actual_wc_id, [
                            'name' => $wc_product->get_name(),
                            'current_stock' => $after_quantity,
                            'min_stock_level' => $min_stock,
                            'sku' => $wc_product->get_sku() ?? '',
                        ]);
                    }

                    // Create inventory movement
                    $inv_product_col = self::pick_column($inventory_movements_table, ['wc_product_id', 'product_id']);
                    $inv_user_col = self::pick_column($inventory_movements_table, ['wp_user_id', 'performed_by']);

                    $movement_data = [
                        'type' => 'sale',
                        'quantity' => -$quantity,
                        'before_quantity' => $before_quantity,
                        'after_quantity' => $after_quantity,
                        'unit_cost' => 0,
                        'total_cost' => 0,
                        'notes' => "Order #{$wc_order_id} - {$source}",
                        'movement_date' => current_time('mysql'),
                    ];

                    if ($inv_product_col) {
                        $movement_data[$inv_product_col] = $actual_wc_id;
                    }
                    if ($inv_user_col) {
                        $movement_data[$inv_user_col] = get_current_user_id();
                    }
                    
                    // Always fill 'product_id' if it exists in the table (for legacy support)
                    if (self::table_has_column($inventory_movements_table, 'product_id')) {
                        $movement_data['product_id'] = $item_id;
                    }

                    // Inventory movements should never block POS processing (audit only).
                    $inserted = $wpdb->insert($inventory_movements_table, $movement_data);
                    if ($inserted === false) {
                        error_log('Asmaa Salon: Failed to create inventory movement. ' . ($wpdb->last_error ?: ''));
                    }
                } else {
                    // Handle service
                    $service_product_id = Product_Service::get_or_create_service_product(
                        $item_id,
                        $item_name,
                        $unit_price
                    );
                    
                    $wc_product = wc_get_product($service_product_id);
                    if (!$wc_product) {
                        throw new \Exception(__('Service product not found', 'asmaa-salon'));
                    }
                    
                    // Add service as virtual product
                    $wc_order->add_product($wc_product, $quantity, [
                        'subtotal' => $item_total,
                        'total' => $item_total,
                    ]);
                    
                    // Save staff_id in item meta
                    $order_items = $wc_order->get_items();
                    $last_item = end($order_items);
                    if ($last_item && $staff_id) {
                        $last_item->add_meta_data('_asmaa_staff_id', $staff_id);
                        $last_item->save();
                    }
                }

                // Store item info for loyalty/commissions
                $created_order_items[] = [
                    'id' => 0, // Will be updated after order is saved
                    'item_type' => $item_type,
                    'item_id' => $item_id,
                    'item_name' => $item_name,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'total' => $item_total,
                    'staff_id' => $staff_id,
                ];
            }

            // Update item IDs from actual WC order items
            $wc_order_items = $wc_order->get_items();
            $item_index = 0;
            foreach ($wc_order_items as $wc_item_id => $wc_item) {
                if ($item_index < count($created_order_items)) {
                    $created_order_items[$item_index]['id'] = $wc_item_id;
                }
                $item_index++;
            }

            // Set order totals
            $wc_order->set_discount_total($discount);
            $wc_order->calculate_totals(); // This will set subtotal and total based on items
            $wc_order->set_total($total); // Explicitly set total if different from calculated
            $wc_order->set_payment_method($payment_method);
            $wc_order->set_payment_method_title($payment_method);
            $wc_order->payment_complete();
            $wc_order->add_order_note(sprintf('%s Order - Source: %s', ucfirst($source), $source));
            $wc_order->save();

            // Create Invoice
            $invoices_table = $wpdb->prefix . 'asmaa_invoices';
            $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$invoices_table}"), 4, '0', STR_PAD_LEFT);

            $invoice_customer_col = self::pick_column($invoices_table, ['wc_customer_id', 'customer_id']);
            $invoice_order_col = self::pick_column($invoices_table, ['wc_order_id', 'order_id']);

            $invoice_data = [
                'invoice_number' => $invoice_number,
                'issue_date' => current_time('Y-m-d'),
                'due_date' => current_time('Y-m-d'),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => 0,
                'total' => $total,
                'paid_amount' => $total,
                'due_amount' => 0,
                'status' => 'paid',
                'notes' => sprintf('%s Order', ucfirst($source)),
            ];

            if ($invoice_customer_col) {
                $invoice_data[$invoice_customer_col] = $customer_id;
            }
            if ($invoice_order_col) {
                $invoice_data[$invoice_order_col] = $wc_order_id;
            }

            $invoice_inserted = $wpdb->insert($invoices_table, $invoice_data);
            if ($invoice_inserted === false) {
                throw new \Exception(__('Failed to create invoice', 'asmaa-salon') . ': ' . ($wpdb->last_error ?: ''));
            }
            $invoice_id = $wpdb->insert_id;

            // Create Invoice Items
            $invoice_items_table = $wpdb->prefix . 'asmaa_invoice_items';
            foreach ($items as $item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                $unit_price = (float) ($item['unit_price'] ?? 0);
                $item_total = $unit_price * $quantity;

                $invoice_item_inserted = $wpdb->insert($invoice_items_table, [
                    'invoice_id' => $invoice_id,
                    'description' => sanitize_text_field($item['name'] ?? 'Item'),
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'total' => $item_total,
                ]);

                if ($invoice_item_inserted === false) {
                    throw new \Exception(__('Failed to create invoice item', 'asmaa-salon') . ': ' . ($wpdb->last_error ?: ''));
                }
            }

            // Create Payment Record
            $payments_table = $wpdb->prefix . 'asmaa_payments';
            $payment_number = 'PAY-' . date('Ymd') . '-' . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);

            $payment_customer_col = self::pick_column($payments_table, ['wc_customer_id', 'customer_id']);
            $payment_user_col = self::pick_column($payments_table, ['wp_user_id', 'processed_by']);
            $payment_order_col = self::pick_column($payments_table, ['order_id', 'wc_order_id']);

            $payment_data = [
                'payment_number' => $payment_number,
                'invoice_id' => $invoice_id,
                'amount' => $total,
                'payment_method' => $payment_method,
                'status' => 'completed',
                'payment_date' => current_time('mysql'),
                'notes' => sprintf('%s Payment', ucfirst($source)),
            ];

            if ($payment_customer_col) {
                $payment_data[$payment_customer_col] = $customer_id;
            }
            if ($payment_order_col) {
                $payment_data[$payment_order_col] = $wc_order_id;
            }
            if ($payment_user_col) {
                $payment_data[$payment_user_col] = get_current_user_id();
            }

            $payment_inserted = $wpdb->insert($payments_table, $payment_data);
            if ($payment_inserted === false) {
                throw new \Exception(__('Failed to create payment', 'asmaa-salon') . ': ' . ($wpdb->last_error ?: ''));
            }
            $payment_id = $wpdb->insert_id;

            // Update invoice with payment_id
            if (self::table_has_column($invoices_table, 'payment_id')) {
                $wpdb->update($invoices_table, ['payment_id' => $payment_id], ['id' => $invoice_id]);
            }

            // Process Loyalty Points
            if ($customer_id) {
                // Loyalty should never block order completion.
                try {
                    Loyalty_Service::process_order_points(
                        $customer_id,
                        $wc_order_id,
                        $order_number,
                        $created_order_items,
                        $total
                    );
                } catch (\Throwable $e) {
                    error_log('Asmaa Salon: Loyalty processing failed for order #' . $wc_order_id . ' - ' . $e->getMessage());
                }
            }

            // Process Commissions
            // Commissions should never block order completion.
            try {
                Commission_Service::process_order_commissions(
                    $wc_order_id,
                    $created_order_items,
                    $booking_id
                );
            } catch (\Throwable $e) {
                error_log('Asmaa Salon: Commission processing failed for order #' . $wc_order_id . ' - ' . $e->getMessage());
            }

            // Update related booking/queue status if provided
            if ($booking_id) {
                $bookings_table = $wpdb->prefix . 'asmaa_bookings';
                $wpdb->update($bookings_table, [
                    'status' => 'completed',
                    'completed_at' => current_time('mysql'),
                ], ['id' => $booking_id]);
            }

            if ($queue_ticket_id) {
                $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';
                $wpdb->update($queue_table, [
                    'status' => 'completed',
                    'completed_at' => current_time('mysql'),
                ], ['id' => $queue_ticket_id]);
            }

            // Activity Log
            ActivityLogger::log_order('created', $wc_order_id, $customer_id, [
                'status' => 'completed',
                'payment_status' => 'paid',
                'total' => $total,
                'items_count' => count($items),
                'source' => $source,
                'booking_id' => $booking_id,
                'queue_ticket_id' => $queue_ticket_id,
                'wc_order_id' => $wc_order_id,
            ]);

            // Auto-create queue tickets and worker calls for POS orders with services
            if ($source === 'pos' && empty($queue_ticket_id)) {
                self::auto_create_queue_tickets_from_pos($wc_order_id, $customer_id, $items);
            }

            $wpdb->query('COMMIT');

            return [
                'wc_order_id' => $wc_order_id,
                'order_number' => $order_number,
                'invoice_id' => $invoice_id,
                'invoice_number' => $invoice_number,
                'payment_id' => $payment_id,
                'payment_number' => $payment_number,
                'customer_id' => $customer_id,
                'total' => $total,
                'prepaid_amount' => $prepaid_amount,
                'prepaid_orders_count' => count($prepaid_orders),
            ];
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Create order from booking
     * 
     * @param int $booking_id Booking ID
     * @param array $additional_items Additional items to add to order (optional)
     * @param string $payment_method Payment method (default: 'cash')
     * @return array Order processing result
     * @throws \Exception
     */
    public static function create_from_booking(int $booking_id, array $additional_items = [], string $payment_method = 'cash'): array
    {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $services_table = $wpdb->prefix . 'asmaa_services';

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, s.name as service_name, s.price as service_price
             FROM {$bookings_table} b
             LEFT JOIN {$services_table} s ON b.service_id = s.id
             WHERE b.id = %d AND b.deleted_at IS NULL",
            $booking_id
        ));

        if (!$booking) {
            throw new \Exception(__('Booking not found', 'asmaa-salon'));
        }

        // Build items array from booking
        $items = [];
        
        // Add booking service as first item
        if ($booking->service_id) {
            $items[] = [
                'service_id' => (int) $booking->service_id,
                'name' => $booking->service_name ?? __('Service', 'asmaa-salon'),
                'quantity' => 1,
                'unit_price' => (float) ($booking->final_price ?? $booking->price ?? $booking->service_price ?? 0),
                'staff_id' => $booking->wp_user_id ? (int) $booking->wp_user_id : null,
            ];
        }

        // Add additional items
        $items = array_merge($items, $additional_items);

        return self::process_order([
            'customer_id' => (int) $booking->wc_customer_id,
            'items' => $items,
            'payment_method' => $payment_method,
            'discount' => (float) ($booking->discount ?? 0),
            'booking_id' => $booking_id,
            'source' => 'booking',
        ]);
    }

    /**
     * Create order from queue ticket
     * 
     * @param int $queue_ticket_id Queue ticket ID
     * @param array $additional_items Additional items to add to order (optional)
     * @param string $payment_method Payment method (default: 'cash')
     * @return array Order processing result
     * @throws \Exception
     */
    public static function create_from_queue(int $queue_ticket_id, array $additional_items = [], string $payment_method = 'cash'): array
    {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';
        $services_table = $wpdb->prefix . 'asmaa_services';

        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT q.*, s.name as service_name, s.price as service_price
             FROM {$queue_table} q
             LEFT JOIN {$services_table} s ON q.service_id = s.id
             WHERE q.id = %d AND q.deleted_at IS NULL",
            $queue_ticket_id
        ));

        if (!$ticket) {
            throw new \Exception(__('Queue ticket not found', 'asmaa-salon'));
        }

        // Build items array from queue ticket
        $items = [];
        
        // Add queue service as first item
        if ($ticket->service_id) {
            $items[] = [
                'service_id' => (int) $ticket->service_id,
                'name' => $ticket->service_name ?? __('Service', 'asmaa-salon'),
                'quantity' => 1,
                'unit_price' => (float) ($ticket->service_price ?? 0),
                'staff_id' => $ticket->wp_user_id ? (int) $ticket->wp_user_id : null,
            ];
        }

        // Add additional items
        $items = array_merge($items, $additional_items);

        return self::process_order([
            'customer_id' => (int) $ticket->wc_customer_id,
            'items' => $items,
            'payment_method' => $payment_method,
            'discount' => 0,
            'queue_ticket_id' => $queue_ticket_id,
            'booking_id' => $ticket->booking_id ? (int) $ticket->booking_id : null,
            'source' => 'queue',
        ]);
    }

    /**
     * Get total paid amount for a WooCommerce order from our payments table
     * 
     * @param int $wc_order_id WooCommerce order ID
     * @return float Total paid amount
     */
    public static function get_total_paid_for_order(int $wc_order_id): float
    {
        global $wpdb;
        $payments_table = $wpdb->prefix . 'asmaa_payments';
        
        $paid = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$payments_table} WHERE order_id = %d AND status = 'completed'",
            $wc_order_id
        ));
        
        return (float) ($paid ?: 0);
    }

    /**
     * Automatically create queue tickets and worker calls for POS orders containing services
     * 
     * @param int $wc_order_id WooCommerce order ID
     * @param int $customer_id Customer ID
     * @param array $items Order items
     * @return void
     */
    private static function auto_create_queue_tickets_from_pos(int $wc_order_id, int $customer_id, array $items): void
    {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';
        $worker_calls_table = $wpdb->prefix . 'asmaa_worker_calls';
        $services_table = $wpdb->prefix . 'asmaa_services';

        // Get customer name for worker calls
        $customer_name = null;
        $customer = get_user_by('ID', $customer_id);
        if ($customer) {
            $customer_name = $customer->display_name ?: $customer->user_login;
        }

        // Filter items that are services (have service_id)
        $service_items = array_filter($items, function($item) {
            return !empty($item['service_id']);
        });

        if (empty($service_items)) {
            return; // No services in this order, skip automation
        }

        // Create queue ticket and worker call for each service
        foreach ($service_items as $item) {
            $service_id = (int) $item['service_id'];
            $staff_id = !empty($item['staff_id']) ? (int) $item['staff_id'] : null;
            $quantity = (int) ($item['quantity'] ?? 1);

            // Get service name
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT name FROM {$services_table} WHERE id = %d",
                $service_id
            ));
            $service_name = $service ? $service->name : __('Service', 'asmaa-salon');

            // Create queue ticket for each quantity (if multiple services, create multiple tickets)
            for ($i = 0; $i < $quantity; $i++) {
                // Generate ticket number
                $ticket_number = 'T-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$queue_table}"), 4, '0', STR_PAD_LEFT);

                // Create queue ticket
                $queue_data = [
                    'ticket_number' => $ticket_number,
                    'wc_customer_id' => $customer_id,
                    'service_id' => $service_id,
                    'wp_user_id' => $staff_id,
                    'status' => 'waiting',
                    'notes' => sprintf(__('Auto-created from POS Order #%d', 'asmaa-salon'), $wc_order_id),
                    'check_in_at' => current_time('mysql'),
                ];

                $wpdb->insert($queue_table, $queue_data);
                if ($wpdb->last_error) {
                    continue; // Skip this ticket if insertion failed
                }

                $ticket_id = (int) $wpdb->insert_id;

                // Create worker call with 'staff_called' status
                $worker_call_data = [
                    'wp_user_id' => $staff_id ?: 0,
                    'ticket_id' => $ticket_id,
                    'customer_name' => $customer_name,
                    'status' => 'staff_called',
                    'notes' => sprintf(__('Auto-created from POS Order #%d - Service: %s', 'asmaa-salon'), $wc_order_id, $service_name),
                ];

                $wpdb->insert($worker_calls_table, $worker_call_data);
                $worker_call_id = $wpdb->last_error ? null : (int) $wpdb->insert_id;

                // Log activity
                ActivityLogger::log_queue_ticket('created', $ticket_id, $customer_id, [
                    'status' => 'waiting',
                    'service_id' => $service_id,
                    'staff_id' => $staff_id,
                    'source' => 'pos_auto',
                    'wc_order_id' => $wc_order_id,
                ]);

                // Dispatch dashboard notification for staff call
                if ($worker_call_id) {
                    NotificationDispatcher::dashboard_admins('Dashboard.WorkerCallStaffCalled', [
                        'event' => 'worker_call.staff_called',
                        'worker_call_id' => $worker_call_id,
                        'ticket_id' => $ticket_id,
                        'staff_id' => $staff_id,
                        'customer_id' => $customer_id,
                        'service_name' => $service_name,
                        'title_en' => 'Staff called automatically',
                        'message_en' => sprintf('Customer "%s" added to queue from POS. Service: %s', $customer_name ?: 'Customer', $service_name),
                        'title_ar' => 'تم استدعاء موظف تلقائياً',
                        'message_ar' => sprintf('تم إضافة العميل "%s" إلى الطابور من نقطة البيع. الخدمة: %s', $customer_name ?: 'عميل', $service_name),
                        'action' => [
                            'route' => '/queue',
                        ],
                        'severity' => 'info',
                    ]);
                }
            }
        }
    }
}


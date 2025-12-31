<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\ActivityLogger;

if (!defined('ABSPATH')) {
    exit;
}

class POS_Controller extends Base_Controller
{
    protected string $rest_base = 'pos';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_initial_data'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/process', [
            ['methods' => 'POST', 'callback' => [$this, 'process_order'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/session', [
            ['methods' => 'GET', 'callback' => [$this, 'get_session'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
            ['methods' => 'POST', 'callback' => [$this, 'create_session'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/session/close', [
            ['methods' => 'POST', 'callback' => [$this, 'close_session'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/prepaid/(?P<customer_id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_prepaid_orders'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/sync-pending', [
            ['methods' => 'POST', 'callback' => [$this, 'sync_pending_orders'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
        ]);
    }

    /**
     * Get initial data for POS page
     */
    public function get_initial_data(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        
        try {
            // Get active customers (from queue and bookings)
            $activeCustomers = $this->get_active_customers();
            
            // Get open session
            $session = $this->get_open_session();
            
            return $this->success_response([
                'active_customers' => $activeCustomers,
                'open_session' => $session,
            ]);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Process POS order
     */
    public function process_order(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            // Validate request
            $customer_id = $request->get_param('customer_id') ? (int) $request->get_param('customer_id') : null;
            $items = $request->get_param('items');
            $payment_method = sanitize_text_field($request->get_param('payment_method')) ?: 'cash';
            $discount = (float) ($request->get_param('discount') ?? 0);
            $booking_id = $request->get_param('booking_id') ? (int) $request->get_param('booking_id') : null;
            $queue_ticket_id = $request->get_param('queue_ticket_id') ? (int) $request->get_param('queue_ticket_id') : null;

            if (empty($customer_id)) {
                throw new \Exception(__('Customer is required for POS sale', 'asmaa-salon'));
            }

            if (empty($items) || !is_array($items)) {
                throw new \Exception(__('Items are required', 'asmaa-salon'));
            }

            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                if (empty($item['product_id']) && empty($item['service_id'])) {
                    throw new \Exception(__('Each item must have product_id or service_id', 'asmaa-salon'));
                }
                $subtotal += (float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 1);
            }

            // Check for prepaid amounts (deposits) from previous orders
            $prepaid_amount = 0.0;
            if ($customer_id) {
                $prepaid_orders = wc_get_orders([
                    'customer_id' => $customer_id,
                    'status' => ['processing', 'on-hold'],
                    'limit' => 10,
                ]);
                
                foreach ($prepaid_orders as $prepaid_order) {
                    $prepaid_amount += (float) $prepaid_order->get_total_paid();
                }
            }

            $total = max(0, $subtotal - $discount - $prepaid_amount);
            
            // Store prepaid amount info for response
            $prepaid_info = [
                'prepaid_amount' => $prepaid_amount,
                'prepaid_orders_count' => count($prepaid_orders ?? []),
            ];

            // Create WooCommerce Order directly
            $wc_order = wc_create_order([
                'customer_id' => $customer_id,
                'status' => 'wc-completed',
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
                    // Add product to WooCommerce order
                    $wc_product = wc_get_product($item_id);
                    if (!$wc_product) {
                        throw new \Exception(__('Product not found', 'asmaa-salon'));
                    }

                    // Check stock before adding
                    $current_stock = $wc_product->get_stock_quantity();
                    $before_quantity = (int) ($current_stock ?? 0);
                    $after_quantity = max(0, $before_quantity - $quantity);

                    if ($after_quantity < 0) {
                        throw new \Exception(__('Insufficient stock', 'asmaa-salon'));
                    }

                    // Add product to order
                    $wc_order->add_product($wc_product, $quantity, [
                        'subtotal' => $item_total,
                        'total' => $item_total,
                    ]);
                    
                    // Get the last added item and save staff_id in meta
                    $items = $wc_order->get_items();
                    $last_item = end($items);
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
                        $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_product_id = %d", $item_id)
                    );
                    
                    $min_stock = (int) ($extended->min_stock_level ?? 0);
                    if ($extended && $after_quantity <= $min_stock && $before_quantity > $min_stock) {
                        \AsmaaSalon\Services\NotificationDispatcher::low_stock_alert($item_id, [
                            'name' => $wc_product->get_name(),
                            'current_stock' => $after_quantity,
                            'min_stock_level' => $min_stock,
                            'sku' => $wc_product->get_sku() ?? '',
                        ]);
                    }

                    // Create inventory movement
                    $wpdb->insert($inventory_movements_table, [
                        'wc_product_id' => $item_id,
                        'type' => 'sale',
                        'quantity' => -$quantity,
                        'before_quantity' => $before_quantity,
                        'after_quantity' => $after_quantity,
                        'unit_cost' => 0,
                        'total_cost' => 0,
                        'notes' => "POS Sale - WC Order #{$wc_order_id}",
                        'wp_user_id' => get_current_user_id(),
                        'movement_date' => current_time('mysql'),
                    ]);
                    if ($wpdb->last_error) {
                        throw new \Exception(__('Failed to create inventory movement', 'asmaa-salon'));
                    }
                } else {
                    // Add service as Virtual Product
                    $service_product_id = \AsmaaSalon\Services\Product_Service::get_or_create_service_product(
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
                    
                    // Get the last added item and save staff_id in meta
                    $items = $wc_order->get_items();
                    $last_item = end($items);
                    if ($last_item && $staff_id) {
                        $last_item->add_meta_data('_asmaa_staff_id', $staff_id);
                        $last_item->save();
                    }
                }

                // Store item info for loyalty/commissions
                $created_order_items[] = [
                    'id' => 0, // WC order items don't have our IDs
                    'item_type' => $item_type,
                    'item_id' => $item_id,
                    'item_name' => $item_name,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'total' => $item_total,
                    'staff_id' => $staff_id,
                ];
            }

            // Set order totals
            $wc_order->set_subtotal($subtotal);
            $wc_order->set_discount_total($discount);
            $wc_order->set_total($total);
            $wc_order->set_payment_method($payment_method);
            $wc_order->set_payment_method_title($payment_method);
            $wc_order->payment_complete(); // Mark as paid
            $wc_order->add_order_note('POS Sale');
            $wc_order->save();

            // Create Invoice (still needed for our system)
            $invoices_table = $wpdb->prefix . 'asmaa_invoices';
            $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$invoices_table}"), 4, '0', STR_PAD_LEFT);

            $invoice_data = [
                'wc_order_id' => $wc_order_id,
                'wc_customer_id' => $customer_id,
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
                'notes' => 'POS Sale',
            ];

            $wpdb->insert($invoices_table, $invoice_data);
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create invoice', 'asmaa-salon'));
            }
            $invoice_id = $wpdb->insert_id;

            // Create Invoice Items
            $invoice_items_table = $wpdb->prefix . 'asmaa_invoice_items';
            foreach ($items as $item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                $unit_price = (float) ($item['unit_price'] ?? 0);
                $item_total = $unit_price * $quantity;

                $wpdb->insert($invoice_items_table, [
                    'invoice_id' => $invoice_id,
                    'description' => sanitize_text_field($item['name'] ?? 'Item'),
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'total' => $item_total,
                ]);
                if ($wpdb->last_error) {
                    throw new \Exception(__('Failed to create invoice item', 'asmaa-salon'));
                }
            }

            // Create Payment Record
            $payments_table = $wpdb->prefix . 'asmaa_payments';
            $payment_number = 'PAY-' . date('Ymd') . '-' . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);
            
            $payment_data = [
                'payment_number' => $payment_number,
                'invoice_id' => $invoice_id,
                'wc_customer_id' => $customer_id,
                'wc_order_id' => $wc_order_id,
                'amount' => $total,
                'payment_method' => $payment_method,
                'status' => 'completed',
                'payment_date' => current_time('mysql'),
                'notes' => 'POS Payment',
                'wp_user_id' => get_current_user_id(),
            ];

            $wpdb->insert($payments_table, $payment_data);
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create payment', 'asmaa-salon'));
            }
            $payment_id = $wpdb->insert_id;

            // Update invoice with payment_id
            $wpdb->update($invoices_table, ['payment_id' => $payment_id], ['id' => $invoice_id]);

            // Update POS Session
            $this->update_session($wc_order_id, $total, $payment_method);

            // Process Loyalty Points (if customer exists)
            if ($customer_id) {
                $this->process_loyalty_points($customer_id, $wc_order_id, $order_number, $created_order_items, $total);
            }

            // Process Commissions (if staff assigned)
            $this->process_commissions($wc_order_id, $created_order_items);

            // Activity Log
            ActivityLogger::log_order('created', $wc_order_id, $customer_id, [
                'status' => 'completed',
                'payment_status' => 'paid',
                'total' => $total,
                'items_count' => count($items),
                'pos' => true,
                'wc_order_id' => $wc_order_id,
            ]);

            $wpdb->query('COMMIT');

            return $this->success_response([
                'order_id' => $wc_order_id,
                'order_number' => $order_number,
                'invoice_id' => $invoice_id,
                'invoice_number' => $invoice_number,
                'payment_id' => $payment_id,
                'payment_number' => $payment_number,
                'customer_id' => $customer_id,
                'total' => $total,
                'prepaid_amount' => $prepaid_amount,
                'prepaid_orders_count' => count($prepaid_orders ?? []),
            ], __('Order processed successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get or create POS session
     */
    public function get_session(WP_REST_Request $request): WP_REST_Response
    {
        $session = $this->get_open_session();
        
        if (!$session) {
            // Auto-create session for development
            $session = $this->create_new_session();
        }

        return $this->success_response($session);
    }

    /**
     * Create new POS session
     */
    public function create_session(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        
        try {
            // Check if there's already an open session
            $existing = $this->get_open_session();
            if ($existing) {
                return $this->success_response($existing);
            }

            $session = $this->create_new_session();
            return $this->success_response($session, __('Session created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Close POS session
     */
    public function close_session(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        
        try {
            $sessions_table = $wpdb->prefix . 'asmaa_pos_sessions';
            $user_id = get_current_user_id();

            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$sessions_table} WHERE user_id = %d AND status = 'open'",
                $user_id
            ));

            if (!$session) {
                return $this->error_response(__('No open session found', 'asmaa-salon'), 404);
            }

            $closing_cash = (float) ($request->get_param('closing_cash') ?? 0);
            $notes = sanitize_textarea_field($request->get_param('notes') ?? '');

            $wpdb->update($sessions_table, [
                'status' => 'closed',
                'closed_at' => current_time('mysql'),
                'closing_cash' => $closing_cash,
                'notes' => $notes,
            ], ['id' => $session->id]);

            return $this->success_response([
                'session_id' => $session->id,
                'status' => 'closed',
            ], __('Session closed successfully', 'asmaa-salon'));

        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get active customers from queue and bookings
     */
    private function get_active_customers(): array
    {
        global $wpdb;
        
        $active_customers = [];
        
        // Get customers from queue
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';
        $services_table = $wpdb->prefix . 'asmaa_services';

        $queue_customers = $wpdb->get_results(
            "SELECT q.*, 
                    u.display_name as name, 
                    u.user_email as email,
                    s.name as service_name, 
                    st.display_name as staff_name
             FROM {$queue_table} q
             LEFT JOIN {$wpdb->users} u ON q.wc_customer_id = u.ID
             LEFT JOIN {$services_table} s ON q.service_id = s.id
             LEFT JOIN {$wpdb->users} st ON q.wp_user_id = st.ID
             WHERE DATE(q.created_at) = CURDATE()
             AND q.status IN ('waiting', 'called', 'serving')
             AND q.deleted_at IS NULL
             ORDER BY q.created_at ASC"
        );

        foreach ($queue_customers as $ticket) {
            if ($ticket->wc_customer_id) {
                // Get phone from WooCommerce customer
                $wc_customer = new \WC_Customer($ticket->wc_customer_id);
                $phone = $wc_customer->get_billing_phone();
                
                $active_customers[] = [
                    'id' => $ticket->wc_customer_id,
                    'name' => !empty($ticket->name) ? $ticket->name : 'Walk-in Customer',
                    'phone' => $phone ?? '',
                    'current_service' => $ticket->service_name ?? 'N/A',
                    'staff_name' => $ticket->staff_name ?? 'Unassigned',
                    'status' => $ticket->status,
                    'type' => 'queue',
                    'ticket_number' => $ticket->ticket_number ?? null,
                    'check_in_at' => $ticket->check_in_at ?? null,
                ];
            }
        }

        // Get customers from bookings (all today's bookings - pending, confirmed, in_progress)
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $booking_customers = $wpdb->get_results(
            "SELECT b.*, 
                    u.display_name as name, 
                    u.user_email as email,
                    s.name as service_name, 
                    st.display_name as staff_name
             FROM {$bookings_table} b
             LEFT JOIN {$wpdb->users} u ON b.wc_customer_id = u.ID
             LEFT JOIN {$services_table} s ON b.service_id = s.id
             LEFT JOIN {$wpdb->users} st ON b.wp_user_id = st.ID
             WHERE b.booking_date = CURDATE()
             AND b.status IN ('pending', 'confirmed', 'in_progress')
             AND b.deleted_at IS NULL
             ORDER BY b.booking_time ASC"
        );

        foreach ($booking_customers as $booking) {
            if ($booking->wc_customer_id) {
                // Get phone from WooCommerce customer
                $wc_customer = new \WC_Customer($booking->wc_customer_id);
                $phone = $wc_customer->get_billing_phone();
                
                $active_customers[] = [
                    'id' => $booking->wc_customer_id,
                    'name' => !empty($booking->name) ? $booking->name : 'Walk-in Customer',
                    'phone' => $phone ?? '',
                    'current_service' => $booking->service_name ?? 'N/A',
                    'staff_name' => $booking->staff_name ?? 'Unassigned',
                    'status' => $booking->status,
                    'type' => 'booking',
                    'booking_date' => $booking->booking_date ?? null,
                    'booking_time' => $booking->booking_time ?? null,
                    'booking_start_at' => (!empty($booking->booking_date) && !empty($booking->booking_time))
                        ? ($booking->booking_date . ' ' . $booking->booking_time)
                        : null,
                ];
            }
        }

        // Remove duplicates
        $unique_customers = [];
        $seen_ids = [];
        foreach ($active_customers as $customer) {
            if (!in_array($customer['id'], $seen_ids)) {
                $unique_customers[] = $customer;
                $seen_ids[] = $customer['id'];
            }
        }

        return $unique_customers;
    }

    /**
     * Get open POS session
     */
    private function get_open_session(): ?object
    {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'asmaa_pos_sessions';
        $user_id = get_current_user_id();

        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$sessions_table} WHERE user_id = %d AND status = 'open' ORDER BY opened_at DESC LIMIT 1",
            $user_id
        ));

        return $session;
    }

    /**
     * Create new POS session
     */
    private function create_new_session(): object
    {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'asmaa_pos_sessions';
        $user_id = get_current_user_id();

        $wpdb->insert($sessions_table, [
            'user_id' => $user_id,
            'opened_at' => current_time('mysql'),
            'opening_balance' => 0,
            'expected_cash' => 0,
            'actual_cash' => null,
            'status' => 'open',
            'notes' => 'Auto-created session',
        ]);

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions_table} WHERE id = %d", $wpdb->insert_id));
    }

    /**
     * Update POS session with new transaction
     */
    private function update_session(int $wc_order_id, float $total, string $payment_method = 'cash'): void
    {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'asmaa_pos_sessions';
        $user_id = get_current_user_id();

        $session = $this->get_open_session();
        if ($session) {
            // Track expected cash only when payment is cash
            if ($payment_method === 'cash') {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$sessions_table}
                     SET expected_cash = COALESCE(expected_cash, opening_balance) + %f
                     WHERE id = %d",
                    $total,
                    $session->id
                ));
            }
        }
    }

    /**
     * Process loyalty points for customer
     */
    private function process_loyalty_points(int $customer_id, int $order_id, string $order_number, array $order_items, float $total): void
    {
        global $wpdb;

        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';

        $extended = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$extended_table} WHERE wc_customer_id = %d",
            $customer_id
        ));
        
        if (!$extended) {
            // Create extended data if doesn't exist
            $wpdb->insert($extended_table, [
                'wc_customer_id' => $customer_id,
                'total_visits' => 1,
                'total_spent' => $total,
                'loyalty_points' => 0,
            ]);
            $extended = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$extended_table} WHERE wc_customer_id = %d",
                $customer_id
            ));
        }

        // Always update customer totals for purchases
        $wpdb->query($wpdb->prepare(
            "UPDATE {$extended_table}
             SET total_visits = COALESCE(total_visits, 0) + 1,
                 total_spent = COALESCE(total_spent, 0) + %f
             WHERE wc_customer_id = %d",
            $total,
            $customer_id
        ));

        // Programs settings: apply ONE points value to all services + products
        $programs = get_option('asmaa_salon_programs_settings', []);
        $loyalty = is_array($programs) && isset($programs['loyalty']) && is_array($programs['loyalty']) ? $programs['loyalty'] : [];
        $enabled = array_key_exists('enabled', $loyalty) ? (bool) $loyalty['enabled'] : true;
        if (!$enabled) {
            return;
        }

        $points_per_item = (int) ($loyalty['default_service_points'] ?? 1);
        if ($points_per_item < 0) {
            $points_per_item = 0;
        }

        $balance_before = (int) ($extended->loyalty_points ?? 0);
        $balance_after = $balance_before;

        foreach ($order_items as $row) {
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $points = $points_per_item * $qty;
            if ($points <= 0) {
                continue;
            }

            $next_balance = $balance_after + $points;
            $desc = sprintf(
                'Order %s: %s x%d',
                $order_number,
                (string) ($row['item_name'] ?? 'Item'),
                $qty
            );

            $wpdb->insert($transactions_table, [
                'wc_customer_id' => $customer_id,
                'type' => 'earned',
                'points' => $points,
                'balance_before' => $balance_after,
                'balance_after' => $next_balance,
                'reference_type' => 'order_item',
                'reference_id' => (int) ($row['id'] ?? 0),
                'description' => $desc,
                'wp_user_id' => get_current_user_id(),
            ]);

            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create loyalty transaction', 'asmaa-salon'));
            }

            $balance_after = $next_balance;
        }

        if ($balance_after !== $balance_before) {
            $wpdb->update($extended_table, ['loyalty_points' => $balance_after], ['wc_customer_id' => $customer_id]);
            
            // Update Apple Wallet pass
            try {
                \AsmaaSalon\Services\Apple_Wallet_Service::update_loyalty_pass($customer_id);
            } catch (\Exception $e) {
                error_log('Apple Wallet update failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Process staff commissions
     */
    private function process_commissions(int $order_id, array $order_items): void
    {
        global $wpdb;

        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';

        $programs = get_option('asmaa_salon_programs_settings', []);
        $comm = is_array($programs) && isset($programs['commissions']) && is_array($programs['commissions']) ? $programs['commissions'] : [];
        $enabled = array_key_exists('enabled', $comm) ? (bool) $comm['enabled'] : true;
        if (!$enabled) {
            return;
        }

        $default_service_rate = (float) ($comm['default_service_rate'] ?? 10.00);
        $default_product_rate = (float) ($comm['default_product_rate'] ?? 5.00);
        $staff_overrides = isset($comm['staff_overrides']) && is_array($comm['staff_overrides']) ? $comm['staff_overrides'] : [];

        foreach ($order_items as $row) {
            $staff_id = !empty($row['staff_id']) ? (int) $row['staff_id'] : null;
            if (!$staff_id) {
                continue;
            }

            $item_type = (string) ($row['item_type'] ?? '');
            $base_amount = (float) ($row['total'] ?? 0);
            if ($base_amount <= 0) {
                continue;
            }

            $rate = $item_type === 'product' ? $default_product_rate : $default_service_rate;
            if (isset($staff_overrides[(string) $staff_id]) && is_array($staff_overrides[(string) $staff_id])) {
                $ov = $staff_overrides[(string) $staff_id];
                $rate = $item_type === 'product'
                    ? (float) ($ov['product_rate'] ?? $rate)
                    : (float) ($ov['service_rate'] ?? $rate);
            }
            if ($rate < 0) {
                $rate = 0;
            }

            $commission_amount = round($base_amount * ($rate / 100), 3);
            if ($commission_amount <= 0) {
                continue;
            }

            $wpdb->insert($commissions_table, [
                'wp_user_id' => $staff_id,
                'order_id' => $order_id,
                'order_item_id' => (int) ($row['id'] ?? 0),
                'booking_id' => null,
                'base_amount' => $base_amount,
                'commission_rate' => $rate,
                'commission_amount' => $commission_amount,
                'rating_bonus' => 0,
                'final_amount' => $commission_amount,
                'status' => 'pending',
                'notes' => (string) ($row['item_name'] ?? ''),
            ]);

            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create staff commission', 'asmaa-salon'));
            }
        }
    }

    /**
     * Get prepaid orders for a customer
     */
    public function get_prepaid_orders(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $customer_id = (int) $request->get_param('customer_id');
        
        if (empty($customer_id)) {
            return $this->error_response(__('Customer ID is required', 'asmaa-salon'), 400);
        }

        $prepaid_orders = wc_get_orders([
            'customer_id' => $customer_id,
            'status' => ['processing', 'on-hold'],
            'limit' => 20,
        ]);

        $prepaid_data = [];
        $total_prepaid = 0.0;

        foreach ($prepaid_orders as $order) {
            $paid = (float) $order->get_total_paid();
            $total_prepaid += $paid;
            
            $prepaid_data[] = [
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'total' => (float) $order->get_total(),
                'paid' => $paid,
                'status' => $order->get_status(),
            ];
        }

        // Update prepaid orders status to completed if fully paid
        if ($total_prepaid > 0) {
            foreach ($prepaid_orders as $order) {
                if ((float) $order->get_total_paid() >= (float) $order->get_total()) {
                    $order->update_status('completed', 'Completed via POS payment');
                }
            }
        }

        return $this->success_response([
            'prepaid_orders' => $prepaid_data,
            'total_prepaid' => $total_prepaid,
            'count' => count($prepaid_data),
        ]);
    }

    /**
     * Sync pending orders from offline storage (Idempotency)
     */
    public function sync_pending_orders(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        global $wpdb;
        $pending_orders_table = $wpdb->prefix . 'asmaa_pending_orders_sync';
        
        $orders = $request->get_param('orders');
        if (!is_array($orders) || empty($orders)) {
            return $this->error_response(__('No orders provided', 'asmaa-salon'), 400);
        }
        
        $results = [];
        
        foreach ($orders as $order_data) {
            $client_side_id = sanitize_text_field($order_data['client_side_id'] ?? '');
            
            if (empty($client_side_id)) {
                $results[] = [
                    'client_side_id' => $client_side_id,
                    'success' => false,
                    'error' => 'Missing client_side_id',
                ];
                continue;
            }
            
            // Idempotency Check: التحقق من وجود الطلب مسبقاً
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT wc_order_id FROM {$pending_orders_table} WHERE client_side_id = %s",
                $client_side_id
            ));
            
            if ($existing) {
                // الطلب موجود مسبقاً - إرجاع النتيجة بدون معالجة
                $results[] = [
                    'client_side_id' => $client_side_id,
                    'success' => true,
                    'wc_order_id' => (int) $existing,
                    'message' => 'Order already synced',
                ];
                continue;
            }
            
            // معالجة الطلب
            try {
                $order_data_array = $order_data['order_data'] ?? [];
                
                // Create a new request with order data
                $order_request = new WP_REST_Request('POST', $this->namespace . '/pos/process');
                foreach ($order_data_array as $key => $value) {
                    $order_request->set_param($key, $value);
                }
                
                $response = $this->process_order($order_request);
                
                if (is_wp_error($response)) {
                    throw new \Exception($response->get_error_message());
                }
                
                $response_data = $response->get_data();
                $wc_order_id = $response_data['data']->order_id ?? null;
                
                if (!$wc_order_id) {
                    throw new \Exception('Failed to get WC order ID from response');
                }
                
                // تسجيل الطلب في جدول المزامنة
                $wpdb->insert($pending_orders_table, [
                    'client_side_id' => $client_side_id,
                    'wc_order_id' => $wc_order_id,
                    'synced_at' => current_time('mysql'),
                ]);
                
                $results[] = [
                    'client_side_id' => $client_side_id,
                    'success' => true,
                    'wc_order_id' => $wc_order_id,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'client_side_id' => $client_side_id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $this->success_response([
            'synced' => count(array_filter($results, fn($r) => $r['success'])),
            'failed' => count(array_filter($results, fn($r) => !$r['success'])),
            'results' => $results,
        ]);
    }
}
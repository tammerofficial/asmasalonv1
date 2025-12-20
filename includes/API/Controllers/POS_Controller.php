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
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            // Validate request
            $customer_id = $request->get_param('customer_id') ? (int) $request->get_param('customer_id') : null;
            $items = $request->get_param('items');
            $payment_method = sanitize_text_field($request->get_param('payment_method')) ?: 'cash';
            $discount = (float) ($request->get_param('discount') ?? 0);

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

            $total = max(0, $subtotal - $discount);

            // Create Order
            $orders_table = $wpdb->prefix . 'asmaa_orders';
            $order_number = 'ORD-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$orders_table}"), 4, '0', STR_PAD_LEFT);

            $order_data = [
                'customer_id' => $customer_id,
                'order_number' => $order_number,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => 0,
                'total' => $total,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => $payment_method,
                'notes' => 'POS Sale',
            ];

            $wpdb->insert($orders_table, $order_data);
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create order', 'asmaa-salon'));
            }

            $order_id = $wpdb->insert_id;

            // Create Order Items and process inventory
            $order_items_table = $wpdb->prefix . 'asmaa_order_items';
            $products_table = $wpdb->prefix . 'asmaa_products';
            $inventory_movements_table = $wpdb->prefix . 'asmaa_inventory_movements';
            $created_order_items = [];

            foreach ($items as $item) {
                $item_type = !empty($item['product_id']) ? 'product' : 'service';
                $item_id = !empty($item['product_id']) ? (int) $item['product_id'] : (int) $item['service_id'];
                $quantity = (int) ($item['quantity'] ?? 1);
                $unit_price = (float) ($item['unit_price'] ?? 0);
                $item_total = $unit_price * $quantity;
                $staff_id = !empty($item['staff_id']) ? (int) $item['staff_id'] : null;
                $item_name = sanitize_text_field($item['name'] ?? 'Item');

                // Create order item
                $wpdb->insert($order_items_table, [
                    'order_id' => $order_id,
                    'item_type' => $item_type,
                    'item_id' => $item_id,
                    'item_name' => $item_name,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'discount' => 0,
                    'total' => $item_total,
                    'staff_id' => $staff_id,
                ]);
                if ($wpdb->last_error) {
                    throw new \Exception(__('Failed to create order item', 'asmaa-salon'));
                }

                $order_item_id = (int) $wpdb->insert_id;
                $created_order_items[] = [
                    'id' => $order_item_id,
                    'item_type' => $item_type,
                    'item_id' => $item_id,
                    'item_name' => $item_name,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'total' => $item_total,
                    'staff_id' => $staff_id,
                ];

                // Process inventory for products
                if (!empty($item['product_id'])) {
                    $product_id = (int) $item['product_id'];
                    
                    // Get current stock
                    $product = $wpdb->get_row($wpdb->prepare("SELECT stock_quantity FROM {$products_table} WHERE id = %d", $product_id));
                    if (!$product) {
                        throw new \Exception(__('Product not found', 'asmaa-salon'));
                    }

                    $before_quantity = (int) $product->stock_quantity;
                    $after_quantity = max(0, $before_quantity - $quantity);

                    if ($after_quantity < 0) {
                        throw new \Exception(__('Insufficient stock', 'asmaa-salon'));
                    }

                    // Update product stock
                    $wpdb->update($products_table, ['stock_quantity' => $after_quantity], ['id' => $product_id]);

                    // ✅ FIX: Check for low stock and send notification
                    $product_full = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$products_table} WHERE id = %d",
                        $product_id
                    ));
                    
                    $min_stock = (int) ($product_full->min_stock_level ?? 0);
                    // Send notification if stock reaches or falls below minimum (including 0)
                    if ($product_full && $after_quantity <= $min_stock && $before_quantity > $min_stock) {
                        // Only send if this is the first time crossing the threshold
                        \AsmaaSalon\Services\NotificationDispatcher::low_stock_alert($product_id, [
                            'name' => $product_full->name ?? $product_full->name_ar ?? 'Product',
                            'current_stock' => $after_quantity,
                            'min_stock_level' => $min_stock,
                            'sku' => $product_full->sku ?? '',
                        ]);
                    }

                    // Create inventory movement
                    $wpdb->insert($inventory_movements_table, [
                        'product_id' => $product_id,
                        'type' => 'sale',
                        'quantity' => -$quantity,
                        'before_quantity' => $before_quantity,
                        'after_quantity' => $after_quantity,
                        'unit_cost' => 0,
                        'total_cost' => 0,
                        'notes' => "POS Sale - Order #{$order_id}",
                        'performed_by' => get_current_user_id(),
                        'movement_date' => current_time('mysql'),
                    ]);
                    if ($wpdb->last_error) {
                        throw new \Exception(__('Failed to create inventory movement', 'asmaa-salon'));
                    }
                }
            }

            // Create Invoice
            $invoices_table = $wpdb->prefix . 'asmaa_invoices';
            $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$invoices_table}"), 4, '0', STR_PAD_LEFT);

            $invoice_data = [
                'order_id' => $order_id,
                'customer_id' => $customer_id,
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

            // ✅ FIX: Create Payment Record (CRITICAL!)
            $payments_table = $wpdb->prefix . 'asmaa_payments';
            $payment_number = 'PAY-' . date('Ymd') . '-' . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);
            
            $payment_data = [
                'payment_number' => $payment_number,
                'invoice_id' => $invoice_id,
                'customer_id' => $customer_id,
                'order_id' => $order_id,
                'amount' => $total,
                'payment_method' => $payment_method,
                'status' => 'completed',
                'payment_date' => current_time('mysql'),
                'notes' => 'POS Payment',
                'processed_by' => get_current_user_id(),
            ];

            $wpdb->insert($payments_table, $payment_data);
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create payment', 'asmaa-salon'));
            }
            $payment_id = $wpdb->insert_id;

            // Update invoice with payment_id
            $wpdb->update($invoices_table, ['payment_id' => $payment_id], ['id' => $invoice_id]);

            // Update POS Session
            $this->update_session($order_id, $total, $payment_method);

            // Process Loyalty Points (if customer exists)
            if ($customer_id) {
                $this->process_loyalty_points($customer_id, $order_id, $order_number, $created_order_items, $total);
            }

            // Process Commissions (if staff assigned)
            $this->process_commissions($order_id, $created_order_items);

            // Activity Log
            ActivityLogger::log_order('created', $order_id, $customer_id, [
                'status' => 'completed',
                'payment_status' => 'paid',
                'total' => $total,
                'items_count' => count($items),
                'pos' => true,
            ]);

            $wpdb->query('COMMIT');

            return $this->success_response([
                'order_id' => $order_id,
                'order_number' => $order_number,
                'invoice_id' => $invoice_id,
                'invoice_number' => $invoice_number,
                'payment_id' => $payment_id, // ✅ Include payment_id
                'payment_number' => $payment_number, // ✅ Include payment_number
                'customer_id' => $customer_id,
                'total' => $total,
            ], __('Order processed successfully', 'asmaa-salon'));

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
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $services_table = $wpdb->prefix . 'asmaa_services';
        $staff_table = $wpdb->prefix . 'asmaa_staff';

        $queue_customers = $wpdb->get_results(
            "SELECT q.*, c.name, c.phone, s.name as service_name, st.name as staff_name
             FROM {$queue_table} q
             LEFT JOIN {$customers_table} c ON q.customer_id = c.id
             LEFT JOIN {$services_table} s ON q.service_id = s.id
             LEFT JOIN {$staff_table} st ON q.staff_id = st.id
             WHERE DATE(q.created_at) = CURDATE()
             AND q.status IN ('waiting', 'called', 'serving')
             AND q.deleted_at IS NULL
             ORDER BY q.created_at ASC"
        );

        foreach ($queue_customers as $ticket) {
            if ($ticket->customer_id) {
                $active_customers[] = [
                    'id' => $ticket->customer_id,
                    'name' => !empty($ticket->name) ? $ticket->name : 'Walk-in Customer',
                    'phone' => $ticket->phone ?? '',
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
            "SELECT b.*, c.name, c.phone, s.name as service_name, st.name as staff_name
             FROM {$bookings_table} b
             LEFT JOIN {$customers_table} c ON b.customer_id = c.id
             LEFT JOIN {$services_table} s ON b.service_id = s.id
             LEFT JOIN {$staff_table} st ON b.staff_id = st.id
             WHERE b.booking_date = CURDATE()
             AND b.status IN ('pending', 'confirmed', 'in_progress')
             AND b.deleted_at IS NULL
             ORDER BY b.booking_time ASC"
        );

        foreach ($booking_customers as $booking) {
            if ($booking->customer_id) {
                $active_customers[] = [
                    'id' => $booking->customer_id,
                    'name' => !empty($booking->name) ? $booking->name : 'Walk-in Customer',
                    'phone' => $booking->phone ?? '',
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
    private function update_session(int $order_id, float $total, string $payment_method = 'cash'): void
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

        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';

        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT id, loyalty_points, total_visits, total_spent FROM {$customers_table} WHERE id = %d",
            $customer_id
        ));
        if (!$customer) {
            return;
        }

        // Always update customer totals for purchases
        $wpdb->query($wpdb->prepare(
            "UPDATE {$customers_table}
             SET total_visits = COALESCE(total_visits, 0) + 1,
                 total_spent = COALESCE(total_spent, 0) + %f
             WHERE id = %d",
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

        $balance_before = (int) ($customer->loyalty_points ?? 0);
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
                'customer_id' => $customer_id,
                'type' => 'earned',
                'points' => $points,
                'balance_before' => $balance_after,
                'balance_after' => $next_balance,
                'reference_type' => 'order_item',
                'reference_id' => (int) ($row['id'] ?? 0),
                'description' => $desc,
                'performed_by' => get_current_user_id(),
            ]);

            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create loyalty transaction', 'asmaa-salon'));
            }

            $balance_after = $next_balance;
        }

        if ($balance_after !== $balance_before) {
            $wpdb->update($customers_table, ['loyalty_points' => $balance_after], ['id' => $customer_id]);
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
                'staff_id' => $staff_id,
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
}
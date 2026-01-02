<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\ActivityLogger;
use AsmaaSalon\Services\Unified_Order_Service;

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
    public function get_initial_data(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        
        try {
            // Get active customers (from queue and bookings)
            $activeCustomers = $this->get_active_customers();
            
            // Get open session
            $session = $this->get_open_session();
            
            // Get popularity data
            $popularity = $this->get_items_popularity();
            
            return $this->success_response([
                'active_customers' => $activeCustomers,
                'open_session' => $session,
                'popularity' => $popularity,
            ]);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Process POS order using Unified Order Service
     */
    public function process_order(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            // Prepare parameters for unified service
            $params = [
                'customer_id' => $request->get_param('customer_id') ? (int) $request->get_param('customer_id') : null,
                'items' => $request->get_param('items'),
                'payment_method' => sanitize_text_field($request->get_param('payment_method')) ?: 'cash',
                'discount' => (float) ($request->get_param('discount') ?? 0),
                'discount_reason' => sanitize_text_field($request->get_param('discount_reason') ?? ''),
                'booking_id' => $request->get_param('booking_id') ? (int) $request->get_param('booking_id') : null,
                'queue_ticket_id' => $request->get_param('queue_ticket_id') ? (int) $request->get_param('queue_ticket_id') : null,
                'source' => 'pos',
            ];

            // Process order through unified service
            $result = Unified_Order_Service::process_order($params);

            // Update POS Session
            $this->update_session($result['wc_order_id'], $result['total'], $params['payment_method']);

            return $this->success_response([
                'order_id' => $result['wc_order_id'],
                'order_number' => $result['order_number'],
                'invoice_id' => $result['invoice_id'],
                'invoice_number' => $result['invoice_number'],
                'payment_id' => $result['payment_id'],
                'payment_number' => $result['payment_number'],
                'customer_id' => $result['customer_id'],
                'total' => $result['total'],
                'prepaid_amount' => $result['prepaid_amount'],
                'prepaid_orders_count' => $result['prepaid_orders_count'],
            ], __('Order processed successfully', 'asmaa-salon'), 201);
        } catch (\Throwable $e) {
            error_log('Asmaa Salon POS process_order error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get or create POS session
     */
    public function get_session(WP_REST_Request $request): WP_REST_Response|WP_Error
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
     * Get items popularity based on sales in the last 30 days
     */
    private function get_items_popularity(): array
    {
        global $wpdb;
        $order_items_table = $wpdb->prefix . 'asmaa_order_items';
        
        $results = $wpdb->get_results(
            "SELECT item_type, item_id, SUM(quantity) as total_sold
             FROM {$order_items_table}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY item_type, item_id
             ORDER BY total_sold DESC
             LIMIT 20"
        );
        
        $popularity = [
            'services' => [],
            'products' => []
        ];
        
        foreach ($results as $row) {
            if ($row->item_type === 'service') {
                $popularity['services'][] = (int) $row->item_id;
            } else {
                $popularity['products'][] = (int) $row->item_id;
            }
        }
        
        return $popularity;
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
            $paid = \AsmaaSalon\Services\Unified_Order_Service::get_total_paid_for_order($order->get_id());
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
                $order_paid = \AsmaaSalon\Services\Unified_Order_Service::get_total_paid_for_order($order->get_id());
                if ($order_paid >= (float) $order->get_total()) {
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
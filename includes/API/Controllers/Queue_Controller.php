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

class Queue_Controller extends Base_Controller
{
    protected string $rest_base = 'queue/tickets';

    public function register_routes(): void
    {
        // Support both /queue and /queue/tickets for backward compatibility
        $bases = ['queue', 'queue/tickets'];
        
        foreach ($bases as $base) {
            register_rest_route($this->namespace, '/' . $base, [
                ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_queue_view')],
                ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => $this->permission_callback('asmaa_queue_create')],
            ]);

            register_rest_route($this->namespace, '/' . $base . '/(?P<id>\d+)', [
                ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_queue_view')],
                ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => $this->permission_callback('asmaa_queue_update')],
                ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => $this->permission_callback('asmaa_queue_delete')],
            ]);

            // Custom endpoints
            register_rest_route($this->namespace, '/' . $base . '/next', [
                ['methods' => 'GET', 'callback' => [$this, 'get_next'], 'permission_callback' => $this->permission_callback('asmaa_queue_view')],
            ]);

            register_rest_route($this->namespace, '/' . $base . '/(?P<id>\d+)/call', [
                ['methods' => 'POST', 'callback' => [$this, 'call_ticket'], 'permission_callback' => $this->permission_callback('asmaa_queue_call')],
            ]);

            register_rest_route($this->namespace, '/' . $base . '/(?P<id>\d+)/start', [
                ['methods' => 'POST', 'callback' => [$this, 'start_serving'], 'permission_callback' => $this->permission_callback('asmaa_queue_start')],
            ]);

            register_rest_route($this->namespace, '/' . $base . '/(?P<id>\d+)/complete', [
                ['methods' => 'POST', 'callback' => [$this, 'complete_ticket'], 'permission_callback' => $this->permission_callback('asmaa_queue_complete')],
            ]);

            register_rest_route($this->namespace, '/' . $base . '/call-next', [
                ['methods' => 'POST', 'callback' => [$this, 'call_next'], 'permission_callback' => $this->permission_callback('asmaa_queue_call')],
            ]);

            register_rest_route($this->namespace, '/' . $base . '/(?P<id>\d+)/checkout', [
                ['methods' => 'POST', 'callback' => [$this, 'checkout_ticket'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
            ]);
        }
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $services_table = $wpdb->prefix . 'asmaa_services';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = ['q.deleted_at IS NULL'];
        
        $status = $request->get_param('status');
        if ($status) {
            $statuses = explode(',', $status);
            $where[] = "q.status IN ('" . implode("','", array_map('sanitize_text_field', $statuses)) . "')";
        }

        $status_not = $request->get_param('status_not');
        if ($status_not) {
            $statuses = explode(',', $status_not);
            $where[] = "q.status NOT IN ('" . implode("','", array_map('sanitize_text_field', $statuses)) . "')";
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} q {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, 
                    u.display_name as customer_name,
                    u.user_email as customer_email,
                    s.name as service_name, 
                    st.display_name as staff_name
             FROM {$table} q
             LEFT JOIN {$wpdb->users} u ON q.wc_customer_id = u.ID
             LEFT JOIN {$services_table} s ON q.service_id = s.id
             LEFT JOIN {$wpdb->users} st ON q.wp_user_id = st.ID
             {$where_clause} 
             ORDER BY q.created_at ASC 
             LIMIT %d OFFSET %d",
            $params['per_page'],
            $offset
        ));

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));

        if (!$item) {
            return $this->error_response(__('Queue ticket not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';

        // Generate ticket number
        $ticket_number = 'T-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$table}"), 4, '0', STR_PAD_LEFT);

        $booking_id = $request->get_param('booking_id') ? (int) $request->get_param('booking_id') : null;
        
        $data = [
            'ticket_number' => $ticket_number,
            'wc_customer_id' => $request->get_param('customer_id') ? (int) $request->get_param('customer_id') : null,
            'booking_id' => $booking_id,
            'service_id' => $request->get_param('service_id') ? (int) $request->get_param('service_id') : null,
            'wp_user_id' => $request->get_param('staff_id') ? (int) $request->get_param('staff_id') : null,
            'status' => 'waiting',
            'notes' => sanitize_textarea_field($request->get_param('notes')),
            'check_in_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return $this->error_response(__('Failed to create queue ticket', 'asmaa-salon'), 500);
        }

        $ticket_id = (int) $wpdb->insert_id;

        // Create initial worker call entry so that it appears immediately in Staff Room
        $worker_calls_table = $wpdb->prefix . 'asmaa_worker_calls';
        $staff_id           = $data['wp_user_id'] ?: null;

        // Get customer name for worker call
        $customer_name = null;
        if ($data['wc_customer_id']) {
            $user = get_user_by('ID', (int) $data['wc_customer_id']);
            $customer_name = $user ? ($user->display_name ?: $user->user_login) : null;
        }

        $wpdb->insert(
            $worker_calls_table,
            [
                'wp_user_id'      => $staff_id ?: 0,
                'ticket_id'     => $ticket_id,
                'customer_name' => $customer_name,
                'status'        => 'pending',
                'notes'         => $data['notes'],
            ]
        );

        // Link booking to queue ticket if booking_id is provided
        if ($booking_id) {
            $bookings_table = $wpdb->prefix . 'asmaa_bookings';
            $wpdb->update($bookings_table, ['queue_ticket_id' => $ticket_id], ['id' => (int) $booking_id]);
            
            // Update booking status to 'arrived' when converted to queue
            $wpdb->update($bookings_table, ['status' => 'arrived'], ['id' => (int) $booking_id]);
        }

        ActivityLogger::log_queue_ticket('created', $ticket_id, (int) ($data['wc_customer_id'] ?: 0), [
            'status' => $data['status'],
            'service_id' => $data['service_id'],
            'staff_id' => $data['wp_user_id'],
            'booking_id' => $booking_id ? (int) $booking_id : null,
        ]);

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $ticket_id));
        return $this->success_response($item, __('Queue ticket created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Queue ticket not found', 'asmaa-salon'), 404);
        }

        $data = [];
        if ($request->has_param('status')) {
            $data['status'] = sanitize_text_field($request->get_param('status'));
        }
        if ($request->has_param('notes')) {
            $data['notes'] = sanitize_textarea_field($request->get_param('notes'));
        }

        if (empty($data)) {
            return $this->error_response(__('No data to update', 'asmaa-salon'), 400);
        }

        $wpdb->update($table, $data, ['id' => $id]);
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        ActivityLogger::log_queue_ticket('updated', $id, (int) ($existing->wc_customer_id ?: 0), [
            'changed' => array_keys($data),
            'status' => $item->status ?? null,
        ]);

        return $this->success_response($item, __('Queue ticket updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Queue ticket not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);

        ActivityLogger::log_queue_ticket('deleted', $id, (int) ($existing->wc_customer_id ?: 0), [
            'soft_delete' => true,
        ]);

        return $this->success_response(null, __('Queue ticket deleted successfully', 'asmaa-salon'));
    }

    public function get_next(): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';

        $item = $wpdb->get_row(
            "SELECT * FROM {$table} WHERE status = 'waiting' AND deleted_at IS NULL ORDER BY created_at ASC LIMIT 1"
        );

        if (!$item) {
            return $this->error_response(__('No waiting tickets', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function call_ticket(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Queue ticket not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, [
            'status' => 'called',
            'called_at' => current_time('mysql'),
        ], ['id' => $id]);

        ActivityLogger::log_queue_ticket('called', $id, (int) ($existing->wc_customer_id ?: 0), [
            'called_at' => current_time('mysql'),
        ]);

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        return $this->success_response($item, __('Ticket called successfully', 'asmaa-salon'));
    }

    public function start_serving(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Queue ticket not found', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');
        $wpdb->update($table, [
            'status' => 'serving',
            'serving_started_at' => $now,
        ], ['id' => $id]);

        ActivityLogger::log_queue_ticket('serving_started', $id, (int) ($existing->wc_customer_id ?: 0), [
            'serving_started_at' => $now,
            'staff_id' => $existing->wp_user_id ?? null,
        ]);

        // Update related booking status to in_progress if linked
        if ($existing->booking_id ?? null) {
            $bookings_table = $wpdb->prefix . 'asmaa_bookings';
            $wpdb->update($bookings_table, ['status' => 'in_progress'], ['id' => (int) $existing->booking_id]);
        }

        // Update worker call status to accepted
        $worker_calls_table = $wpdb->prefix . 'asmaa_worker_calls';
        $wpdb->update($worker_calls_table, [
            'status' => 'accepted',
            'accepted_at' => $now,
        ], ['ticket_id' => $id, 'status' => 'staff_called']);

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        return $this->success_response($item, __('Service started successfully', 'asmaa-salon'));
    }

    public function complete_ticket(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Queue ticket not found', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');

        $wpdb->update($table, [
            'status'       => 'completed',
            'completed_at' => $now,
        ], ['id' => $id]);

        ActivityLogger::log_queue_ticket('completed', $id, (int) ($existing->wc_customer_id ?: 0), [
            'completed_at' => $now,
        ]);

        // Update related booking status to completed if linked
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$bookings_table} WHERE queue_ticket_id = %d AND deleted_at IS NULL",
            $id
        ));
        if ($booking) {
            $wpdb->update($bookings_table, [
                'status' => 'completed',
                'completed_at' => $now,
            ], ['id' => (int) $booking->id]);
        }

        // Update worker call status to completed
        $worker_calls_table = $wpdb->prefix . 'asmaa_worker_calls';
        $wpdb->update($worker_calls_table, [
            'status' => 'completed',
            'completed_at' => $now,
        ], ['ticket_id' => $id]);

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        return $this->success_response($item, __('Ticket completed successfully', 'asmaa-salon'));
    }

    /**
     * Call the next waiting ticket (combines get_next + call_ticket logic).
     */
    public function call_next(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_queue_tickets';
        $worker_calls_table = $wpdb->prefix . 'asmaa_worker_calls';

        // Get the next waiting ticket
        $ticket = $wpdb->get_row(
            "SELECT * FROM {$table} WHERE status = 'waiting' AND deleted_at IS NULL ORDER BY created_at ASC LIMIT 1"
        );

        if (!$ticket) {
            return $this->error_response(__('No waiting tickets', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');
        $ticket_id = (int) $ticket->id;

        // Update ticket status to called
        $wpdb->update($table, [
            'status'    => 'called',
            'called_at' => $now,
        ], ['id' => $ticket_id]);

        ActivityLogger::log_queue_ticket('called_next', $ticket_id, (int) ($ticket->wc_customer_id ?: 0), [
            'called_at' => $now,
            'staff_id' => $ticket->wp_user_id ?: null,
        ]);

        // Update or create worker call
        $worker_call = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$worker_calls_table} WHERE ticket_id = %d",
            $ticket_id
        ));

        if ($worker_call) {
            $wpdb->update($worker_calls_table, [
                'status'    => 'customer_called',
                'called_at' => $now,
            ], ['id' => (int) $worker_call->id]);
        } else {
            // Get customer name
            $customer_name = null;
            if ($ticket->wc_customer_id) {
                $user = get_user_by('ID', (int) $ticket->wc_customer_id);
                $customer_name = $user ? ($user->display_name ?: $user->user_login) : null;
            }

            $wpdb->insert($worker_calls_table, [
                'wp_user_id'      => $ticket->wp_user_id ?: 0,
                'ticket_id'     => $ticket_id,
                'customer_name' => $customer_name,
                'status'        => 'customer_called',
                'called_at'      => $now,
            ]);
        }

        $updated_ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $ticket_id));
        return $this->success_response($updated_ticket, __('Next ticket called successfully', 'asmaa-salon'));
    }

    /**
     * Create queue ticket from booking
     */
    public function create_from_booking(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $booking_id = (int) $request->get_param('booking_id');

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$bookings_table} WHERE id = %d AND deleted_at IS NULL",
            $booking_id
        ));

        if (!$booking) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        // Check if already converted
        if ($booking->queue_ticket_id) {
            return $this->error_response(__('Booking already converted to queue ticket', 'asmaa-salon'), 400);
        }

        // Create queue ticket with booking data
        $ticket_request = new WP_REST_Request('POST', $this->namespace . '/queue');
        $ticket_request->set_param('customer_id', (int) $booking->wc_customer_id);
        $ticket_request->set_param('booking_id', $booking_id);
        $ticket_request->set_param('service_id', (int) $booking->service_id);
        $ticket_request->set_param('staff_id', $booking->wp_user_id ? (int) $booking->wp_user_id : null);
        $ticket_request->set_param('notes', sprintf(__('Converted from booking #%d', 'asmaa-salon'), $booking_id));

        $response = $this->create_item($ticket_request);

        if (!is_wp_error($response)) {
            $response_data = $response->get_data();
            $ticket_id = $response_data['data']->id ?? null;

            if ($ticket_id) {
                // Update booking with queue_ticket_id and status
                $wpdb->update(
                    $bookings_table,
                    [
                        'queue_ticket_id' => (int) $ticket_id,
                        'status' => 'arrived',
                    ],
                    ['id' => $booking_id]
                );
            }
        }

        return $response;
    }

    /**
     * Checkout queue ticket - Create order from queue ticket
     */
    public function checkout_ticket(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $queue_ticket_id = (int) $request->get_param('id');
        $additional_items = $request->get_param('additional_items') ?: [];
        $payment_method = sanitize_text_field($request->get_param('payment_method')) ?: 'cash';

        try {
            $result = Unified_Order_Service::create_from_queue($queue_ticket_id, $additional_items, $payment_method);

            return $this->success_response([
                'order_id' => $result['wc_order_id'],
                'order_number' => $result['order_number'],
                'invoice_id' => $result['invoice_id'],
                'invoice_number' => $result['invoice_number'],
                'payment_id' => $result['payment_id'],
                'payment_number' => $result['payment_number'],
                'total' => $result['total'],
            ], __('Queue ticket checkout completed successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }
}

<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\ActivityLogger;
use AsmaaSalon\Services\NotificationDispatcher;
use AsmaaSalon\Services\Unified_Order_Service;

if (!defined('ABSPATH')) {
    exit;
}

class Bookings_Controller extends Base_Controller
{
    protected string $rest_base = 'bookings';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_bookings_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => $this->permission_callback('asmaa_bookings_create')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_bookings_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => $this->permission_callback('asmaa_bookings_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => $this->permission_callback('asmaa_bookings_delete')],
        ]);

        // Custom endpoints
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/confirm', [
            ['methods' => 'POST', 'callback' => [$this, 'confirm_booking'], 'permission_callback' => $this->permission_callback('asmaa_bookings_confirm')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/cancel', [
            ['methods' => 'POST', 'callback' => [$this, 'cancel_booking'], 'permission_callback' => $this->permission_callback('asmaa_bookings_cancel')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/complete', [
            ['methods' => 'POST', 'callback' => [$this, 'complete_booking'], 'permission_callback' => $this->permission_callback('asmaa_bookings_complete')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/start', [
            ['methods' => 'POST', 'callback' => [$this, 'start_booking'], 'permission_callback' => $this->permission_callback('asmaa_bookings_update')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/convert-to-queue', [
            ['methods' => 'POST', 'callback' => [$this, 'convert_to_queue'], 'permission_callback' => $this->permission_callback('asmaa_bookings_update')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/checkout', [
            ['methods' => 'POST', 'callback' => [$this, 'checkout_booking'], 'permission_callback' => $this->permission_callback('asmaa_pos_use')],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';
        $services_table = $wpdb->prefix . 'asmaa_services';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = ['b.deleted_at IS NULL'];
        
        $booking_date = $request->get_param('booking_date');
        if ($booking_date) {
            $where[] = $wpdb->prepare('b.booking_date = %s', $booking_date);
        }

        $status = $request->get_param('status');
        if ($status) {
            $where[] = $wpdb->prepare('b.status = %s', $status);
        }

        $customer_id = $request->get_param('customer_id');
        if ($customer_id) {
            $where[] = $wpdb->prepare('b.wc_customer_id = %d', $customer_id);
        }

        $staff_id = $request->get_param('staff_id');
        if ($staff_id) {
            $where[] = $wpdb->prepare('b.wp_user_id = %d', $staff_id);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} b {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, 
                    u.display_name as customer_name,
                    u.user_email as customer_email,
                    s.name as service_name,
                    st.display_name as staff_name
             FROM {$table} b
             LEFT JOIN {$wpdb->users} u ON b.wc_customer_id = u.ID
             LEFT JOIN {$services_table} s ON b.service_id = s.id
             LEFT JOIN {$wpdb->users} st ON b.wp_user_id = st.ID
             {$where_clause} 
             ORDER BY b.booking_date DESC, b.booking_time DESC 
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
        $table = $wpdb->prefix . 'asmaa_bookings';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));

        if (!$item) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';

        $data = [
            'wc_customer_id' => (int) $request->get_param('customer_id'),
            'wp_user_id' => $request->get_param('staff_id') ? (int) $request->get_param('staff_id') : null,
            'service_id' => (int) $request->get_param('service_id'),
            'booking_date' => sanitize_text_field($request->get_param('booking_date')),
            'booking_time' => sanitize_text_field($request->get_param('booking_time')),
            'end_time' => sanitize_text_field($request->get_param('end_time')),
            'status' => sanitize_text_field($request->get_param('status')) ?: 'pending',
            'price' => (float) $request->get_param('price'),
            'discount' => (float) $request->get_param('discount'),
            'final_price' => (float) $request->get_param('final_price'),
            'notes' => sanitize_textarea_field($request->get_param('notes')),
            'source' => sanitize_text_field($request->get_param('source')),
        ];

        if (empty($data['wc_customer_id']) || empty($data['service_id']) || empty($data['booking_date']) || empty($data['booking_time'])) {
            return $this->error_response(__('Customer, service, date and time are required', 'asmaa-salon'), 400);
        }

        // Check for conflicts
        $conflict = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE wp_user_id = %d AND booking_date = %s AND booking_time = %s AND status NOT IN ('cancelled', 'no_show') AND deleted_at IS NULL",
            $data['wp_user_id'] ?: 0,
            $data['booking_date'],
            $data['booking_time']
        ));

        if ($conflict) {
            return $this->error_response(__('Time slot is already booked', 'asmaa-salon'), 400);
        }

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return $this->error_response(__('Failed to create booking', 'asmaa-salon'), 500);
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id));
        
        // Log activity
        ActivityLogger::log_booking('created', $wpdb->insert_id, $data['wc_customer_id'], ['status' => $data['status']]);

        // Dashboard notification (admins)
        NotificationDispatcher::dashboard_admins('Dashboard.BookingCreated', [
            'event' => 'booking.created',
            'booking_id' => (int) $wpdb->insert_id,
            'customer_id' => (int) $data['wc_customer_id'],
            'staff_id' => $data['wp_user_id'] ? (int) $data['wp_user_id'] : null,
            'booking_date' => $data['booking_date'],
            'booking_time' => $data['booking_time'],
            'title_en' => 'New booking created',
            'message_en' => sprintf('Booking #%d created for %s %s', (int) $wpdb->insert_id, (string) $data['booking_date'], (string) $data['booking_time']),
            'title_ar' => '📅 تم إنشاء حجز جديد',
            'message_ar' => sprintf('تم إنشاء حجز رقم #%d بتاريخ %s الساعة %s', (int) $wpdb->insert_id, (string) $data['booking_date'], (string) $data['booking_time']),
            'action' => [
                'route' => '/bookings',
            ],
            'severity' => 'info',
        ]);
        
        // Send booking confirmation notification
        if ($data['status'] === 'confirmed') {
            NotificationDispatcher::booking_confirmation($data['wc_customer_id'], [
                'booking_id' => $wpdb->insert_id,
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
            ]);
        }
        
        return $this->success_response($item, __('Booking created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        $data = [];
        $fields = ['customer_id', 'staff_id', 'service_id', 'booking_date', 'booking_time', 'end_time', 'status', 'price', 'discount', 'final_price', 'notes', 'source'];

        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if ($field === 'customer_id') {
                    $data['wc_customer_id'] = $request->get_param($field) ? (int) $request->get_param($field) : null;
                } elseif ($field === 'staff_id') {
                    $data['wp_user_id'] = $request->get_param($field) ? (int) $request->get_param($field) : null;
                } elseif ($field === 'service_id') {
                    $data[$field] = $request->get_param($field) ? (int) $request->get_param($field) : null;
                } elseif (in_array($field, ['price', 'discount', 'final_price'])) {
                    $data[$field] = (float) $request->get_param($field);
                } elseif ($field === 'notes') {
                    $data[$field] = sanitize_textarea_field($request->get_param($field));
                } else {
                    $data[$field] = sanitize_text_field($request->get_param($field));
                }
            }
        }

        if (empty($data)) {
            return $this->error_response(__('No data to update', 'asmaa-salon'), 400);
        }

        $wpdb->update($table, $data, ['id' => $id]);
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        return $this->success_response($item, __('Booking updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
        return $this->success_response(null, __('Booking deleted successfully', 'asmaa-salon'));
    }

    public function confirm_booking(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');
        $wpdb->update($table, [
            'status' => 'confirmed',
            'confirmed_at' => $now,
        ], ['id' => $id]);

        // Update customer last_visit_at in extended data
        if ($existing->wc_customer_id) {
            $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$extended_table} (wc_customer_id, last_visit_at) VALUES (%d, %s)
                 ON DUPLICATE KEY UPDATE last_visit_at = %s",
                (int) $existing->wc_customer_id,
                $now,
                $now
            ));
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        // Log activity
        ActivityLogger::log_booking('confirmed', $id, (int) $existing->wc_customer_id, ['confirmed_at' => $now]);

        // Dashboard notification (admins)
        NotificationDispatcher::dashboard_admins('Dashboard.BookingConfirmed', [
            'event' => 'booking.confirmed',
            'booking_id' => (int) $id,
            'customer_id' => (int) $existing->wc_customer_id,
            'staff_id' => $existing->wp_user_id ? (int) $existing->wp_user_id : null,
            'booking_date' => (string) $existing->booking_date,
            'booking_time' => (string) $existing->booking_time,
            'title_en' => 'Booking confirmed',
            'message_en' => sprintf('Booking #%d confirmed', (int) $id),
            'title_ar' => '✅ تم تأكيد الحجز',
            'message_ar' => sprintf('تم تأكيد الحجز رقم #%d', (int) $id),
            'action' => [
                'route' => '/bookings',
            ],
            'severity' => 'success',
        ]);
        
        // Send confirmation notification
        NotificationDispatcher::booking_confirmation((int) $existing->wc_customer_id, [
            'booking_id' => $id,
            'booking_date' => $existing->booking_date,
            'booking_time' => $existing->booking_time,
        ]);

        return $this->success_response($item, __('Booking confirmed successfully', 'asmaa-salon'));
    }

    public function cancel_booking(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['status' => 'cancelled'], ['id' => $id]);
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        // Dashboard notification (admins)
        NotificationDispatcher::dashboard_admins('Dashboard.BookingCancelled', [
            'event' => 'booking.cancelled',
            'booking_id' => (int) $id,
            'customer_id' => (int) $existing->wc_customer_id,
            'staff_id' => $existing->wp_user_id ? (int) $existing->wp_user_id : null,
            'booking_date' => (string) $existing->booking_date,
            'booking_time' => (string) $existing->booking_time,
            'title_en' => 'Booking cancelled',
            'message_en' => sprintf('Booking #%d cancelled', (int) $id),
            'title_ar' => '❌ تم إلغاء الحجز',
            'message_ar' => sprintf('تم إلغاء الحجز رقم #%d', (int) $id),
            'action' => [
                'route' => '/bookings',
            ],
            'severity' => 'warning',
        ]);

        return $this->success_response($item, __('Booking cancelled successfully', 'asmaa-salon'));
    }

    public function complete_booking(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');
        $wpdb->update($table, [
            'status' => 'completed',
            'completed_at' => $now,
        ], ['id' => $id]);

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        // Log activity
        ActivityLogger::log_booking('completed', $id, (int) $existing->wc_customer_id, ['completed_at' => $now]);
        
        // Send post-visit thank you notification
        NotificationDispatcher::post_visit_thank_you((int) $existing->wc_customer_id, [
            'booking_id' => $id,
            'service_name' => 'Service', // Could fetch from service_id
        ]);

        return $this->success_response($item, __('Booking completed successfully', 'asmaa-salon'));
    }

    public function start_booking(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['status' => 'in_progress'], ['id' => $id]);
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        return $this->success_response($item, __('Booking started successfully', 'asmaa-salon'));
    }

    /**
     * Convert booking to queue ticket
     */
    public function convert_to_queue(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_bookings';
        $id = (int) $request->get_param('id');

        $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$booking) {
            return $this->error_response(__('Booking not found', 'asmaa-salon'), 404);
        }

        // Check if already converted
        if ($booking->queue_ticket_id) {
            return $this->error_response(__('Booking already converted to queue ticket', 'asmaa-salon'), 400);
        }

        // Create queue ticket via Queue Controller
        $queue_controller = new \AsmaaSalon\API\Controllers\Queue_Controller();
        $queue_request = new WP_REST_Request('POST', '/asmaa-salon/v1/queue');
        $queue_request->set_param('customer_id', (int) $booking->wc_customer_id);
        $queue_request->set_param('booking_id', $id);
        $queue_request->set_param('service_id', (int) $booking->service_id);
        $queue_request->set_param('staff_id', $booking->wp_user_id ? (int) $booking->wp_user_id : null);
        $queue_request->set_param('notes', sprintf(__('Converted from booking #%d', 'asmaa-salon'), $id));

        $queue_response = $queue_controller->create_item($queue_request);

        if (is_wp_error($queue_response)) {
            return $queue_response;
        }

        $queue_data = $queue_response->get_data();
        $queue_ticket_id = $queue_data['data']->id ?? null;

        if ($queue_ticket_id) {
            // Update booking with queue_ticket_id and status
            $wpdb->update(
                $table,
                [
                    'queue_ticket_id' => (int) $queue_ticket_id,
                    'status' => 'arrived',
                ],
                ['id' => $id]
            );

            ActivityLogger::log_booking('converted_to_queue', $id, (int) $booking->wc_customer_id, [
                'queue_ticket_id' => (int) $queue_ticket_id,
            ]);
        }

        $updated_booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        return $this->success_response($updated_booking, __('Booking converted to queue ticket successfully', 'asmaa-salon'));
    }

    /**
     * Checkout booking - Create order from booking
     */
    public function checkout_booking(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $booking_id = (int) $request->get_param('id');
        $additional_items = $request->get_param('additional_items') ?: [];
        $payment_method = sanitize_text_field($request->get_param('payment_method')) ?: 'cash';

        try {
            $result = Unified_Order_Service::create_from_booking($booking_id, $additional_items, $payment_method);

            return $this->success_response([
                'order_id' => $result['wc_order_id'],
                'order_number' => $result['order_number'],
                'invoice_id' => $result['invoice_id'],
                'invoice_number' => $result['invoice_number'],
                'payment_id' => $result['payment_id'],
                'payment_number' => $result['payment_number'],
                'total' => $result['total'],
            ], __('Booking checkout completed successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }
}

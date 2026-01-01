<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\NotificationDispatcher;

if (!defined('ABSPATH')) {
    exit;
}

class Worker_Calls_Controller extends Base_Controller
{
    protected string $rest_base = 'worker-calls';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_items'],
                'permission_callback' => $this->permission_callback('asmaa_worker_calls_view'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/call-customer', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'call_customer'],
                'permission_callback' => $this->permission_callback('asmaa_worker_calls_update'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/call-staff', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'call_staff'],
                'permission_callback' => $this->permission_callback('asmaa_worker_calls_update'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/complete', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'complete_call'],
                'permission_callback' => $this->permission_callback('asmaa_worker_calls_update'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/accept', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'accept_call'],
                'permission_callback' => $this->permission_callback('asmaa_worker_calls_update'),
            ],
        ]);
    }

    /**
     * Get worker calls with related queue / staff / customer / service info.
     */
    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $table_calls     = $wpdb->prefix . 'asmaa_worker_calls';
        $table_queue     = $wpdb->prefix . 'asmaa_queue_tickets';
        $table_services  = $wpdb->prefix . 'asmaa_services';

        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where   = ['w.created_at IS NOT NULL'];
        $status  = $request->get_param('status');
        $staffId = $request->get_param('staff_id');
        $date    = $request->get_param('date');

        // Filter by active statuses if 'active' is requested
        if ($status === 'active') {
            $where[] = "w.status IN ('pending', 'staff_called', 'customer_called', 'accepted')";
        } elseif ($status) {
            $where[] = $wpdb->prepare('w.status = %s', $status);
        }

        if ($staffId) {
            $where[] = $wpdb->prepare('w.wp_user_id = %d', (int) $staffId);
        }

        // By default limit to today (salon daily board)
        if ($date) {
            $where[] = $wpdb->prepare('DATE(w.created_at) = %s', $date);
        } else {
            $where[] = $wpdb->prepare('DATE(w.created_at) = %s', current_time('Y-m-d'));
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_calls} w {$whereClause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                w.*,
                q.ticket_number,
                q.status        AS queue_status,
                u.display_name AS customer_name,
                u.user_email   AS customer_email,
                s.name          AS service_name,
                st.display_name AS staff_name,
                COALESCE(ext.chair_number, 0) AS staff_chair_number,
                ext.position     AS staff_position,
                ext.rating       AS staff_rating,
                ext.total_services AS staff_total_services
             FROM {$table_calls} w
             LEFT JOIN {$table_queue} q ON w.ticket_id = q.id
             LEFT JOIN {$wpdb->users} u ON q.wc_customer_id = u.ID
             LEFT JOIN {$table_services} s ON q.service_id = s.id
             LEFT JOIN {$wpdb->users} st ON w.wp_user_id = st.ID
             LEFT JOIN {$wpdb->prefix}asmaa_staff_extended_data ext ON ext.wp_user_id = w.wp_user_id
             {$whereClause}
             ORDER BY COALESCE(w.called_at, w.created_at) DESC, w.created_at DESC
             LIMIT %d OFFSET %d",
            $params['per_page'],
            $offset
        ));

        return $this->success_response([
            'items'      => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    /**
     * Call customer for a specific worker call (updates worker call + queue ticket).
     */
    public function call_customer(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $table_calls = $wpdb->prefix . 'asmaa_worker_calls';
        $table_queue = $wpdb->prefix . 'asmaa_queue_tickets';
        $id          = (int) $request->get_param('id');

        $call = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_calls} WHERE id = %d",
            $id
        ));

        if (!$call) {
            return $this->error_response(__('Worker call not found', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');

        $wpdb->update(
            $table_calls,
            [
                'status'    => 'customer_called',
                'called_at' => $now,
            ],
            ['id' => $id]
        );

        if (!empty($call->ticket_id)) {
            $wpdb->update(
                $table_queue,
                [
                    'status'    => 'called',
                    'called_at' => $now,
                ],
                ['id' => (int) $call->ticket_id]
            );
        }

        $updated = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_calls} WHERE id = %d",
            $id
        ));

        // Dashboard notification (admins)
        NotificationDispatcher::dashboard_admins('Dashboard.WorkerCallCustomerCalled', [
            'event' => 'worker_call.customer_called',
            'worker_call_id' => (int) $id,
            'staff_id' => $call->wp_user_id ? (int) $call->wp_user_id : null,
            'ticket_id' => $call->ticket_id ? (int) $call->ticket_id : null,
            'title_en' => 'Customer called',
            'message_en' => sprintf('Worker call #%d: customer called', (int) $id),
            'title_ar' => '📣 تم نداء العميل',
            'message_ar' => sprintf('تم نداء العميل (استدعاء رقم #%d)', (int) $id),
            'action' => [
                'route' => '/worker-calls',
            ],
            'severity' => 'info',
        ]);

        return $this->success_response($updated, __('Customer called successfully', 'asmaa-salon'));
    }

    /**
     * Call staff (notify staff screen that staff is needed).
     */
    public function call_staff(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $table_calls = $wpdb->prefix . 'asmaa_worker_calls';
        $id          = (int) $request->get_param('id');

        $call = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_calls} WHERE id = %d",
            $id
        ));

        if (!$call) {
            return $this->error_response(__('Worker call not found', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');

        $wpdb->update(
            $table_calls,
            [
                'status' => 'staff_called',
            ],
            ['id' => $id]
        );

        $updated = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_calls} WHERE id = %d",
            $id
        ));

        // Dashboard notification (admins)
        NotificationDispatcher::dashboard_admins('Dashboard.WorkerCallStaffCalled', [
            'event' => 'worker_call.staff_called',
            'worker_call_id' => (int) $id,
            'staff_id' => $call->wp_user_id ? (int) $call->wp_user_id : null,
            'ticket_id' => $call->ticket_id ? (int) $call->ticket_id : null,
            'title_en' => 'Staff called',
            'message_en' => sprintf('Worker call #%d: staff called', (int) $id),
            'title_ar' => '🔔 تم استدعاء موظف',
            'message_ar' => sprintf('تم استدعاء موظف (استدعاء رقم #%d)', (int) $id),
            'action' => [
                'route' => '/worker-calls',
            ],
            'severity' => 'warning',
        ]);

        return $this->success_response($updated, __('Staff called successfully', 'asmaa-salon'));
    }

    /**
     * Accept worker call (staff accepts the call).
     */
    public function accept_call(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $table_calls = $wpdb->prefix . 'asmaa_worker_calls';
        $table_queue = $wpdb->prefix . 'asmaa_queue_tickets';
        $id          = (int) $request->get_param('id');

        $call = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_calls} WHERE id = %d",
            $id
        ));

        if (!$call) {
            return $this->error_response(__('Worker call not found', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');

        $wpdb->update(
            $table_calls,
            [
                'status'      => 'accepted',
                'accepted_at' => $now,
            ],
            ['id' => $id]
        );

        // Update queue ticket to serving if linked
        if (!empty($call->ticket_id)) {
            $wpdb->update(
                $table_queue,
                [
                    'status'            => 'serving',
                    'serving_started_at' => $now,
                ],
                ['id' => (int) $call->ticket_id]
            );
        }

        $updated = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_calls} WHERE id = %d",
            $id
        ));

        return $this->success_response($updated, __('Worker call accepted successfully', 'asmaa-salon'));
    }

    /**
     * Mark worker call as completed.
     */
    public function complete_call(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $table_calls = $wpdb->prefix . 'asmaa_worker_calls';
        $id          = (int) $request->get_param('id');

        $call = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_calls} WHERE id = %d",
            $id
        ));

        if (!$call) {
            return $this->error_response(__('Worker call not found', 'asmaa-salon'), 404);
        }

        $now = current_time('mysql');

        $wpdb->update(
            $table_calls,
            [
                'status'       => 'completed',
                'completed_at' => $now,
            ],
            ['id' => $id]
        );

        $updated = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_calls} WHERE id = %d",
            $id
        ));

        return $this->success_response($updated, __('Worker call completed successfully', 'asmaa-salon'));
    }
}


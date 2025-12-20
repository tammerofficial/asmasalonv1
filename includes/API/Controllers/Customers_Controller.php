<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;

use WP_Error;
if (!defined('ABSPATH')) {
    exit;
}

class Customers_Controller extends Base_Controller
{
    protected string $rest_base = 'customers';

    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_items'],
                    'permission_callback' => $this->permission_callback('asmaa_customers_view'),
                ],
                [
                    'methods'             => 'POST',
                    'callback'            => [$this, 'create_item'],
                    'permission_callback' => $this->permission_callback('asmaa_customers_create'),
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)',
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_item'],
                    'permission_callback' => $this->permission_callback('asmaa_customers_view'),
                ],
                [
                    'methods'             => 'PUT',
                    'callback'            => [$this, 'update_item'],
                    'permission_callback' => $this->permission_callback('asmaa_customers_update'),
                ],
                [
                    'methods'             => 'DELETE',
                    'callback'            => [$this, 'delete_item'],
                    'permission_callback' => $this->permission_callback('asmaa_customers_delete'),
                ],
            ]
        );

        // Customer profile (today's purchases + loyalty)
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)/profile',
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_profile'],
                    'permission_callback' => $this->permission_callback('asmaa_customers_view'),
                ],
            ]
        );
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customers';

        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        // Build WHERE clause
        $where = ['deleted_at IS NULL'];
        $search = $request->get_param('search');
        if ($search) {
            $where[] = $wpdb->prepare(
                '(name LIKE %s OR phone LIKE %s OR email LIKE %s)',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $status = $request->get_param('status');
        if ($status === 'active') {
            $where[] = 'is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'is_active = 0';
        }

        $gender = $request->get_param('gender');
        if ($gender) {
            $where[] = $wpdb->prepare('gender = %s', $gender);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);

        // Get total count
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");

        // Get items
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} {$where_clause} ORDER BY id DESC LIMIT %d OFFSET %d",
                $params['per_page'],
                $offset
            )
        );

        return $this->success_response([
            'items'      => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customers';
        $id    = (int) $request->get_param('id');

        $item = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id)
        );

        if (!$item) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function get_profile(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $orders_table = $wpdb->prefix . 'asmaa_orders';
        $order_items_table = $wpdb->prefix . 'asmaa_order_items';
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $loyalty_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';

        $id = (int) $request->get_param('id');
        $customer = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$customers_table} WHERE id = %d AND deleted_at IS NULL", $id)
        );

        if (!$customer) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }

        $today = date('Y-m-d');

        $today_orders = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, i.id AS invoice_id, i.invoice_number
             FROM {$orders_table} o
             LEFT JOIN {$invoices_table} i ON i.order_id = o.id
             WHERE o.customer_id = %d
               AND DATE(o.created_at) = %s
               AND o.deleted_at IS NULL
             ORDER BY o.created_at DESC",
            $id,
            $today
        ));

        $order_ids = array_map(static fn($o) => (int) $o->id, $today_orders ?: []);
        $today_items = [];
        if (!empty($order_ids)) {
            $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));
            $sql = "SELECT oi.*
                    FROM {$order_items_table} oi
                    WHERE oi.order_id IN ({$placeholders})
                    ORDER BY oi.created_at DESC";
            $today_items = $wpdb->get_results($wpdb->prepare($sql, ...$order_ids));
        }

        $today_total = 0.0;
        foreach ($today_orders as $o) {
            $today_total += (float) ($o->total ?? 0);
        }

        $today_loyalty = $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM {$loyalty_table}
             WHERE customer_id = %d
               AND DATE(created_at) = %s
             ORDER BY created_at DESC",
            $id,
            $today
        ));

        $today_points = 0;
        foreach ($today_loyalty as $tx) {
            if ((string) ($tx->type ?? '') === 'earned') {
                $today_points += (int) ($tx->points ?? 0);
            }
        }

        // Current membership (if any)
        $current_membership = $wpdb->get_row($wpdb->prepare(
            "SELECT
                m.*,
                p.name AS plan_name,
                p.name_ar AS plan_name_ar,
                p.price AS plan_price,
                p.duration_months AS plan_duration_months,
                p.discount_percentage,
                p.free_services_count AS services_limit,
                p.points_multiplier,
                p.priority_booking
             FROM {$memberships_table} m
             LEFT JOIN {$plans_table} p ON p.id = m.membership_plan_id
             WHERE m.customer_id = %d
             ORDER BY (m.status = 'active') DESC, m.end_date DESC, m.id DESC
             LIMIT 1",
            $id
        ));

        $membership_history = $wpdb->get_results($wpdb->prepare(
            "SELECT
                m.*,
                p.name AS plan_name,
                p.name_ar AS plan_name_ar,
                p.price AS plan_price,
                p.duration_months AS plan_duration_months,
                p.discount_percentage,
                p.free_services_count AS services_limit,
                p.points_multiplier,
                p.priority_booking
             FROM {$memberships_table} m
             LEFT JOIN {$plans_table} p ON p.id = m.membership_plan_id
             WHERE m.customer_id = %d
             ORDER BY m.id DESC
             LIMIT 10",
            $id
        ));

        return $this->success_response([
            'customer' => $customer,
            'today' => [
                'date' => $today,
                'orders' => $today_orders,
                'items' => $today_items,
                'total_amount' => round($today_total, 3),
                'points_earned' => $today_points,
            ],
            'membership' => [
                'current' => $current_membership,
                'history' => $membership_history,
            ],
            'loyalty' => [
                'balance' => (int) ($customer->loyalty_points ?? 0),
                'today_transactions' => $today_loyalty,
            ],
        ]);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customers';

        $data = [
            'name'              => sanitize_text_field($request->get_param('name')),
            'phone'             => sanitize_text_field($request->get_param('phone')),
            'email'             => sanitize_email($request->get_param('email')),
            'address'           => sanitize_textarea_field($request->get_param('address')),
            'city'              => sanitize_text_field($request->get_param('city')),
            'date_of_birth'     => $request->get_param('date_of_birth') ?: null,
            'gender'            => sanitize_text_field($request->get_param('gender')),
            'notes'             => sanitize_textarea_field($request->get_param('notes')),
            'preferred_staff_id' => $request->get_param('preferred_staff_id') ? (int) $request->get_param('preferred_staff_id') : null,
            'is_active'         => $request->get_param('is_active') ? 1 : 0,
        ];

        // Validate required fields
        if (empty($data['name']) || empty($data['phone'])) {
            return $this->error_response(__('Name and phone are required', 'asmaa-salon'), 400);
        }

        // Check if phone already exists
        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE phone = %s AND deleted_at IS NULL", $data['phone'])
        );
        if ($existing) {
            return $this->error_response(__('Phone number already exists', 'asmaa-salon'), 400);
        }

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return $this->error_response(__('Failed to create customer', 'asmaa-salon'), 500);
        }

        $id = $wpdb->insert_id;
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        return $this->success_response($item, __('Customer created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customers';
        $id    = (int) $request->get_param('id');

        // Check if exists
        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id)
        );
        if (!$existing) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }

        $data = [];
        $fields = ['name', 'phone', 'email', 'address', 'city', 'date_of_birth', 'gender', 'notes', 'preferred_staff_id', 'is_active'];

        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if ($field === 'phone') {
                    $data[$field] = sanitize_text_field($request->get_param($field));
                } elseif ($field === 'email') {
                    $data[$field] = sanitize_email($request->get_param($field));
                } elseif ($field === 'address' || $field === 'notes') {
                    $data[$field] = sanitize_textarea_field($request->get_param($field));
                } elseif ($field === 'is_active') {
                    $data[$field] = $request->get_param($field) ? 1 : 0;
                } elseif ($field === 'preferred_staff_id') {
                    $data[$field] = $request->get_param($field) ? (int) $request->get_param($field) : null;
                } else {
                    $data[$field] = sanitize_text_field($request->get_param($field));
                }
            }
        }

        // Check phone uniqueness if changed
        if (isset($data['phone']) && $data['phone'] !== $existing->phone) {
            $duplicate = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM {$table} WHERE phone = %s AND id != %d AND deleted_at IS NULL", $data['phone'], $id)
            );
            if ($duplicate) {
                return $this->error_response(__('Phone number already exists', 'asmaa-salon'), 400);
            }
        }

        if (empty($data)) {
            return $this->error_response(__('No data to update', 'asmaa-salon'), 400);
        }

        $result = $wpdb->update($table, $data, ['id' => $id]);

        if ($result === false) {
            return $this->error_response(__('Failed to update customer', 'asmaa-salon'), 500);
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        return $this->success_response($item, __('Customer updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customers';
        $id    = (int) $request->get_param('id');

        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id)
        );
        if (!$existing) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }

        // Soft delete
        $result = $wpdb->update(
            $table,
            ['deleted_at' => current_time('mysql')],
            ['id' => $id]
        );

        if ($result === false) {
            return $this->error_response(__('Failed to delete customer', 'asmaa-salon'), 500);
        }

        return $this->success_response(null, __('Customer deleted successfully', 'asmaa-salon'));
    }
}

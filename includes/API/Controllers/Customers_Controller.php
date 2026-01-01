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

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        global $wpdb;
        $params = $this->get_pagination_params($request);
        
        // Get WooCommerce customers
        $args = [
            'role' => 'customer',
            'number' => $params['per_page'],
            'offset' => ($params['page'] - 1) * $params['per_page'],
            'orderby' => 'registered',
            'order' => 'DESC',
        ];

        // Add search filter
        $search = $request->get_param('search');
        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        // Get customers from WordPress users (WooCommerce customers are WordPress users)
        $users = get_users($args);
        $user_counts = count_users();
        $total = $user_counts['avail_roles']['customer'] ?? 0;

        // Get extended data for all customers
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $customer_ids = array_map(fn($user) => $user->ID, $users);
        
        $extended_data_map = [];
        if (!empty($customer_ids)) {
            $placeholders = implode(',', array_fill(0, count($customer_ids), '%d'));
            $extended_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$extended_table} WHERE wc_customer_id IN ({$placeholders})",
                    ...$customer_ids
                )
            );
            
            foreach ($extended_rows as $row) {
                $extended_data_map[$row->wc_customer_id] = $row;
            }
        }

        // Format response
        $items = [];
        foreach ($users as $user) {
            $customer_data = $this->format_customer_data($user, $extended_data_map[$user->ID] ?? null);
            $items[] = $customer_data;
        }

        return $this->success_response([
            'items'      => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $id = (int) $request->get_param('id');
        $user = get_user_by('ID', $id);

        if (!$user || !in_array('customer', (array) $user->roles)) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }

        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $id)
        );

        $customer_data = $this->format_customer_data($user, $extended);

        return $this->success_response($customer_data);
    }

    public function get_profile(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        global $wpdb;
        $id = (int) $request->get_param('id');
        $user = get_user_by('ID', $id);

        if (!$user || !in_array('customer', (array) $user->roles)) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }

        // Get extended data
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $id)
        );

        $customer_data = $this->format_customer_data($user, $extended);

        // Get today's orders from WooCommerce
        $today = date('Y-m-d');
        $wc_orders = wc_get_orders([
            'customer_id' => $id,
            'date_created' => $today,
            'limit' => -1,
        ]);

        $today_orders = [];
        $today_total = 0.0;
        foreach ($wc_orders as $wc_order) {
            $order_data = [
                'id' => $wc_order->get_id(),
                'order_number' => $wc_order->get_order_number(),
                'total' => (float) $wc_order->get_total(),
                'status' => $wc_order->get_status(),
                'date' => $wc_order->get_date_created()->date('Y-m-d H:i:s'),
            ];
            $today_orders[] = $order_data;
            $today_total += $order_data['total'];
        }

        // Get today's loyalty transactions
        $loyalty_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        $today_loyalty = $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM {$loyalty_table}
             WHERE wc_customer_id = %d
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

        // Get current membership
        $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
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
             WHERE m.wc_customer_id = %d
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
             WHERE m.wc_customer_id = %d
             ORDER BY m.id DESC
             LIMIT 10",
            $id
        ));

        return $this->success_response([
            'customer' => $customer_data,
            'today' => [
                'date' => $today,
                'orders' => $today_orders,
                'total_amount' => round($today_total, 3),
                'points_earned' => $today_points,
            ],
            'membership' => [
                'current' => $current_membership,
                'history' => $membership_history,
            ],
            'loyalty' => [
                'balance' => (int) ($extended->loyalty_points ?? 0),
                'today_transactions' => $today_loyalty,
            ],
        ]);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $name = sanitize_text_field($request->get_param('name'));
        $phone = sanitize_text_field($request->get_param('phone'));
        $email = sanitize_email($request->get_param('email'));

        if (empty($name) || empty($phone)) {
            return $this->error_response(__('Name and phone are required', 'asmaa-salon'), 400);
        }

        // Create WordPress user (WooCommerce customer)
        $username = sanitize_user($phone . '_' . time());
        $user_email = $email ?: ($phone . '@asmaa-salon.local');

        // Check if email already exists
        if ($email && email_exists($user_email)) {
            return $this->error_response(__('Email already exists', 'asmaa-salon'), 400);
        }

        $user_id = wp_create_user($username, wp_generate_password(), $user_email);

        if (is_wp_error($user_id)) {
            return $this->error_response($user_id->get_error_message(), 400);
        }

        // Assign WooCommerce customer role
        $user = new \WP_User($user_id);
        $user->set_role('customer');

        // Update user data
        $user_data = [
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name,
        ];
        wp_update_user($user_data);

        // Create WooCommerce customer
        $wc_customer = new \WC_Customer($user_id);
        $wc_customer->set_billing_phone($phone);
        $wc_customer->set_billing_address_1(sanitize_text_field($request->get_param('address')));
        $wc_customer->set_billing_city(sanitize_text_field($request->get_param('city')));
        $wc_customer->save();

        // Create extended data
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $preferred_staff_id = $request->get_param('preferred_staff_id') ? (int) $request->get_param('preferred_staff_id') : null;

        $wpdb->insert($extended_table, [
            'wc_customer_id' => $user_id,
            'preferred_staff_id' => $preferred_staff_id,
            'notes' => sanitize_textarea_field($request->get_param('notes')),
        ]);

        $user = get_user_by('ID', $user_id);
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $user_id)
        );

        $customer_data = $this->format_customer_data($user, $extended);

        return $this->success_response($customer_data, __('Customer created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $id = (int) $request->get_param('id');
        $user = get_user_by('ID', $id);

        if (!$user || !in_array('customer', (array) $user->roles)) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }

        // Update WordPress user
        $user_data = [];
        if ($request->has_param('name')) {
            $user_data['display_name'] = sanitize_text_field($request->get_param('name'));
            $user_data['first_name'] = sanitize_text_field($request->get_param('name'));
        }
        if ($request->has_param('email')) {
            $email = sanitize_email($request->get_param('email'));
            if ($email && $email !== $user->user_email) {
                if (email_exists($email)) {
                    return $this->error_response(__('Email already exists', 'asmaa-salon'), 400);
                }
                $user_data['user_email'] = $email;
            }
        }

        if (!empty($user_data)) {
            $user_data['ID'] = $id;
            wp_update_user($user_data);
        }

        // Update WooCommerce customer
        $wc_customer = new \WC_Customer($id);
        if ($request->has_param('phone')) {
            $wc_customer->set_billing_phone(sanitize_text_field($request->get_param('phone')));
        }
        if ($request->has_param('address')) {
            $wc_customer->set_billing_address_1(sanitize_text_field($request->get_param('address')));
        }
        if ($request->has_param('city')) {
            $wc_customer->set_billing_city(sanitize_text_field($request->get_param('city')));
        }
        $wc_customer->save();

        // Update extended data
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended_data = [];

        if ($request->has_param('preferred_staff_id')) {
            $extended_data['preferred_staff_id'] = $request->get_param('preferred_staff_id') ? (int) $request->get_param('preferred_staff_id') : null;
        }
        if ($request->has_param('notes')) {
            $extended_data['notes'] = sanitize_textarea_field($request->get_param('notes'));
        }

        if (!empty($extended_data)) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $id)
            );

            if ($existing) {
                $wpdb->update($extended_table, $extended_data, ['wc_customer_id' => $id]);
            } else {
                $extended_data['wc_customer_id'] = $id;
                $wpdb->insert($extended_table, $extended_data);
            }
        }

        $user = get_user_by('ID', $id);
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $id)
        );

        $customer_data = $this->format_customer_data($user, $extended);

        return $this->success_response($customer_data, __('Customer updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');
        $user = get_user_by('ID', $id);

        if (!$user || !in_array('customer', (array) $user->roles)) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }

        // Delete WordPress user (cascade will delete extended data)
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($id);

        return $this->success_response(null, __('Customer deleted successfully', 'asmaa-salon'));
    }

    /**
     * Format customer data from WordPress user and extended data
     */
    private function format_customer_data($user, $extended = null): array
    {
        $wc_customer = new \WC_Customer($user->ID);
        
        // Get gender from user meta (if exists)
        $gender = get_user_meta($user->ID, 'gender', true) ?: null;

        return [
            'id' => $user->ID,
            'name' => $user->display_name ?: $user->user_login,
            'email' => $user->user_email,
            'phone' => $wc_customer->get_billing_phone(),
            'address' => $wc_customer->get_billing_address_1(),
            'city' => $wc_customer->get_billing_city(),
            'gender' => $gender,
            'total_visits' => (int) ($extended->total_visits ?? 0),
            'total_spent' => (float) ($extended->total_spent ?? 0),
            'loyalty_points' => (int) ($extended->loyalty_points ?? 0),
            'last_visit_at' => $extended->last_visit_at ?? null,
            'preferred_staff_id' => $extended->preferred_staff_id ?? null,
            'notes' => $extended->notes ?? null,
            'is_active' => true, // All WooCommerce customers are active by default
            'created_at' => $user->user_registered,
        ];
    }
}

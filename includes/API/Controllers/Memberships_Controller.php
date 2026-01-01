<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\Apple_Wallet_Service;
if (!defined('ABSPATH')) {
    exit;
}

class Memberships_Controller extends Base_Controller
{
    protected string $rest_base = 'memberships';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/plans', [
            ['methods' => 'GET', 'callback' => [$this, 'get_plans'], 'permission_callback' => $this->permission_callback('asmaa_memberships_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_plan'], 'permission_callback' => $this->permission_callback('asmaa_memberships_create')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/plans/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_plan'], 'permission_callback' => $this->permission_callback('asmaa_memberships_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_plan'], 'permission_callback' => $this->permission_callback('asmaa_memberships_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_plan'], 'permission_callback' => $this->permission_callback('asmaa_memberships_delete')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_customer_memberships'], 'permission_callback' => $this->permission_callback('asmaa_memberships_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_membership'], 'permission_callback' => $this->permission_callback('asmaa_memberships_create')],
        ]);

        // Alias to match frontend route used by Memberships page
        register_rest_route($this->namespace, '/' . $this->rest_base . '/members', [
            ['methods' => 'GET', 'callback' => [$this, 'get_customer_memberships'], 'permission_callback' => $this->permission_callback('asmaa_memberships_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_membership'], 'permission_callback' => $this->permission_callback('asmaa_memberships_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_membership'], 'permission_callback' => $this->permission_callback('asmaa_memberships_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'cancel_membership'], 'permission_callback' => $this->permission_callback('asmaa_memberships_cancel')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/renew', [
            ['methods' => 'POST', 'callback' => [$this, 'renew_membership'], 'permission_callback' => $this->permission_callback('asmaa_memberships_renew')],
        ]);
        
        // Apple Wallet pass creation
        register_rest_route($this->namespace, '/' . $this->rest_base . '/apple-wallet/(?P<customer_id>\d+)', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_apple_wallet_pass'],
                'permission_callback' => $this->permission_callback('asmaa_memberships_view'),
            ],
        ]);
    }

    public function get_plans(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_membership_plans';

        $items = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY price ASC"
        );

        return $this->success_response($items);
    }

    public function get_plan(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_membership_plans';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        if (!$item) {
            return $this->error_response(__('Membership plan not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function create_plan(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_membership_plans';

        $data = [
            'name' => sanitize_text_field($request->get_param('name')),
            'name_ar' => sanitize_text_field($request->get_param('name_ar')),
            'description' => sanitize_textarea_field($request->get_param('description')),
            'price' => (float) $request->get_param('price'),
            'duration_months' => (int) $request->get_param('duration_months'),
            'discount_percentage' => (float) $request->get_param('discount_percentage'),
            'free_services_count' => (int) $request->get_param('free_services_count'),
            'priority_booking' => $request->get_param('priority_booking') ? 1 : 0,
            'points_multiplier' => (float) ($request->get_param('points_multiplier') ?: 1.0),
            'is_active' => $request->get_param('is_active') ? 1 : 0,
        ];

        if (empty($data['name']) || empty($data['price']) || empty($data['duration_months'])) {
            return $this->error_response(__('Name, price and duration are required', 'asmaa-salon'), 400);
        }

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return $this->error_response(__('Failed to create membership plan', 'asmaa-salon'), 500);
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id));
        return $this->success_response($item, __('Membership plan created successfully', 'asmaa-salon'), 201);
    }

    public function update_plan(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_membership_plans';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$existing) {
            return $this->error_response(__('Membership plan not found', 'asmaa-salon'), 404);
        }

        $data = [];
        $fields = ['name', 'name_ar', 'description', 'price', 'duration_months', 'discount_percentage', 'free_services_count', 'priority_booking', 'points_multiplier', 'is_active'];

        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if ($field === 'description') {
                    $data[$field] = sanitize_textarea_field($request->get_param($field));
                } elseif (in_array($field, ['price', 'discount_percentage', 'points_multiplier'])) {
                    $data[$field] = (float) $request->get_param($field);
                } elseif (in_array($field, ['duration_months', 'free_services_count'])) {
                    $data[$field] = (int) $request->get_param($field);
                } elseif (in_array($field, ['priority_booking', 'is_active'])) {
                    $data[$field] = $request->get_param($field) ? 1 : 0;
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

        return $this->success_response($item, __('Membership plan updated successfully', 'asmaa-salon'));
    }

    public function delete_plan(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_membership_plans';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$existing) {
            return $this->error_response(__('Membership plan not found', 'asmaa-salon'), 404);
        }

        // Soft delete by deactivating
        $wpdb->update($table, ['is_active' => 0], ['id' => $id]);
        return $this->success_response(null, __('Membership plan deleted successfully', 'asmaa-salon'));
    }

    public function get_customer_memberships(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customer_memberships';
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = [];
        
        $customer_id = $request->get_param('customer_id');
        if ($customer_id) {
            $where[] = $wpdb->prepare('m.wc_customer_id = %d', $customer_id);
        }

        $status = $request->get_param('status');
        if ($status) {
            $where[] = $wpdb->prepare('m.status = %s', $status);
        }

        $search = $request->get_param('search');
        if ($search) {
            $like = '%' . $wpdb->esc_like((string) $search) . '%';
            $where[] = $wpdb->prepare('m.wc_customer_id IN (SELECT ID FROM {$wpdb->users} WHERE display_name LIKE %s OR user_email LIKE %s)', $like, $like);
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} m LEFT JOIN {$plans_table} p ON p.id = m.membership_plan_id {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT
                m.*,
                u.display_name AS customer_name,
                u.user_email AS customer_email,
                p.name AS plan_name,
                p.name_ar AS plan_name_ar,
                p.duration_months AS plan_duration_months,
                p.discount_percentage,
                p.free_services_count AS services_limit,
                p.points_multiplier,
                p.priority_booking
            FROM {$table} m
            LEFT JOIN {$wpdb->users} u ON u.ID = m.wc_customer_id
            LEFT JOIN {$plans_table} p ON p.id = m.membership_plan_id
            {$where_clause}
            ORDER BY m.created_at DESC
            LIMIT %d OFFSET %d",
            $params['per_page'],
            $offset
        ));

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function create_membership(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customer_memberships';

        $customer_id = (int) $request->get_param('customer_id');
        $plan_id = (int) $request->get_param('membership_plan_id');
        $duration_months = (int) ($request->get_param('duration_months') ?: 1);

        if (empty($customer_id) || empty($plan_id)) {
            return $this->error_response(__('Customer ID and plan ID are required', 'asmaa-salon'), 400);
        }

        // Get plan details
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
        $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$plans_table} WHERE id = %d AND is_active = 1", $plan_id));
        if (!$plan) {
            return $this->error_response(__('Membership plan not found or inactive', 'asmaa-salon'), 404);
        }

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$duration_months} months"));

        $data = [
            'wc_customer_id' => $customer_id,
            'membership_plan_id' => $plan_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => 'active',
            'auto_renew' => $request->get_param('auto_renew') ? 1 : 0,
            'services_used' => 0,
        ];

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return $this->error_response(__('Failed to create membership', 'asmaa-salon'), 500);
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id));
        
        // Update Apple Wallet pass
        try {
            Apple_Wallet_Service::update_membership_pass($customer_id);
        } catch (\Exception $e) {
            error_log('Apple Wallet update failed: ' . $e->getMessage());
        }
        
        return $this->success_response($item, __('Membership created successfully', 'asmaa-salon'), 201);
    }

    public function get_membership(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customer_memberships';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        if (!$item) {
            return $this->error_response(__('Membership not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function update_membership(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customer_memberships';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$existing) {
            return $this->error_response(__('Membership not found', 'asmaa-salon'), 404);
        }

        $data = [];
        if ($request->has_param('auto_renew')) {
            $data['auto_renew'] = $request->get_param('auto_renew') ? 1 : 0;
        }
        if ($request->has_param('status')) {
            $data['status'] = sanitize_text_field($request->get_param('status'));
        }

        if (empty($data)) {
            return $this->error_response(__('No data to update', 'asmaa-salon'), 400);
        }

        $wpdb->update($table, $data, ['id' => $id]);
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        // Update Apple Wallet pass
        if ($item && isset($item->wc_customer_id)) {
            try {
                Apple_Wallet_Service::update_pass((int) $item->wc_customer_id);
            } catch (\Exception $e) {
                error_log('Apple Wallet update failed: ' . $e->getMessage());
            }
        }

        return $this->success_response($item, __('Membership updated successfully', 'asmaa-salon'));
    }

    public function cancel_membership(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_customer_memberships';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$existing) {
            return $this->error_response(__('Membership not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['status' => 'cancelled'], ['id' => $id]);
        return $this->success_response(null, __('Membership cancelled successfully', 'asmaa-salon'));
    }

    public function renew_membership(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
            $extensions_table = $wpdb->prefix . 'asmaa_membership_extensions';
            $id = (int) $request->get_param('id');
            $months = (int) ($request->get_param('months') ?: 1);
            $amount_paid = (float) $request->get_param('amount_paid');

            $membership = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$memberships_table} WHERE id = %d", $id));
            if (!$membership) {
                throw new \Exception(__('Membership not found', 'asmaa-salon'));
            }

            // Extend end date
            $new_end_date = date('Y-m-d', strtotime($membership->end_date . " +{$months} months"));
            $wpdb->update($memberships_table, [
                'end_date' => $new_end_date,
                'status' => 'active',
            ], ['id' => $id]);

            // Record extension
            $wpdb->insert($extensions_table, [
                'customer_membership_id' => $id,
                'extended_by_months' => $months,
                'amount_paid' => $amount_paid,
                'extended_at' => current_time('mysql'),
            ]);

            $wpdb->query('COMMIT');

            $updated = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$memberships_table} WHERE id = %d", $id));
            
            // Update Apple Wallet pass
            if ($updated && isset($updated->wc_customer_id)) {
                try {
                    Apple_Wallet_Service::update_pass((int) $updated->wc_customer_id);
                } catch (\Exception $e) {
                    error_log('Apple Wallet update failed: ' . $e->getMessage());
                }
            }
            
            return $this->success_response($updated, __('Membership renewed successfully', 'asmaa-salon'));
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }
    
    /**
     * Create Apple Wallet pass for membership
     */
    public function create_apple_wallet_pass(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $customer_id = (int) $request->get_param('customer_id');
        
        try {
            $result = Apple_Wallet_Service::create_membership_pass($customer_id);
            return $this->success_response($result, __('Membership pass created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }
}

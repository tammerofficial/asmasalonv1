<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\Apple_Wallet_Service;

if (!defined('ABSPATH')) {
    exit;
}

class Commissions_Controller extends Base_Controller
{
    protected string $rest_base = 'commissions';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_commissions_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_commissions_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/approve', [
            ['methods' => 'POST', 'callback' => [$this, 'approve_commissions'], 'permission_callback' => $this->permission_callback('asmaa_commissions_approve')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/settings', [
            ['methods' => 'GET', 'callback' => [$this, 'get_settings'], 'permission_callback' => $this->permission_callback('asmaa_commissions_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_settings'], 'permission_callback' => $this->permission_callback('asmaa_commissions_calculate')],
        ]);
        
        // Apple Wallet pass creation for staff
        register_rest_route($this->namespace, '/' . $this->rest_base . '/apple-wallet/(?P<staff_id>\d+)', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_apple_wallet_pass'],
                'permission_callback' => $this->permission_callback('asmaa_commissions_view'),
            ],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff_commissions';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = [];
        
        $staff_id = $request->get_param('staff_id');
        if ($staff_id) {
            $where[] = $wpdb->prepare('sc.wp_user_id = %d', $staff_id);
        }

        $status = $request->get_param('status');
        if ($status) {
            $where[] = $wpdb->prepare('sc.status = %s', $status);
        }

        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');
        if ($start_date && $end_date) {
            $where[] = $wpdb->prepare('DATE(sc.created_at) BETWEEN %s AND %s', $start_date, $end_date);
        }

        $search = $request->get_param('search');
        if ($search) {
            $like = '%' . $wpdb->esc_like((string) $search) . '%';
            $where[] = $wpdb->prepare('(u.display_name LIKE %s OR u.user_email LIKE %s)', $like, $like);
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} sc LEFT JOIN {$wpdb->users} u ON u.ID = sc.wp_user_id {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT
                sc.*,
                u.display_name AS staff_name
             FROM {$table} sc
             LEFT JOIN {$wpdb->users} u ON u.ID = sc.wp_user_id
             {$where_clause}
             ORDER BY sc.created_at DESC
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
        $table = $wpdb->prefix . 'asmaa_staff_commissions';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT sc.*, u.display_name AS staff_name
             FROM {$table} sc
             LEFT JOIN {$wpdb->users} u ON u.ID = sc.wp_user_id
             WHERE sc.id = %d",
            $id
        ));

        if (!$item) {
            return $this->error_response(__('Commission not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function approve_commissions(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff_commissions';
        $ids = $request->get_param('ids'); // Array of commission IDs

        if (empty($ids) || !is_array($ids)) {
            return $this->error_response(__('Commission IDs are required', 'asmaa-salon'), 400);
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return $this->error_response(__('Commission IDs are required', 'asmaa-salon'), 400);
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = "UPDATE {$table}
                SET status = 'approved', wp_user_id_approved = %d, approved_at = %s
                WHERE id IN ({$placeholders}) AND status = 'pending'";
        $params = array_merge([get_current_user_id(), current_time('mysql')], $ids);

        $wpdb->query($wpdb->prepare($sql, ...$params));

        // Update Apple Wallet passes for affected staff
        $staff_ids = $wpdb->get_col("SELECT DISTINCT wp_user_id FROM {$table} WHERE id IN (" . implode(',', $ids) . ")");
        foreach ($staff_ids as $staff_id) {
            try {
                Apple_Wallet_Service::update_commissions_pass((int) $staff_id);
            } catch (\Exception $e) {
                error_log('Apple Wallet update failed for staff ' . $staff_id . ': ' . $e->getMessage());
            }
        }

        return $this->success_response(null, __('Commissions approved successfully', 'asmaa-salon'));
    }

    public function get_settings(): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_commission_settings';

        $settings = $wpdb->get_row("SELECT * FROM {$table} ORDER BY id DESC LIMIT 1");

        if (!$settings) {
            // Return defaults
            return $this->success_response([
                'service_commission_rate' => 10.00,
                'product_commission_rate' => 5.00,
                'reception_share_percentage' => 0.00,
                'rating_bonus_enabled' => true,
                'rating_bonus_5_star' => 50.000,
                'rating_bonus_4_star' => 25.000,
            ]);
        }

        return $this->success_response($settings);
    }

    public function update_settings(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_commission_settings';

        $data = [
            'service_commission_rate' => (float) $request->get_param('service_commission_rate'),
            'product_commission_rate' => (float) $request->get_param('product_commission_rate'),
            'reception_share_percentage' => (float) $request->get_param('reception_share_percentage'),
            'rating_bonus_enabled' => $request->get_param('rating_bonus_enabled') ? 1 : 0,
            'rating_bonus_5_star' => (float) $request->get_param('rating_bonus_5_star'),
            'rating_bonus_4_star' => (float) $request->get_param('rating_bonus_4_star'),
        ];

        $existing = $wpdb->get_row("SELECT * FROM {$table} ORDER BY id DESC LIMIT 1");

        if ($existing) {
            $wpdb->update($table, $data, ['id' => $existing->id]);
        } else {
            $wpdb->insert($table, $data);
        }

        $settings = $wpdb->get_row("SELECT * FROM {$table} ORDER BY id DESC LIMIT 1");
        return $this->success_response($settings, __('Settings updated successfully', 'asmaa-salon'));
    }
    
    /**
     * Create Apple Wallet pass for staff commissions
     */
    public function create_apple_wallet_pass(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $staff_id = (int) $request->get_param('staff_id');
        
        try {
            $result = Apple_Wallet_Service::create_commissions_pass($staff_id);
            return $this->success_response($result, __('Commissions pass created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }
}

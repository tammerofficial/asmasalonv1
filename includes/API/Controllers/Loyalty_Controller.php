<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\ActivityLogger;

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Controller extends Base_Controller
{
    protected string $rest_base = 'loyalty';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/transactions', [
            ['methods' => 'GET', 'callback' => [$this, 'get_transactions'], 'permission_callback' => $this->permission_callback('asmaa_loyalty_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', [
            ['methods' => 'GET', 'callback' => [$this, 'get_stats'], 'permission_callback' => $this->permission_callback('asmaa_loyalty_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/earn', [
            ['methods' => 'POST', 'callback' => [$this, 'earn_points'], 'permission_callback' => $this->permission_callback('asmaa_loyalty_manage')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/redeem', [
            ['methods' => 'POST', 'callback' => [$this, 'redeem_points'], 'permission_callback' => $this->permission_callback('asmaa_loyalty_manage')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/adjust', [
            ['methods' => 'POST', 'callback' => [$this, 'adjust_points'], 'permission_callback' => $this->permission_callback('asmaa_loyalty_adjust')],
        ]);
    }

    public function get_transactions(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = [];
        
        $customer_id = $request->get_param('customer_id');
        if ($customer_id) {
            $where[] = $wpdb->prepare('customer_id = %d', $customer_id);
        }

        $type = $request->get_param('type');
        if ($type) {
            $where[] = $wpdb->prepare('type = %s', $type);
        }

        $customer_search = $request->get_param('customer_search');
        if ($customer_search) {
            $like = '%' . $wpdb->esc_like((string) $customer_search) . '%';
            $where[] = $wpdb->prepare('(c.name LIKE %s OR c.phone LIKE %s)', $like, $like);
        }

        // Always exclude deleted customers from listing
        $where[] = 'c.deleted_at IS NULL';

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} t LEFT JOIN {$customers_table} c ON c.id = t.customer_id {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT
                t.*,
                c.name AS customer_name,
                c.phone AS customer_phone
             FROM {$table} t
             LEFT JOIN {$customers_table} c ON c.id = t.customer_id
             {$where_clause}
             ORDER BY t.created_at DESC
             LIMIT %d OFFSET %d",
            $params['per_page'],
            $offset
        ));

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function get_stats(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        $customers_table = $wpdb->prefix . 'asmaa_customers';

        $total_points_issued = (int) $wpdb->get_var("SELECT COALESCE(SUM(points), 0) FROM {$transactions_table} WHERE type = 'earned'");
        $total_points_redeemed = (int) abs((int) $wpdb->get_var("SELECT COALESCE(SUM(points), 0) FROM {$transactions_table} WHERE type = 'redeemed'"));
        $active_points = (int) $wpdb->get_var("SELECT COALESCE(SUM(loyalty_points), 0) FROM {$customers_table} WHERE deleted_at IS NULL");
        $active_customers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$customers_table} WHERE deleted_at IS NULL AND loyalty_points > 0");

        return $this->success_response([
            'total_points_issued' => $total_points_issued,
            'total_points_redeemed' => $total_points_redeemed,
            'active_points' => $active_points,
            'active_customers' => $active_customers,
        ]);
    }

    public function earn_points(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $customer_id = (int) $request->get_param('customer_id');
            $points = (int) $request->get_param('points');
            $reference_type = sanitize_text_field($request->get_param('reference_type'));
            $reference_id = $request->get_param('reference_id') ? (int) $request->get_param('reference_id') : null;
            $description = sanitize_text_field($request->get_param('description'));

            if (empty($customer_id) || $points <= 0) {
                throw new \Exception(__('Customer ID and positive points are required', 'asmaa-salon'));
            }

            // Get current balance
            $customers_table = $wpdb->prefix . 'asmaa_customers';
            $customer = $wpdb->get_row($wpdb->prepare("SELECT loyalty_points FROM {$customers_table} WHERE id = %d", $customer_id));
            if (!$customer) {
                throw new \Exception(__('Customer not found', 'asmaa-salon'));
            }

            $balance_before = (int) $customer->loyalty_points;
            $balance_after = $balance_before + $points;

            // Create transaction
            $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
            $wpdb->insert($transactions_table, [
                'customer_id' => $customer_id,
                'type' => 'earned',
                'points' => $points,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
                'description' => $description,
                'performed_by' => get_current_user_id(),
            ]);

            // Update customer balance
            $wpdb->update($customers_table, ['loyalty_points' => $balance_after], ['id' => $customer_id]);

            $wpdb->query('COMMIT');

            ActivityLogger::log_loyalty('earned', $customer_id, [
                'points' => $points,
                'balance_after' => $balance_after,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
            ]);

            return $this->success_response([
                'customer_id' => $customer_id,
                'points_earned' => $points,
                'balance_after' => $balance_after,
            ], __('Points earned successfully', 'asmaa-salon'));
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    public function redeem_points(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $customer_id = (int) $request->get_param('customer_id');
            $points = (int) $request->get_param('points');
            $reference_type = sanitize_text_field($request->get_param('reference_type'));
            $reference_id = $request->get_param('reference_id') ? (int) $request->get_param('reference_id') : null;
            $description = sanitize_text_field($request->get_param('description'));

            if (empty($customer_id) || $points <= 0) {
                throw new \Exception(__('Customer ID and positive points are required', 'asmaa-salon'));
            }

            // Get current balance
            $customers_table = $wpdb->prefix . 'asmaa_customers';
            $customer = $wpdb->get_row($wpdb->prepare("SELECT loyalty_points FROM {$customers_table} WHERE id = %d", $customer_id));
            if (!$customer) {
                throw new \Exception(__('Customer not found', 'asmaa-salon'));
            }

            $balance_before = (int) $customer->loyalty_points;
            
            if ($balance_before < $points) {
                throw new \Exception(__('Insufficient points', 'asmaa-salon'));
            }

            $balance_after = $balance_before - $points;

            // Create transaction
            $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
            $wpdb->insert($transactions_table, [
                'customer_id' => $customer_id,
                'type' => 'redeemed',
                'points' => -$points,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
                'description' => $description,
                'performed_by' => get_current_user_id(),
            ]);

            // Update customer balance
            $wpdb->update($customers_table, ['loyalty_points' => $balance_after], ['id' => $customer_id]);

            $wpdb->query('COMMIT');

            ActivityLogger::log_loyalty('redeemed', $customer_id, [
                'points' => $points,
                'balance_after' => $balance_after,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
            ]);

            return $this->success_response([
                'customer_id' => $customer_id,
                'points_redeemed' => $points,
                'balance_after' => $balance_after,
            ], __('Points redeemed successfully', 'asmaa-salon'));
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    public function adjust_points(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $customer_id = (int) $request->get_param('customer_id');
            $points = (int) $request->get_param('points'); // Can be positive or negative
            $description = sanitize_text_field($request->get_param('description'));

            if (empty($customer_id)) {
                throw new \Exception(__('Customer ID is required', 'asmaa-salon'));
            }

            // Get current balance
            $customers_table = $wpdb->prefix . 'asmaa_customers';
            $customer = $wpdb->get_row($wpdb->prepare("SELECT loyalty_points FROM {$customers_table} WHERE id = %d", $customer_id));
            if (!$customer) {
                throw new \Exception(__('Customer not found', 'asmaa-salon'));
            }

            $balance_before = (int) $customer->loyalty_points;
            $balance_after = $balance_before + $points;

            if ($balance_after < 0) {
                throw new \Exception(__('Balance cannot be negative', 'asmaa-salon'));
            }

            // Create transaction
            $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
            $wpdb->insert($transactions_table, [
                'customer_id' => $customer_id,
                'type' => 'adjustment',
                'points' => $points,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'description' => $description ?: __('Manual adjustment', 'asmaa-salon'),
                'performed_by' => get_current_user_id(),
            ]);

            // Update customer balance
            $wpdb->update($customers_table, ['loyalty_points' => $balance_after], ['id' => $customer_id]);

            $wpdb->query('COMMIT');

            ActivityLogger::log_loyalty('adjusted', $customer_id, [
                'points' => $points,
                'balance_after' => $balance_after,
                'description' => $description,
            ]);

            return $this->success_response([
                'customer_id' => $customer_id,
                'points_adjusted' => $points,
                'balance_after' => $balance_after,
            ], __('Points adjusted successfully', 'asmaa-salon'));
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }
}

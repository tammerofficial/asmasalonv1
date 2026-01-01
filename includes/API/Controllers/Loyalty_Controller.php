<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\ActivityLogger;
use AsmaaSalon\Services\Apple_Wallet_Service;

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Controller extends Base_Controller
{
    protected string $rest_base = 'loyalty';

    /**
     * Lightweight column existence helper (supports old/new schemas).
     */
    private function pick_column(string $table, array $candidates): ?string
    {
        global $wpdb;
        $cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
        if (!is_array($cols) || empty($cols)) {
            return null;
        }
        $map = array_fill_keys(array_map('strtolower', $cols), true);
        foreach ($candidates as $col) {
            if (isset($map[strtolower((string) $col)])) {
                return (string) $col;
            }
        }
        return null;
    }

    private function table_exists(string $table): bool
    {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
    }

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
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $customer_col = $this->pick_column($table, ['wc_customer_id', 'customer_id']) ?: 'wc_customer_id';

        $where = [];
        
        $customer_id = $request->get_param('customer_id');
        if ($customer_id) {
            $where[] = $wpdb->prepare("{$customer_col} = %d", (int) $customer_id);
        }

        $type = $request->get_param('type');
        if ($type) {
            $where[] = $wpdb->prepare('type = %s', $type);
        }

        $customer_search = $request->get_param('customer_search');
        if ($customer_search) {
            $like = '%' . $wpdb->esc_like((string) $customer_search) . '%';
            // NOTE: use interpolated table name (avoid literal "{$wpdb->users}" bug).
            $where[] = $wpdb->prepare(
                "{$customer_col} IN (SELECT ID FROM {$wpdb->users} WHERE display_name LIKE %s OR user_email LIKE %s)",
                $like,
                $like
            );
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.display_name AS customer_name, u.user_email AS customer_email
             FROM {$table} t
             LEFT JOIN {$wpdb->users} u ON u.ID = t.{$customer_col}
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
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';

        $total_points_issued = (int) $wpdb->get_var("SELECT COALESCE(SUM(points), 0) FROM {$transactions_table} WHERE type = 'earned'");
        $total_points_redeemed = (int) abs((int) $wpdb->get_var("SELECT COALESCE(SUM(points), 0) FROM {$transactions_table} WHERE type = 'redeemed'"));
        $active_points = (int) $wpdb->get_var("SELECT COALESCE(SUM(loyalty_points), 0) FROM {$extended_table}");
        $active_customers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$extended_table} WHERE loyalty_points > 0");

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
                return $this->error_response(__('Customer ID and positive points are required', 'asmaa-salon'), 400);
            }

            // Get current balance from extended data
            $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
            $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
            if (!$this->table_exists($extended_table) || !$this->table_exists($transactions_table)) {
                return $this->error_response(__('Database tables are not ready. Please re-activate the plugin to run migrations.', 'asmaa-salon'), 500);
            }

            $extended = $wpdb->get_row($wpdb->prepare("SELECT loyalty_points FROM {$extended_table} WHERE wc_customer_id = %d", $customer_id));
            if (!$extended) {
                // Create extended data if doesn't exist
                $wpdb->insert($extended_table, ['wc_customer_id' => $customer_id, 'loyalty_points' => 0]);
                $balance_before = 0;
            } else {
                $balance_before = (int) $extended->loyalty_points;
            }

            $balance_after = $balance_before + $points;

            // Create transaction
            $customer_col = $this->pick_column($transactions_table, ['wc_customer_id', 'customer_id']) ?: 'wc_customer_id';
            $user_col = $this->pick_column($transactions_table, ['wp_user_id', 'performed_by']) ?: 'wp_user_id';
            
            $performed_by = get_current_user_id();
            
            $result = $wpdb->insert($transactions_table, [
                $customer_col => $customer_id,
                'type' => 'earned',
                'points' => $points,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
                'description' => $description,
                $user_col => $performed_by ?: null, // Use NULL instead of 0 for FK
            ]);

            if ($result === false) {
                throw new \Exception(__('Failed to record loyalty transaction', 'asmaa-salon'));
            }

            // Update customer balance in extended data
            $update_result = $wpdb->update($extended_table, ['loyalty_points' => $balance_after], ['wc_customer_id' => $customer_id]);
            
            if ($update_result === false) {
                throw new \Exception(__('Failed to update customer loyalty balance', 'asmaa-salon'));
            }

            $wpdb->query('COMMIT');

            // Update Apple Wallet pass
            try {
                Apple_Wallet_Service::update_loyalty_pass($customer_id);
            } catch (\Exception $e) {
                // Log error but don't fail the transaction
                error_log('Apple Wallet update failed: ' . $e->getMessage());
            }

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
                return $this->error_response(__('Customer ID and positive points are required', 'asmaa-salon'), 400);
            }

            // Get current balance from extended data
            $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
            $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
            if (!$this->table_exists($extended_table) || !$this->table_exists($transactions_table)) {
                return $this->error_response(__('Database tables are not ready. Please re-activate the plugin to run migrations.', 'asmaa-salon'), 500);
            }

            $extended = $wpdb->get_row($wpdb->prepare("SELECT loyalty_points FROM {$extended_table} WHERE wc_customer_id = %d", $customer_id));
            if (!$extended) {
                // Create a row so the client gets a consistent "insufficient points" instead of a 500 when data isn't initialized yet.
                $wpdb->insert($extended_table, ['wc_customer_id' => $customer_id, 'loyalty_points' => 0]);
                $balance_before = 0;
            } else {
                $balance_before = (int) $extended->loyalty_points;
            }

            if ($balance_before < $points) {
                $wpdb->query('ROLLBACK');
                return $this->error_response(__('Insufficient points', 'asmaa-salon'), 400);
            }

            $balance_after = $balance_before - $points;

            // Create transaction
            $customer_col = $this->pick_column($transactions_table, ['wc_customer_id', 'customer_id']) ?: 'wc_customer_id';
            $user_col = $this->pick_column($transactions_table, ['wp_user_id', 'performed_by']) ?: 'wp_user_id';
            
            $performed_by = get_current_user_id();
            
            $result = $wpdb->insert($transactions_table, [
                $customer_col => $customer_id,
                'type' => 'redeemed',
                'points' => -$points,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
                'description' => $description,
                $user_col => $performed_by ?: null, // Use NULL instead of 0 for FK
            ]);

            if ($result === false) {
                throw new \Exception(__('Failed to record loyalty redemption', 'asmaa-salon'));
            }

            // Update customer balance in extended data
            $update_result = $wpdb->update($extended_table, ['loyalty_points' => $balance_after], ['wc_customer_id' => $customer_id]);
            
            if ($update_result === false) {
                throw new \Exception(__('Failed to update customer loyalty balance', 'asmaa-salon'));
            }

            $wpdb->query('COMMIT');

            // Update Apple Wallet pass
            try {
                Apple_Wallet_Service::update_loyalty_pass($customer_id);
            } catch (\Exception $e) {
                // Log error but don't fail the transaction
                error_log('Apple Wallet update failed: ' . $e->getMessage());
            }

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

            // Get current balance from extended data
            $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
            $extended = $wpdb->get_row($wpdb->prepare("SELECT loyalty_points FROM {$extended_table} WHERE wc_customer_id = %d", $customer_id));
            if (!$extended) {
                throw new \Exception(__('Customer not found', 'asmaa-salon'));
            }

            $balance_before = (int) $extended->loyalty_points;
            $balance_after = $balance_before + $points;

            if ($balance_after < 0) {
                throw new \Exception(__('Balance cannot be negative', 'asmaa-salon'));
            }

            // Create transaction
            $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
            $wpdb->insert($transactions_table, [
                'wc_customer_id' => $customer_id,
                'type' => 'adjustment',
                'points' => $points,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'description' => $description ?: __('Manual adjustment', 'asmaa-salon'),
                'performed_by' => get_current_user_id(),
            ]);

            // Update customer balance in extended data
            $wpdb->update($extended_table, ['loyalty_points' => $balance_after], ['wc_customer_id' => $customer_id]);

            $wpdb->query('COMMIT');

            // Update Apple Wallet pass
            try {
                Apple_Wallet_Service::update_loyalty_pass($customer_id);
            } catch (\Exception $e) {
                // Log error but don't fail the transaction
                error_log('Apple Wallet update failed: ' . $e->getMessage());
            }

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

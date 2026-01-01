<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\ActivityLogger;
use AsmaaSalon\Services\NotificationDispatcher;

if (!defined('ABSPATH')) {
    exit;
}

class Payments_Controller extends Base_Controller
{
    protected string $rest_base = 'payments';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_payments_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => $this->permission_callback('asmaa_payments_create')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_payments_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => $this->permission_callback('asmaa_payments_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => $this->permission_callback('asmaa_payments_delete')],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_payments';
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = ['p.deleted_at IS NULL'];

        $search = sanitize_text_field((string) ($request->get_param('search') ?? ''));
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = $wpdb->prepare(
                '(p.payment_number LIKE %s OR p.reference_number LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR i.invoice_number LIKE %s)',
                $like,
                $like,
                $like,
                $like,
                $like
            );
        }
        
        $status = $request->get_param('status');
        if ($status) {
            $where[] = $wpdb->prepare('p.status = %s', $status);
        }

        $payment_method = $request->get_param('payment_method');
        if ($payment_method) {
            $where[] = $wpdb->prepare('p.payment_method = %s', $payment_method);
        }

        $customer_id = $request->get_param('customer_id');
        if ($customer_id) {
            $where[] = $wpdb->prepare('p.wc_customer_id = %d', $customer_id);
        }

        $date_from = sanitize_text_field((string) ($request->get_param('date_from') ?? ''));
        if ($date_from !== '') {
            $where[] = $wpdb->prepare('DATE(p.payment_date) >= %s', $date_from);
        }

        $date_to = sanitize_text_field((string) ($request->get_param('date_to') ?? ''));
        if ($date_to !== '') {
            $where[] = $wpdb->prepare('DATE(p.payment_date) <= %s', $date_to);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$table} p
             LEFT JOIN {$wpdb->users} u ON u.ID = p.wc_customer_id
             LEFT JOIN {$invoices_table} i ON p.invoice_id = i.id
             {$where_clause}"
        );

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*,
                    u.display_name AS customer_name,
                    u.user_email AS customer_email,
                    i.invoice_number,
                    CASE 
                        WHEN p.wc_payment_id IS NOT NULL THEN 1 
                        ELSE 0 
                    END AS is_synced_with_wc,
                    p.wc_payment_id
             FROM {$table} p
             LEFT JOIN {$wpdb->users} u ON u.ID = p.wc_customer_id
             LEFT JOIN {$invoices_table} i ON p.invoice_id = i.id
             {$where_clause}
             ORDER BY p.id DESC
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
        $table = $wpdb->prefix . 'asmaa_payments';
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*,
                    u.display_name AS customer_name,
                    u.user_email AS customer_email,
                    i.invoice_number
             FROM {$table} p
             LEFT JOIN {$wpdb->users} u ON u.ID = p.wc_customer_id
             LEFT JOIN {$invoices_table} i ON p.invoice_id = i.id
             WHERE p.id = %d AND p.deleted_at IS NULL",
            $id
        ));

        if (!$item) {
            return $this->error_response(__('Payment not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $table = $wpdb->prefix . 'asmaa_payments';
            $payment_number = 'PAY-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$table}"), 4, '0', STR_PAD_LEFT);

            $data = [
                'invoice_id' => $request->get_param('invoice_id') ? (int) $request->get_param('invoice_id') : null,
                'wc_customer_id' => (int) $request->get_param('customer_id'),
                'order_id' => $request->get_param('order_id') ? (int) $request->get_param('order_id') : null,
                'payment_number' => $payment_number,
                'amount' => (float) $request->get_param('amount'),
                'payment_method' => sanitize_text_field($request->get_param('payment_method')),
                'reference_number' => sanitize_text_field($request->get_param('reference_number')),
                'status' => sanitize_text_field($request->get_param('status')) ?: 'completed',
                'notes' => sanitize_textarea_field($request->get_param('notes')),
                'payment_date' => sanitize_text_field($request->get_param('payment_date')) ?: current_time('mysql'),
                'wp_user_id' => get_current_user_id(),
            ];

            if (empty($data['wc_customer_id']) || empty($data['amount']) || empty($data['payment_method'])) {
                throw new \Exception(__('Customer, amount and payment method are required', 'asmaa-salon'));
            }

            $result = $wpdb->insert($table, $data);
            if ($result === false) {
                throw new \Exception(__('Failed to create payment', 'asmaa-salon'));
            }

            $payment_id = (int) $wpdb->insert_id;

            // Update invoice if provided
            if ($data['invoice_id']) {
                $invoice_table = $wpdb->prefix . 'asmaa_invoices';
                $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$invoice_table} WHERE id = %d", $data['invoice_id']));
                
                if ($invoice) {
                    $new_paid = $invoice->paid_amount + $data['amount'];
                    $new_due = $invoice->total - $new_paid;
                    $new_status = $new_paid >= $invoice->total ? 'paid' : ($new_paid > 0 ? 'partial' : $invoice->status);
                    
                    $wpdb->update($invoice_table, [
                        'paid_amount' => $new_paid,
                        'due_amount' => $new_due,
                        'status' => $new_status,
                    ], ['id' => $data['invoice_id']]);
                }
            }

            // Auto-create invoice if order_id exists and no invoice_id provided
            if ($data['order_id'] && !$data['invoice_id']) {
                $invoice_id = $this->auto_create_invoice_for_order($data['order_id'], $data);
                if ($invoice_id) {
                    // Link payment to the created invoice
                    $wpdb->update($table, ['invoice_id' => $invoice_id], ['id' => $payment_id]);
                    $data['invoice_id'] = $invoice_id;
                }
            }

            // Auto-earn loyalty points based on payment amount (10 points per 1 KWD)
            $this->auto_earn_loyalty_points($data);

            // Update WooCommerce order payment status
            if ($data['order_id'] && class_exists('WooCommerce')) {
                $wc_order = wc_get_order((int) $data['order_id']);
                if ($wc_order) {
                    $wc_order->set_payment_method($data['payment_method']);
                    $wc_order->set_payment_method_title($data['payment_method']);
                    $wc_order->payment_complete();
                    $wc_order->update_status('completed', __('Payment received', 'asmaa-salon'));
                    $wc_order->save();
                }
            }

            // Activity log
            ActivityLogger::log_payment('created', $payment_id, (int) $data['wc_customer_id'], [
                'status' => $data['status'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'invoice_id' => $data['invoice_id'],
                'order_id' => $data['order_id'],
            ]);

            if ($data['order_id']) {
                ActivityLogger::log_order('paid', (int) $data['order_id'], (int) $data['wc_customer_id'], [
                    'payment_id' => $payment_id,
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method'],
                ]);
            }

            // Update customer stats in extended data
            if ($data['wc_customer_id']) {
                $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
                $extended = $wpdb->get_row($wpdb->prepare("SELECT total_visits, total_spent FROM {$extended_table} WHERE wc_customer_id = %d", (int) $data['wc_customer_id']));
                if ($extended) {
                    $wpdb->update($extended_table, [
                        'total_visits' => (int) $extended->total_visits + 1,
                        'total_spent' => (float) $extended->total_spent + (float) $data['amount'],
                    ], ['wc_customer_id' => (int) $data['wc_customer_id']]);
                } else {
                    // Create extended data if doesn't exist
                    $wpdb->insert($extended_table, [
                        'wc_customer_id' => (int) $data['wc_customer_id'],
                        'total_visits' => 1,
                        'total_spent' => (float) $data['amount'],
                    ]);
                }
            }

            // Dashboard notification (admins)
            NotificationDispatcher::dashboard_admins('Dashboard.PaymentCreated', [
                'event' => 'payment.created',
                'payment_id' => (int) $payment_id,
                'payment_number' => (string) $payment_number,
                'customer_id' => (int) $data['wc_customer_id'],
                'invoice_id' => $data['invoice_id'] ? (int) $data['invoice_id'] : null,
                'order_id' => $data['order_id'] ? (int) $data['order_id'] : null,
                'amount' => (float) $data['amount'],
                'payment_method' => (string) $data['payment_method'],
                'title_en' => 'Payment received',
                'message_en' => sprintf('%s received (%.3f KWD)', (string) $payment_number, (float) $data['amount']),
                'title_ar' => '💰 تم تسجيل دفعة',
                'message_ar' => sprintf('تم تسجيل %s بقيمة %.3f د.ك', (string) $payment_number, (float) $data['amount']),
                'action' => [
                    'route' => '/payments',
                ],
                'severity' => 'success',
            ]);

            $wpdb->query('COMMIT');

            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $payment_id));

            // Auto-sync payment to WooCommerce (always enabled)
            \AsmaaSalon\Services\WooCommerce_Integration_Service::sync_payment_to_wc($payment_id);

            return $this->success_response($item, __('Payment created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Automatically earn loyalty points when a payment is completed.
     */
    protected function auto_earn_loyalty_points(array $payment_data): void
    {
        // Only completed payments grant points
        if (($payment_data['status'] ?? 'completed') !== 'completed') {
            return;
        }

        $customer_id = (int) ($payment_data['wc_customer_id'] ?? 0);
        $amount      = (float) ($payment_data['amount'] ?? 0);

        if ($customer_id <= 0 || $amount <= 0) {
            return;
        }

        global $wpdb;

        // 10 points per 1 KWD spent
        $points = (int) floor($amount * 10);
        if ($points <= 0) {
            return;
        }

        $extended_table    = $wpdb->prefix . 'asmaa_customer_extended_data';
        $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';

        $extended = $wpdb->get_row($wpdb->prepare(
            "SELECT loyalty_points FROM {$extended_table} WHERE wc_customer_id = %d",
            $customer_id
        ));

        if (!$extended) {
            // Create extended data if doesn't exist
            $wpdb->insert($extended_table, ['wc_customer_id' => $customer_id, 'loyalty_points' => 0]);
            $balance_before = 0;
        } else {
            $balance_before = (int) $extended->loyalty_points;
        }

        $balance_after  = $balance_before + $points;

        $reference_type = 'order';
        $reference_id   = $payment_data['order_id'] ?? null;

        $wpdb->insert($transactions_table, [
            'wc_customer_id'    => $customer_id,
            'type'           => 'earned',
            'points'         => $points,
            'balance_before' => $balance_before,
            'balance_after'  => $balance_after,
            'reference_type' => $reference_type,
            'reference_id'   => $reference_id,
            'description'    => sprintf(
                'Points from payment %s',
                $payment_data['payment_number'] ?? ''
            ),
            'wp_user_id'   => get_current_user_id(),
        ]);

        $wpdb->update(
            $extended_table,
            ['loyalty_points' => $balance_after],
            ['wc_customer_id' => $customer_id]
        );

        // Update Apple Wallet pass
        try {
            \AsmaaSalon\Services\Apple_Wallet_Service::update_pass($customer_id);
        } catch (\Exception $e) {
            error_log('Apple Wallet update failed: ' . $e->getMessage());
        }
    }

    /**
     * Auto-create invoice for a paid order.
     * Note: order_id is now wc_order_id (WooCommerce order ID)
     */
    protected function auto_create_invoice_for_order(int $wc_order_id, array $payment_data): ?int
    {
        global $wpdb;
        
        if (!class_exists('WooCommerce')) {
            return null;
        }

        $wc_order = wc_get_order($wc_order_id);
        if (!$wc_order) {
            return null;
        }

        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $invoice_items_table = $wpdb->prefix . 'asmaa_invoice_items';

        // Check if invoice already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$invoices_table} WHERE order_id = %d AND deleted_at IS NULL",
            $wc_order_id
        ));
        if ($existing) {
            return (int) $existing->id;
        }

        $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$invoices_table}"), 4, '0', STR_PAD_LEFT);

        $subtotal = (float) $wc_order->get_subtotal();
        $discount = (float) $wc_order->get_discount_total();
        $tax = (float) $wc_order->get_total_tax();
        $total = (float) $wc_order->get_total();
        $paid_amount = (float) $payment_data['amount'];

        $invoice_data = [
            'order_id'     => $wc_order_id, // Now stores WC order ID
            'wc_customer_id'  => (int) $wc_order->get_customer_id(),
            'invoice_number' => $invoice_number,
            'issue_date'   => current_time('Y-m-d'),
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'tax'          => $tax,
            'total'        => $total,
            'paid_amount'  => $paid_amount,
            'due_amount'   => max(0, $total - $paid_amount),
            'status'       => $paid_amount >= $total ? 'paid' : 'partial',
        ];

        $result = $wpdb->insert($invoices_table, $invoice_data);
        if ($result === false) {
            return null;
        }

        $invoice_id = (int) $wpdb->insert_id;

        // Create invoice items from WooCommerce order items
        foreach ($wc_order->get_items() as $wc_item) {
            $item_name = $wc_item->get_name();
            $quantity = (int) $wc_item->get_quantity();
            $line_total = (float) $wc_item->get_total();
            $unit_price = $quantity > 0 ? $line_total / $quantity : 0;

            $wpdb->insert($invoice_items_table, [
                'invoice_id'  => $invoice_id,
                'description' => $item_name,
                'quantity'    => $quantity,
                'unit_price'  => $unit_price,
                'total'       => $line_total,
            ]);
        }

        return $invoice_id;
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_payments';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Payment not found', 'asmaa-salon'), 404);
        }

        // Don't allow updating completed payments
        if ($existing->status === 'completed') {
            return $this->error_response(__('Cannot update completed payment', 'asmaa-salon'), 400);
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

        ActivityLogger::log_payment('updated', $id, (int) $existing->wc_customer_id, [
            'changed' => array_keys($data),
            'status' => $item->status ?? null,
        ]);

        // Auto-sync payment to WooCommerce (always enabled)
        \AsmaaSalon\Services\WooCommerce_Integration_Service::sync_payment_to_wc($id);

        return $this->success_response($item, __('Payment updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_payments';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Payment not found', 'asmaa-salon'), 404);
        }

        // Don't allow deleting completed payments
        if ($existing->status === 'completed') {
            return $this->error_response(__('Cannot delete completed payment', 'asmaa-salon'), 400);
        }

        $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
        return $this->success_response(null, __('Payment deleted successfully', 'asmaa-salon'));
    }
}

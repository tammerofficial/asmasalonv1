<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;

use WP_Error;
if (!defined('ABSPATH')) {
    exit;
}

class Invoices_Controller extends Base_Controller
{
    protected string $rest_base = 'invoices';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_invoices_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => $this->permission_callback('asmaa_invoices_create')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_invoices_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => $this->permission_callback('asmaa_invoices_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => $this->permission_callback('asmaa_invoices_delete')],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_invoices';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = ['i.deleted_at IS NULL'];

        $search = sanitize_text_field((string) ($request->get_param('search') ?? ''));
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = $wpdb->prepare('(invoice_number LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)', $like, $like, $like);
        }
        
        $status = $request->get_param('status');
        if ($status) {
            $where[] = $wpdb->prepare('i.status = %s', $status);
        }

        $customer_id = $request->get_param('customer_id');
        if ($customer_id) {
            $where[] = $wpdb->prepare('i.wc_customer_id = %d', $customer_id);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$table} i
             LEFT JOIN {$wpdb->users} u ON u.ID = i.wc_customer_id
             {$where_clause}"
        );

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*,
                    u.display_name AS customer_name,
                    u.user_email AS customer_email,
                    CASE 
                        WHEN i.wc_order_id IS NOT NULL THEN 1 
                        ELSE 0 
                    END AS is_synced_with_wc,
                    i.wc_order_id
             FROM {$table} i
             LEFT JOIN {$wpdb->users} u ON u.ID = i.wc_customer_id
             {$where_clause}
             ORDER BY i.id DESC
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
        $table = $wpdb->prefix . 'asmaa_invoices';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));

        if (!$item) {
            return $this->error_response(__('Invoice not found', 'asmaa-salon'), 404);
        }

        // Load invoice items
        $items_table = $wpdb->prefix . 'asmaa_invoice_items';
        $item->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$items_table} WHERE invoice_id = %d", $id));

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $table = $wpdb->prefix . 'asmaa_invoices';
            $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$table}"), 4, '0', STR_PAD_LEFT);

            $data = [
                'order_id' => $request->get_param('order_id') ? (int) $request->get_param('order_id') : null,
                'wc_customer_id' => (int) $request->get_param('customer_id'),
                'invoice_number' => $invoice_number,
                'issue_date' => sanitize_text_field($request->get_param('issue_date')) ?: date('Y-m-d'),
                'due_date' => sanitize_text_field($request->get_param('due_date')),
                'subtotal' => (float) $request->get_param('subtotal'),
                'discount' => (float) $request->get_param('discount'),
                'tax' => (float) $request->get_param('tax'),
                'total' => (float) $request->get_param('total'),
                'paid_amount' => (float) ($request->get_param('paid_amount') ?? 0),
                'due_amount' => (float) $request->get_param('total'),
                'status' => sanitize_text_field($request->get_param('status')) ?: 'draft',
                'notes' => sanitize_textarea_field($request->get_param('notes')),
            ];

            if (empty($data['wc_customer_id']) || empty($data['total'])) {
                throw new \Exception(__('Customer and total are required', 'asmaa-salon'));
            }

            $result = $wpdb->insert($table, $data);
            if ($result === false) {
                throw new \Exception(__('Failed to create invoice', 'asmaa-salon'));
            }

            $invoice_id = $wpdb->insert_id;

            // Create invoice items
            $items = $request->get_param('items');
            if ($items && is_array($items)) {
                $items_table = $wpdb->prefix . 'asmaa_invoice_items';
                foreach ($items as $item_data) {
                    $wpdb->insert($items_table, [
                        'invoice_id' => $invoice_id,
                        'description' => sanitize_text_field($item_data['description']),
                        'quantity' => (int) $item_data['quantity'],
                        'unit_price' => (float) $item_data['unit_price'],
                        'total' => (float) $item_data['total'],
                    ]);
                }
            }

            // ✅ FIX: Create Payment if invoice status is 'paid'
            if ($data['status'] === 'paid') {
                $payments_table = $wpdb->prefix . 'asmaa_payments';
                $payment_number = 'PAY-' . date('Ymd') . '-' . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);
                
                $payment_data = [
                    'payment_number' => $payment_number,
                    'invoice_id' => $invoice_id,
                    'wc_customer_id' => $data['wc_customer_id'],
                    'order_id' => $data['order_id'],
                    'amount' => $data['total'],
                    'payment_method' => 'cash', // Default
                    'status' => 'completed',
                    'payment_date' => current_time('mysql'),
                    'notes' => 'Payment created with Invoice',
                    'processed_by' => get_current_user_id(),
                ];

                $wpdb->insert($payments_table, $payment_data);
                if ($wpdb->last_error) {
                    throw new \Exception(__('Failed to create payment', 'asmaa-salon'));
                }

                $payment_id = $wpdb->insert_id;
                // Update invoice with payment_id
                $wpdb->update($table, ['payment_id' => $payment_id], ['id' => $invoice_id]);
            }

            $wpdb->query('COMMIT');

            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $invoice_id));
            $items_table = $wpdb->prefix . 'asmaa_invoice_items';
            $item->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$items_table} WHERE invoice_id = %d", $invoice_id));

            // Auto-sync invoice to WooCommerce (always enabled)
            \AsmaaSalon\Services\WooCommerce_Integration_Service::sync_invoice_to_wc($invoice_id);

            return $this->success_response($item, __('Invoice created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $table = $wpdb->prefix . 'asmaa_invoices';
            $id = (int) $request->get_param('id');

            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
            if (!$existing) {
                throw new \Exception(__('Invoice not found', 'asmaa-salon'));
            }

            // Don't allow updating paid invoices
            if ($existing->status === 'paid') {
                throw new \Exception(__('Cannot update paid invoice', 'asmaa-salon'));
            }

            $old_status = $existing->status;
            $data = [];
            $should_create_payment = false;

            if ($request->has_param('status')) {
                $data['status'] = sanitize_text_field($request->get_param('status'));
                // Check if status changed to 'paid'
                if ($old_status !== 'paid' && $data['status'] === 'paid') {
                    $should_create_payment = true;
                }
            }
            
            if ($request->has_param('paid_amount')) {
                $paid_amount = (float) $request->get_param('paid_amount');
                $data['paid_amount'] = $paid_amount;
                $data['due_amount'] = $existing->total - $paid_amount;
                
                // Update status based on payment
                if ($paid_amount >= $existing->total) {
                    $data['status'] = 'paid';
                    // Check if status changed to 'paid'
                    if ($old_status !== 'paid') {
                        $should_create_payment = true;
                    }
                } elseif ($paid_amount > 0) {
                    $data['status'] = 'partial';
                }
            }

            if (empty($data)) {
                throw new \Exception(__('No data to update', 'asmaa-salon'));
            }

            $wpdb->update($table, $data, ['id' => $id]);
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to update invoice', 'asmaa-salon'));
            }

            // ✅ FIX: Create Payment when invoice becomes 'paid'
            if ($should_create_payment) {
                // Check if payment already exists
                $payments_table = $wpdb->prefix . 'asmaa_payments';
                $existing_payment = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$payments_table} WHERE invoice_id = %d",
                    $id
                ));

                if (!$existing_payment) {
                    $payment_number = 'PAY-' . date('Ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                    
                    $payment_data = [
                        'payment_number' => $payment_number,
                        'invoice_id' => $id,
                        'wc_customer_id' => $existing->wc_customer_id,
                        'order_id' => $existing->order_id,
                        'amount' => $existing->total,
                        'payment_method' => 'cash', // Default
                        'status' => 'completed',
                        'payment_date' => current_time('mysql'),
                        'notes' => 'Payment created from Invoice update',
                        'processed_by' => get_current_user_id(),
                    ];

                    $wpdb->insert($payments_table, $payment_data);
                    if ($wpdb->last_error) {
                        throw new \Exception(__('Failed to create payment', 'asmaa-salon'));
                    }

                    $payment_id = $wpdb->insert_id;
                    // Update invoice with payment_id
                    $wpdb->update($table, ['payment_id' => $payment_id], ['id' => $id]);
                }
            }

            $wpdb->query('COMMIT');

            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

            // Auto-sync invoice to WooCommerce (always enabled)
            \AsmaaSalon\Services\WooCommerce_Integration_Service::sync_invoice_to_wc($id);

            return $this->success_response($item, __('Invoice updated successfully', 'asmaa-salon'));

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_invoices';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Invoice not found', 'asmaa-salon'), 404);
        }

        // Don't allow deleting paid invoices
        if ($existing->status === 'paid') {
            return $this->error_response(__('Cannot delete paid invoice', 'asmaa-salon'), 400);
        }

        $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
        return $this->success_response(null, __('Invoice deleted successfully', 'asmaa-salon'));
    }
}

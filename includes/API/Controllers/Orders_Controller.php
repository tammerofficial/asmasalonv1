<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\ActivityLogger;
use AsmaaSalon\Services\NotificationDispatcher;
use AsmaaSalon\Services\Commission_Service;

if (!defined('ABSPATH')) {
    exit;
}

class Orders_Controller extends Base_Controller
{
    protected string $rest_base = 'orders';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_orders_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => $this->permission_callback('asmaa_orders_create')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_orders_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => $this->permission_callback('asmaa_orders_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => $this->permission_callback('asmaa_orders_delete')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/complete', [
            ['methods' => 'POST', 'callback' => [$this, 'complete_order'], 'permission_callback' => $this->permission_callback('asmaa_orders_update')],
        ]);

    }

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!\AsmaaSalon\Services\WooCommerce_Integration_Service::is_woocommerce_active()) {
            return $this->success_response([
                'items' => [],
                'pagination' => $this->build_pagination_meta(0, 1, 20),
                'stats' => ['total' => 0, 'pending' => 0, 'completed' => 0, 'totalRevenue' => 0],
            ]);
        }

        try {
            $params = $this->get_pagination_params($request);
            
            // 1. Get stats and total count efficiently using SQL if possible
            // or use wc_get_orders with limited return for count
            $status = $request->get_param('status');
            $wc_status = 'any';
            if ($status) {
                $status_map = ['pending' => 'pending', 'completed' => 'completed', 'cancelled' => 'cancelled'];
                $wc_status = $status_map[$status] ?? 'any';
            }

            $wc_args = [
                'status' => $wc_status,
                'limit' => $params['per_page'],
                'offset' => ($params['page'] - 1) * $params['per_page'],
                'orderby' => 'date',
                'order' => 'DESC',
                'paginate' => true, // This gives us total and total_pages
            ];

            // Apply filters
            if ($request->get_param('customer_id')) {
                $wc_args['customer_id'] = (int) $request->get_param('customer_id');
            }

            $date_from = $request->get_param('date_from');
            $date_to = $request->get_param('date_to');
            if ($date_from || $date_to) {
                $wc_args['date_created'] = ($date_from ?: '') . '...' . ($date_to ?: '');
            }

            // Get paginated orders
            $results = wc_get_orders($wc_args);
            
            if (is_wp_error($results)) {
                throw new \Exception($results->get_error_message());
            }

            $wc_orders = [];
            $total = 0;

            if (is_object($results) && isset($results->orders)) {
                $wc_orders = $results->orders;
                $total = (int) $results->total;
            } elseif (is_array($results)) {
                 $wc_orders = $results;
                 $total = count($results);
            }
            
            // Convert to our format
            $items = [];
            $order_ids = [];
            foreach ($wc_orders as $wc_order) {
                if ($wc_order instanceof \WC_Order) {
                    $order_ids[] = $wc_order->get_id();
                }
            }

            // Prefetch paid amounts to avoid N+1 queries
            $paid_amounts = [];
            if (!empty($order_ids)) {
                $ids_str = implode(',', array_map('intval', $order_ids));
                $payments_table = $wpdb->prefix . 'asmaa_payments';
                $results = $wpdb->get_results(
                    "SELECT order_id, SUM(amount) as total_paid FROM {$payments_table} 
                     WHERE order_id IN ({$ids_str}) AND status = 'completed' GROUP BY order_id"
                );
                if ($results) {
                    foreach ($results as $row) {
                        $paid_amounts[(int)$row->order_id] = (float)$row->total_paid;
                    }
                }
            }

            foreach ($wc_orders as $wc_order) {
                if ($wc_order instanceof \WC_Order) {
                    $id = $wc_order->get_id();
                    $items[] = $this->format_wc_order($wc_order, $paid_amounts[$id] ?? 0);
                }
            }

            // 2. Get Statistics (Optimized)
            $stats = $this->get_optimized_stats($wc_args);

            return $this->success_response([
                'items' => $items,
                'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            error_log('Asmaa Salon API Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get optimized stats without loading all order objects
     */
    protected function get_optimized_stats(array $filter_args): array
    {
        global $wpdb;
        
        try {
            // 1. Check if we have filters other than status
            $has_filters = false;
            $filtered_args = $filter_args;
            unset($filtered_args['limit'], $filtered_args['offset'], $filtered_args['paginate'], $filtered_args['status'], $filtered_args['orderby'], $filtered_args['order']);
            
            if (!empty($filtered_args)) {
                $has_filters = true;
            }

            if (!$has_filters) {
                // Fast way using wc_orders_count
                $counts = (array) wc_orders_count();
                // WooCommerce statuses have wc- prefix in wc_orders_count
                $pending = ($counts['wc-pending'] ?? 0) + ($counts['wc-processing'] ?? 0) + ($counts['wc-on-hold'] ?? 0);
                $completed = $counts['wc-completed'] ?? 0;
                $total = array_sum($counts);

                // Revenue - use a more robust way that handles HPOS if needed
                // For now, keep it simple but correct statuses
                $revenue = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
                     JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
                     WHERE meta_key = '_order_total' 
                     AND post_status = 'wc-completed' 
                     AND post_type = 'shop_order'"
                ));

                return [
                    'total' => $total,
                    'pending' => $pending,
                    'completed' => $completed,
                    'totalRevenue' => $revenue,
                ];
            }

            // 2. Way with filters (handle WP_Error)
            $base_args = $filter_args;
            unset($base_args['limit'], $base_args['offset'], $base_args['paginate'], $base_args['status']);
            
            // Total
            $total_args = $base_args;
            $total_args['paginate'] = true;
            $total_args['limit'] = 1;
            $total_res = wc_get_orders($total_args);
            $total_count = (is_object($total_res) && isset($total_res->total)) ? (int) $total_res->total : 0;

            // Pending
            $pending_args = $base_args;
            $pending_args['status'] = ['pending', 'processing', 'on-hold'];
            $pending_args['paginate'] = true;
            $pending_args['limit'] = 1;
            $pending_res = wc_get_orders($pending_args);
            $pending_count = (is_object($pending_res) && isset($pending_res->total)) ? (int) $pending_res->total : 0;

            // Completed
            $completed_args = $base_args;
            $completed_args['status'] = 'completed';
            $completed_args['paginate'] = true;
            $completed_args['limit'] = 1;
            $completed_res = wc_get_orders($completed_args);
            $completed_count = (is_object($completed_res) && isset($completed_res->total)) ? (int) $completed_res->total : 0;

            // Revenue (limited)
            $revenue = 0;
            if ($completed_count > 0) {
                $revenue_args = $completed_args;
                unset($revenue_args['paginate'], $revenue_args['limit']);
                $revenue_args['return'] = 'ids';
                $revenue_args['limit'] = 100; // Hard limit for performance
                
                $ids = wc_get_orders($revenue_args);
                if (is_array($ids) && !empty($ids)) {
                    $ids_str = implode(',', array_map('intval', $ids));
                    $revenue = (float) $wpdb->get_var(
                        "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_order_total' AND post_id IN ({$ids_str})"
                    );
                }
            }

            return [
                'total' => $total_count,
                'pending' => $pending_count,
                'completed' => $completed_count,
                'totalRevenue' => $revenue,
            ];
        } catch (\Throwable $e) {
            error_log('Asmaa Salon Stats Error: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'completed' => 0,
                'totalRevenue' => 0,
            ];
        }
    }

    /**
     * Format WooCommerce order to our API format
     */
    protected function format_wc_order($wc_order, float $prefetched_paid = -1.0): object
    {
        if (!$wc_order instanceof \WC_Order) {
            return (object) [];
        }

        $order_id = $wc_order->get_id();
        $customer_id = $wc_order->get_customer_id();
        
        // Basic info
        $customer_name = $wc_order->get_billing_first_name() . ' ' . $wc_order->get_billing_last_name();
        $customer_phone = $wc_order->get_billing_phone();
        
        if (empty(trim($customer_name)) && $customer_id) {
            $user = get_user_by('ID', $customer_id);
            if ($user) $customer_name = $user->display_name;
        }

        // Status mapping
        $wc_status = $wc_order->get_status();
        $status_map = [
            'pending' => 'pending', 'processing' => 'pending', 'on-hold' => 'pending',
            'completed' => 'completed', 'cancelled' => 'cancelled', 'refunded' => 'cancelled', 'failed' => 'cancelled',
        ];
        
        $total_paid = $prefetched_paid;
        if ($total_paid < 0) {
            $total_paid = \AsmaaSalon\Services\Unified_Order_Service::get_total_paid_for_order($order_id);
        }

        $payment_status = $wc_order->is_paid() ? 'paid' : ($total_paid > 0 ? 'partial' : 'unpaid');

        // Items
        $items = [];
        foreach ($wc_order->get_items() as $item_id => $item) {
            // قراءة staff_id من Meta (hidden meta key)
            $staff_id = $item->get_meta('_asmaa_staff_id');
            
            // تحديد نوع العنصر
            $is_service = false;
            if ($item instanceof \WC_Order_Item_Product) {
                $product = $item->get_product();
                $is_service = $product && $product->is_virtual();
            }
            
            $qty = max(1, $item->get_quantity());
            $items[] = (object) [
                'id' => $item_id,
                'item_type' => $is_service ? 'service' : 'product',
                'item_name' => $item->get_name(),
                'quantity' => $qty,
                'unit_price' => (float) $item->get_subtotal() / $qty,
                'total' => (float) $item->get_total(),
                'staff_id' => $staff_id ? (int) $staff_id : null,
            ];
        }

        $date_created = $wc_order->get_date_created();

        return (object) [
            'id' => $order_id,
            'wc_order_id' => $order_id,
            'order_number' => $wc_order->get_order_number(),
            'wc_customer_id' => $customer_id,
            'customer_name' => trim($customer_name) ?: __('Guest', 'asmaa-salon'),
            'customer_phone' => $customer_phone,
            'total' => (float) $wc_order->get_total(),
            'status' => $status_map[$wc_status] ?? 'pending',
            'payment_status' => $payment_status,
            'payment_method' => $wc_order->get_payment_method_title(),
            'created_at' => $date_created ? $date_created->date('Y-m-d H:i:s') : current_time('mysql'),
            'items' => $items,
        ];
    }

    public function complete_order(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!\AsmaaSalon\Services\WooCommerce_Integration_Service::is_woocommerce_active()) {
            return $this->error_response(__('WooCommerce is not active', 'asmaa-salon'), 400);
        }

        $id = (int) $request->get_param('id');
        $wc_order = wc_get_order($id);

        if (!$wc_order) {
            return $this->error_response(__('Order not found', 'asmaa-salon'), 404);
        }

        $old_status = $wc_order->get_status();
        $wc_order->update_status('completed', __('Order completed from Asmaa Salon', 'asmaa-salon'));
        
        $updated = $this->format_wc_order($wc_order);

        ActivityLogger::log_order('completed', $id, $wc_order->get_customer_id(), [
            'from' => $old_status,
            'to' => 'completed',
        ]);

        return $this->success_response($updated, __('Order completed successfully', 'asmaa-salon'));
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!\AsmaaSalon\Services\WooCommerce_Integration_Service::is_woocommerce_active()) {
            return $this->error_response(__('WooCommerce is not active', 'asmaa-salon'), 400);
        }

        $id = (int) $request->get_param('id');
        $wc_order = wc_get_order($id);

        if (!$wc_order) {
            return $this->error_response(__('Order not found', 'asmaa-salon'), 404);
        }

        $item = $this->format_wc_order($wc_order);

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $customer_id = (int) $request->get_param('customer_id');
            $subtotal = (float) $request->get_param('subtotal');
            $discount = (float) ($request->get_param('discount') ?? 0);
            $tax = (float) ($request->get_param('tax') ?? 0);
            $total = (float) $request->get_param('total');
            $status = sanitize_text_field($request->get_param('status')) ?: 'pending';
            $payment_status = sanitize_text_field($request->get_param('payment_status')) ?: 'unpaid';
            $payment_method = sanitize_text_field($request->get_param('payment_method'));
            $notes = sanitize_textarea_field($request->get_param('notes'));
            $booking_id = $request->get_param('booking_id') ? (int) $request->get_param('booking_id') : null;

            if (empty($customer_id) || empty($total)) {
                throw new \Exception(__('Customer and total are required', 'asmaa-salon'));
            }

            // Create WooCommerce order directly
            $wc_order = wc_create_order([
                'customer_id' => $customer_id,
                'status' => $status === 'completed' ? 'wc-completed' : ($status === 'cancelled' ? 'wc-cancelled' : 'wc-pending'),
            ]);

            if (is_wp_error($wc_order)) {
                throw new \Exception($wc_order->get_error_message());
            }

            $wc_order_id = $wc_order->get_id();
            $order_number = $wc_order->get_order_number();

            // Add items to WooCommerce order
            $items = $request->get_param('items');
            $created_items = [];
            if ($items && is_array($items)) {
                foreach ($items as $item_data) {
                    $item_type = sanitize_text_field($item_data['item_type']);
                    $item_id = (int) $item_data['item_id'];
                    $item_name = sanitize_text_field($item_data['item_name']);
                    $quantity = (int) $item_data['quantity'];
                    $unit_price = (float) $item_data['unit_price'];
                    $item_total = (float) $item_data['total'];
                    $staff_id = isset($item_data['staff_id']) ? (int) $item_data['staff_id'] : null;

                    if ($item_type === 'product') {
                        $wc_product = wc_get_product($item_id);
                    if ($wc_product) {
                        $wc_order->add_product($wc_product, $quantity, [
                            'subtotal' => $unit_price * $quantity,
                            'total' => $item_total,
                        ]);
                        
                        // Get the last added item and save staff_id in meta
                        $items = $wc_order->get_items();
                        $last_item = end($items);
                        if ($last_item && $staff_id) {
                            $last_item->add_meta_data('_asmaa_staff_id', $staff_id);
                            $last_item->save();
                        }
                    }
                } else {
                    // Add service as Virtual Product
                    $service_product_id = \AsmaaSalon\Services\Product_Service::get_or_create_service_product(
                        $item_id,
                        $item_name,
                        $unit_price
                    );
                    
                    $wc_product = wc_get_product($service_product_id);
                    if (!$wc_product) {
                        throw new \Exception(__('Service product not found', 'asmaa-salon'));
                    }
                    
                    // Add service as virtual product
                    $wc_order->add_product($wc_product, $quantity, [
                        'subtotal' => $unit_price * $quantity,
                        'total' => $item_total,
                    ]);
                    
                    // Get the last added item and save staff_id in meta
                    $items = $wc_order->get_items();
                    $last_item = end($items);
                    if ($last_item && $staff_id) {
                        $last_item->add_meta_data('_asmaa_staff_id', $staff_id);
                        $last_item->save();
                    }
                }

                    $created_items[] = (object) [
                        'id' => 0, // WC order items don't have our IDs
                        'order_id' => $wc_order_id,
                        'item_type' => $item_type,
                        'item_id' => $item_id,
                        'item_name' => $item_name,
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'discount' => (float) ($item_data['discount'] ?? 0),
                        'total' => $item_total,
                        'staff_id' => $staff_id,
                    ];
                }
            }

            // Set order totals
            $wc_order->set_discount_total($discount);
            $wc_order->set_total_tax($tax);
            $wc_order->calculate_totals(); // This will set subtotal and total based on items
            $wc_order->set_total($total); // Explicitly set total if different from calculated
            $wc_order->set_payment_method($payment_method);
            $wc_order->set_payment_method_title($payment_method);
            
            if ($payment_status === 'paid') {
                $wc_order->payment_complete();
            }
            
            if ($notes) {
                $wc_order->add_order_note($notes);
            }
            
            $wc_order->save();

            // Process loyalty points for manual orders if completed
            if ($customer_id && $status === 'completed') {
                try {
                    \AsmaaSalon\Services\Loyalty_Service::process_order_points(
                        $customer_id,
                        $wc_order_id,
                        $order_number,
                        $created_items,
                        $total
                    );
                } catch (\Throwable $e) {
                    error_log('Asmaa Salon: Loyalty processing failed for manual order #' . $wc_order_id . ' - ' . $e->getMessage());
                }
            }

            // Auto-create staff commissions for service items using unified service
            Commission_Service::calculate_from_wc_order($wc_order_id, $booking_id);

            // Activity log
            ActivityLogger::log_order('created', $wc_order_id, $customer_id, [
                'status' => $status,
                'payment_status' => $payment_status,
                'total' => $total,
                'items_count' => count($created_items),
                'booking_id' => $booking_id,
                'wc_order_id' => $wc_order_id,
            ]);

            // Dashboard notification (admins)
            NotificationDispatcher::dashboard_admins('Dashboard.OrderCreated', [
                'event' => 'order.created',
                'order_id' => $wc_order_id,
                'order_number' => $order_number,
                'customer_id' => $customer_id,
                'total' => $total,
                'payment_status' => $payment_status,
                'title_en' => 'New order created',
                'message_en' => sprintf('%s created (%.3f KWD)', $order_number, $total),
                'title_ar' => 'تم إنشاء طلب جديد',
                'message_ar' => sprintf('تم إنشاء %s بقيمة %.3f د.ك', $order_number, $total),
                'action' => [
                    'route' => '/orders',
                ],
                'severity' => 'info',
            ]);

            $wpdb->query('COMMIT');

            // Format response
            $item = $this->format_wc_order($wc_order);

            return $this->success_response($item, __('Order created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }


    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $id = (int) $request->get_param('id');
        $wc_order = wc_get_order($id);

        if (!$wc_order) {
            return $this->error_response(__('Order not found', 'asmaa-salon'), 404);
        }

        $customer_id = $wc_order->get_customer_id();
        $changed = [];

        // Update status
        if ($request->has_param('status')) {
            $status = sanitize_text_field($request->get_param('status'));
            $wc_status = $status === 'completed' ? 'wc-completed' : ($status === 'cancelled' ? 'wc-cancelled' : 'wc-pending');
            $wc_order->set_status($wc_status);
            $changed[] = 'status';
        }

        // Update payment status
        if ($request->has_param('payment_status')) {
            $payment_status = sanitize_text_field($request->get_param('payment_status'));
            if ($payment_status === 'paid' && !$wc_order->is_paid()) {
                $wc_order->payment_complete();
            }
            $changed[] = 'payment_status';
        }

        // Update payment method
        if ($request->has_param('payment_method')) {
            $payment_method = sanitize_text_field($request->get_param('payment_method'));
            $wc_order->set_payment_method($payment_method);
            $wc_order->set_payment_method_title($payment_method);
            $changed[] = 'payment_method';
        }

        // Update notes
        if ($request->has_param('notes')) {
            $notes = sanitize_textarea_field($request->get_param('notes'));
            $wc_order->add_order_note($notes);
            $changed[] = 'notes';
        }

        if (empty($changed)) {
            return $this->error_response(__('No data to update', 'asmaa-salon'), 400);
        }

        $wc_order->save();

        ActivityLogger::log_order('updated', $id, $customer_id, [
            'changed' => $changed,
            'status' => $wc_order->get_status(),
            'payment_status' => $wc_order->is_paid() ? 'paid' : 'unpaid',
            'total' => (float) $wc_order->get_total(),
            'wc_order_id' => $id,
        ]);

        $item = $this->format_wc_order($wc_order);

        return $this->success_response($item, __('Order updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $id = (int) $request->get_param('id');
        $wc_order = wc_get_order($id);

        if (!$wc_order) {
            return $this->error_response(__('Order not found', 'asmaa-salon'), 404);
        }

        $customer_id = $wc_order->get_customer_id();

        // Move to trash (WooCommerce way)
        $wc_order->delete();

        ActivityLogger::log_order('deleted', $id, $customer_id, [
            'wc_order_id' => $id,
        ]);

        return $this->success_response(null, __('Order deleted successfully', 'asmaa-salon'));
    }
}

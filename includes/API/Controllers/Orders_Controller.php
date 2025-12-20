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

    public function get_items(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_orders';
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $staff_table = $wpdb->prefix . 'asmaa_staff';
        $order_items_table = $wpdb->prefix . 'asmaa_order_items';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        // IMPORTANT: Always prefix columns with table alias to avoid ambiguity
        // (e.g. both orders and customers have deleted_at).
        $where = ['o.deleted_at IS NULL'];

        $search = sanitize_text_field((string) ($request->get_param('search') ?? ''));
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = $wpdb->prepare('(o.order_number LIKE %s OR c.name LIKE %s OR c.phone LIKE %s)', $like, $like, $like);
        }
        
        $status = $request->get_param('status');
        if ($status) {
            $where[] = $wpdb->prepare('o.status = %s', $status);
        }

        $payment_status = $request->get_param('payment_status');
        if ($payment_status) {
            $where[] = $wpdb->prepare('o.payment_status = %s', $payment_status);
        }

        $payment_method = $request->get_param('payment_method');
        if ($payment_method) {
            $where[] = $wpdb->prepare('o.payment_method = %s', sanitize_text_field((string) $payment_method));
        }

        $customer_id = $request->get_param('customer_id');
        if ($customer_id) {
            $where[] = $wpdb->prepare('o.customer_id = %d', $customer_id);
        }

        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        if ($date_from && $date_to) {
            $where[] = $wpdb->prepare('o.created_at BETWEEN %s AND %s', $date_from . ' 00:00:00', $date_to . ' 23:59:59');
        } elseif ($date_from) {
            $where[] = $wpdb->prepare('o.created_at >= %s', $date_from . ' 00:00:00');
        } elseif ($date_to) {
            $where[] = $wpdb->prepare('o.created_at <= %s', $date_to . ' 23:59:59');
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$table} o
             LEFT JOIN {$customers_table} c ON c.id = o.customer_id
             {$where_clause}"
        );

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT
                o.*,
                c.name AS customer_name,
                c.phone AS customer_phone,
                s.name AS staff_name,
                COALESCE(oi_staff.staff_name, '') AS staff_name_from_items
             FROM {$table} o
             LEFT JOIN {$customers_table} c ON c.id = o.customer_id
             LEFT JOIN {$staff_table} s ON s.id = o.staff_id
             LEFT JOIN (
                SELECT
                    oi.order_id,
                    SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT st.name ORDER BY st.id SEPARATOR ', '), ', ', 1) AS staff_name
                FROM {$order_items_table} oi
                LEFT JOIN {$staff_table} st ON st.id = oi.staff_id
                WHERE oi.staff_id IS NOT NULL
                GROUP BY oi.order_id
             ) AS oi_staff ON oi_staff.order_id = o.id
             {$where_clause}
             ORDER BY o.id DESC
             LIMIT %d OFFSET %d",
            $params['per_page'],
            $offset
        ));

        // Load order items for each order
        foreach ($items as $item) {
            $item->staff_name = !empty($item->staff_name) ? $item->staff_name : (!empty($item->staff_name_from_items) ? $item->staff_name_from_items : null);
            unset($item->staff_name_from_items);

            $item->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$order_items_table} WHERE order_id = %d", $item->id));
        }

        // Stats for the filtered dataset (not just current page)
        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                COALESCE(SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END), 0) AS completed,
                COALESCE(SUM(o.total), 0) AS total_revenue
             FROM {$table} o
             LEFT JOIN {$customers_table} c ON c.id = o.customer_id
             {$where_clause}"
        );

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
            'stats' => [
                'total' => (int) ($stats->total ?? 0),
                'pending' => (int) ($stats->pending ?? 0),
                'completed' => (int) ($stats->completed ?? 0),
                'totalRevenue' => (float) ($stats->total_revenue ?? 0),
            ],
        ]);
    }

    public function complete_order(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_orders';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Order not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['status' => 'completed'], ['id' => $id]);
        $updated = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        ActivityLogger::log_order('completed', $id, (int) ($existing->customer_id ?? 0), [
            'from' => (string) ($existing->status ?? ''),
            'to' => 'completed',
        ]);

        return $this->success_response($updated, __('Order completed successfully', 'asmaa-salon'));
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_orders';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));

        if (!$item) {
            return $this->error_response(__('Order not found', 'asmaa-salon'), 404);
        }

        // Load order items
        $items_table = $wpdb->prefix . 'asmaa_order_items';
        $item->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$items_table} WHERE order_id = %d", $id));

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $table = $wpdb->prefix . 'asmaa_orders';
            $order_number = 'ORD-' . date('Ymd') . '-' . str_pad($wpdb->get_var("SELECT COUNT(*) + 1 FROM {$table}"), 4, '0', STR_PAD_LEFT);

            $data = [
                'customer_id' => (int) $request->get_param('customer_id'),
                'staff_id' => $request->get_param('staff_id') ? (int) $request->get_param('staff_id') : null,
                'booking_id' => $request->get_param('booking_id') ? (int) $request->get_param('booking_id') : null,
                'order_number' => $order_number,
                'subtotal' => (float) $request->get_param('subtotal'),
                'discount' => (float) $request->get_param('discount'),
                'tax' => (float) $request->get_param('tax'),
                'total' => (float) $request->get_param('total'),
                'status' => sanitize_text_field($request->get_param('status')) ?: 'pending',
                'payment_status' => sanitize_text_field($request->get_param('payment_status')) ?: 'unpaid',
                'payment_method' => sanitize_text_field($request->get_param('payment_method')),
                'notes' => sanitize_textarea_field($request->get_param('notes')),
            ];

            if (empty($data['customer_id']) || empty($data['total'])) {
                throw new \Exception(__('Customer and total are required', 'asmaa-salon'));
            }

            $result = $wpdb->insert($table, $data);
            if ($result === false) {
                throw new \Exception(__('Failed to create order', 'asmaa-salon'));
            }

            $order_id = $wpdb->insert_id;

            // Create order items
            $items = $request->get_param('items');
            $created_items = [];
            if ($items && is_array($items)) {
                $items_table = $wpdb->prefix . 'asmaa_order_items';
                foreach ($items as $item_data) {
                    $wpdb->insert($items_table, [
                        'order_id' => $order_id,
                        'item_type' => sanitize_text_field($item_data['item_type']),
                        'item_id' => (int) $item_data['item_id'],
                        'item_name' => sanitize_text_field($item_data['item_name']),
                        'quantity' => (int) $item_data['quantity'],
                        'unit_price' => (float) $item_data['unit_price'],
                        'discount' => (float) ($item_data['discount'] ?? 0),
                        'total' => (float) $item_data['total'],
                        'staff_id' => isset($item_data['staff_id']) ? (int) $item_data['staff_id'] : null,
                    ]);
                    $created_items[] = (object) [
                        'id'        => $wpdb->insert_id,
                        'order_id'  => $order_id,
                        'item_type' => sanitize_text_field($item_data['item_type']),
                        'item_id'   => (int) $item_data['item_id'],
                        'item_name' => sanitize_text_field($item_data['item_name']),
                        'quantity'  => (int) $item_data['quantity'],
                        'unit_price'=> (float) $item_data['unit_price'],
                        'discount'  => (float) ($item_data['discount'] ?? 0),
                        'total'     => (float) $item_data['total'],
                        'staff_id'  => isset($item_data['staff_id']) ? (int) $item_data['staff_id'] : null,
                    ];
                }
            }

            // Auto-create staff commissions for service items based on settings and ratings
            $this->create_staff_commissions_for_order($order_id, $data['booking_id'] ?: null, $created_items);

            // Activity log
            ActivityLogger::log_order('created', $order_id, $data['customer_id'], [
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'total' => $data['total'],
                'items_count' => count($created_items),
                'booking_id' => $data['booking_id'],
            ]);

            // Dashboard notification (admins)
            NotificationDispatcher::dashboard_admins('Dashboard.OrderCreated', [
                'event' => 'order.created',
                'order_id' => (int) $order_id,
                'order_number' => (string) $order_number,
                'customer_id' => (int) $data['customer_id'],
                'total' => (float) $data['total'],
                'payment_status' => (string) $data['payment_status'],
                'title_en' => 'New order created',
                'message_en' => sprintf('%s created (%.3f KWD)', (string) $order_number, (float) $data['total']),
                'title_ar' => '🛒 تم إنشاء طلب جديد',
                'message_ar' => sprintf('تم إنشاء %s بقيمة %.3f د.ك', (string) $order_number, (float) $data['total']),
                'action' => [
                    'route' => '/orders',
                ],
                'severity' => 'info',
            ]);

            $wpdb->query('COMMIT');

            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $order_id));
            $items_table = $wpdb->prefix . 'asmaa_order_items';
            $item->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$items_table} WHERE order_id = %d", $order_id));

            return $this->success_response($item, __('Order created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Create staff commission records for order service items.
     */
    protected function create_staff_commissions_for_order(int $order_id, ?int $booking_id, array $items): void
    {
        if (empty($items)) {
            return;
        }

        global $wpdb;

        $staff_table       = $wpdb->prefix . 'asmaa_staff';
        $settings_table    = $wpdb->prefix . 'asmaa_commission_settings';
        $ratings_table     = $wpdb->prefix . 'asmaa_staff_ratings';
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';

        $settings = $wpdb->get_row("SELECT * FROM {$settings_table} ORDER BY id DESC LIMIT 1");

        $default_service_rate = $settings ? (float) $settings->service_commission_rate : 0.0;
        $bonus_5_star         = $settings ? (float) $settings->rating_bonus_5_star : 0.0;
        $bonus_4_star         = $settings ? (float) $settings->rating_bonus_4_star : 0.0;

        foreach ($items as $item) {
            // Only services have commission in this phase
            if ($item->item_type !== 'service' || empty($item->staff_id)) {
                continue;
            }

            // Determine commission rate (staff-specific overrides default)
            $staff = $wpdb->get_row($wpdb->prepare(
                "SELECT commission_rate FROM {$staff_table} WHERE id = %d AND deleted_at IS NULL",
                (int) $item->staff_id
            ));

            $rate = $staff && $staff->commission_rate !== null
                ? (float) $staff->commission_rate
                : $default_service_rate;

            if ($rate <= 0) {
                continue;
            }

            $base_amount       = (float) $item->total;
            $commission_amount = round($base_amount * ($rate / 100), 3);

            // Rating bonus based on booking rating (latest)
            $rating_bonus = 0.0;
            if ($booking_id) {
                $rating = $wpdb->get_row($wpdb->prepare(
                    "SELECT rating FROM {$ratings_table} WHERE booking_id = %d ORDER BY created_at DESC LIMIT 1",
                    (int) $booking_id
                ));

                if ($rating) {
                    if ((int) $rating->rating === 5 && $bonus_5_star > 0) {
                        $rating_bonus = $bonus_5_star;
                    } elseif ((int) $rating->rating === 4 && $bonus_4_star > 0) {
                        $rating_bonus = $bonus_4_star;
                    }
                }
            }

            $final_amount = $commission_amount + $rating_bonus;

            $wpdb->insert(
                $commissions_table,
                [
                    'staff_id'         => (int) $item->staff_id,
                    'order_id'         => $order_id,
                    'order_item_id'    => $item->id,
                    'booking_id'       => $booking_id ?: null,
                    'base_amount'      => $base_amount,
                    'commission_rate'  => $rate,
                    'commission_amount'=> $commission_amount,
                    'rating_bonus'     => $rating_bonus,
                    'final_amount'     => $final_amount,
                    'status'           => 'pending',
                    'notes'            => sprintf(
                        'Auto commission from order #%d for item %s',
                        $order_id,
                        $item->item_name
                    ),
                ]
            );
        }
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_orders';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Order not found', 'asmaa-salon'), 404);
        }

        $data = [];
        $fields = ['status', 'payment_status', 'payment_method', 'notes'];

        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if ($field === 'notes') {
                    $data[$field] = sanitize_textarea_field($request->get_param($field));
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

        ActivityLogger::log_order('updated', $id, (int) $existing->customer_id, [
            'changed' => array_keys($data),
            'status' => $item->status ?? null,
            'payment_status' => $item->payment_status ?? null,
            'total' => $item->total ?? null,
        ]);

        return $this->success_response($item, __('Order updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_orders';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Order not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);

        ActivityLogger::log_order('deleted', $id, (int) $existing->customer_id, [
            'soft_delete' => true,
        ]);

        return $this->success_response(null, __('Order deleted successfully', 'asmaa-salon'));
    }
}

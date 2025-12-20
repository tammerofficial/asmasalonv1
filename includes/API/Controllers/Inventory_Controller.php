<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;

use WP_Error;
use AsmaaSalon\Services\NotificationDispatcher;
if (!defined('ABSPATH')) {
    exit;
}

class Inventory_Controller extends Base_Controller
{
    protected string $rest_base = 'inventory';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/movements', [
            ['methods' => 'GET', 'callback' => [$this, 'get_movements'], 'permission_callback' => $this->permission_callback('asmaa_inventory_movements')],
            ['methods' => 'POST', 'callback' => [$this, 'create_movement'], 'permission_callback' => $this->permission_callback('asmaa_inventory_manage')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/low-stock', [
            ['methods' => 'GET', 'callback' => [$this, 'get_low_stock'], 'permission_callback' => $this->permission_callback('asmaa_inventory_alerts')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/adjust', [
            ['methods' => 'POST', 'callback' => [$this, 'adjust_stock'], 'permission_callback' => $this->permission_callback('asmaa_inventory_adjust')],
        ]);
    }

    public function get_movements(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_inventory_movements';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = ['deleted_at IS NULL'];
        
        $product_id = $request->get_param('product_id');
        if ($product_id) {
            $where[] = $wpdb->prepare('product_id = %d', $product_id);
        }

        $type = $request->get_param('type');
        if ($type) {
            $where[] = $wpdb->prepare('type = %s', $type);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $params['per_page'],
            $offset
        ));

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function create_movement(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $products_table = $wpdb->prefix . 'asmaa_products';
            $movements_table = $wpdb->prefix . 'asmaa_inventory_movements';

            $product_id = (int) $request->get_param('product_id');
            $type = sanitize_text_field($request->get_param('type'));
            $quantity = (int) $request->get_param('quantity');
            $unit_cost = (float) ($request->get_param('unit_cost') ?: 0);
            $notes = sanitize_textarea_field($request->get_param('notes'));

            if (empty($product_id) || empty($type) || $quantity == 0) {
                throw new \Exception(__('Product ID, type and quantity are required', 'asmaa-salon'));
            }

            // Get current stock
            $product = $wpdb->get_row($wpdb->prepare("SELECT stock_quantity FROM {$products_table} WHERE id = %d", $product_id));
            if (!$product) {
                throw new \Exception(__('Product not found', 'asmaa-salon'));
            }

            $before_quantity = (int) $product->stock_quantity;
            
            // Calculate after quantity based on type
            if (in_array($type, ['purchase', 'return'])) {
                $after_quantity = $before_quantity + abs($quantity);
            } elseif (in_array($type, ['sale', 'adjustment'])) {
                $after_quantity = $before_quantity - abs($quantity);
                if ($after_quantity < 0 && $type === 'sale') {
                    throw new \Exception(__('Insufficient stock', 'asmaa-salon'));
                }
            } else {
                throw new \Exception(__('Invalid movement type', 'asmaa-salon'));
            }

            // Create movement record
            $wpdb->insert($movements_table, [
                'product_id' => $product_id,
                'type' => $type,
                'quantity' => $quantity,
                'before_quantity' => $before_quantity,
                'after_quantity' => $after_quantity,
                'unit_cost' => $unit_cost,
                'total_cost' => $unit_cost * abs($quantity),
                'notes' => $notes,
                'performed_by' => get_current_user_id(),
                'movement_date' => current_time('mysql'),
            ]);

            // Update product stock
            $wpdb->update($products_table, ['stock_quantity' => $after_quantity], ['id' => $product_id]);

            // Low stock dashboard notification (admins)
            $product_meta = $wpdb->get_row($wpdb->prepare(
                "SELECT name, stock_quantity, min_stock_level FROM {$products_table} WHERE id = %d",
                $product_id
            ));
            if ($product_meta && (int) $product_meta->min_stock_level > 0 && (int) $product_meta->stock_quantity <= (int) $product_meta->min_stock_level) {
                NotificationDispatcher::dashboard_admins('Dashboard.InventoryLowStock', [
                    'event' => 'inventory.low_stock',
                    'product_id' => (int) $product_id,
                    'product_name' => (string) $product_meta->name,
                    'stock_quantity' => (int) $product_meta->stock_quantity,
                    'min_stock_level' => (int) $product_meta->min_stock_level,
                    'title_en' => 'Low stock alert',
                    'message_en' => sprintf('%s is low (%d left)', (string) $product_meta->name, (int) $product_meta->stock_quantity),
                    'title_ar' => '⚠️ تنبيه مخزون منخفض',
                    'message_ar' => sprintf('المنتج %s مخزونه منخفض (%d متبقي)', (string) $product_meta->name, (int) $product_meta->stock_quantity),
                    'action' => [
                        'route' => '/inventory',
                        'query' => ['tab' => 'inventory'],
                    ],
                    'severity' => 'warning',
                ]);
            }

            $wpdb->query('COMMIT');

            $movement = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$movements_table} WHERE id = %d", $wpdb->insert_id));
            return $this->success_response($movement, __('Inventory movement created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    public function get_low_stock(): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_products';

        $items = $wpdb->get_results(
            "SELECT * FROM {$table} 
             WHERE stock_quantity <= min_stock_level 
             AND is_active = 1 
             AND deleted_at IS NULL 
             ORDER BY stock_quantity ASC"
        );

        return $this->success_response($items);
    }

    public function adjust_stock(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $products_table = $wpdb->prefix . 'asmaa_products';
            $movements_table = $wpdb->prefix . 'asmaa_inventory_movements';

            $product_id = (int) $request->get_param('product_id');
            $new_quantity = (int) $request->get_param('new_quantity');
            $notes = sanitize_textarea_field($request->get_param('notes'));

            if (empty($product_id) || $new_quantity < 0) {
                throw new \Exception(__('Product ID and valid quantity are required', 'asmaa-salon'));
            }

            // Get current stock
            $product = $wpdb->get_row($wpdb->prepare("SELECT stock_quantity FROM {$products_table} WHERE id = %d", $product_id));
            if (!$product) {
                throw new \Exception(__('Product not found', 'asmaa-salon'));
            }

            $before_quantity = (int) $product->stock_quantity;
            $difference = $new_quantity - $before_quantity;

            if ($difference == 0) {
                throw new \Exception(__('No change in quantity', 'asmaa-salon'));
            }

            // Create adjustment movement
            $wpdb->insert($movements_table, [
                'product_id' => $product_id,
                'type' => 'adjustment',
                'quantity' => $difference,
                'before_quantity' => $before_quantity,
                'after_quantity' => $new_quantity,
                'notes' => $notes ?: __('Manual stock adjustment', 'asmaa-salon'),
                'performed_by' => get_current_user_id(),
                'movement_date' => current_time('mysql'),
            ]);

            // Update product stock
            $wpdb->update($products_table, ['stock_quantity' => $new_quantity], ['id' => $product_id]);

            // Low stock dashboard notification (admins)
            $product_meta = $wpdb->get_row($wpdb->prepare(
                "SELECT name, stock_quantity, min_stock_level FROM {$products_table} WHERE id = %d",
                $product_id
            ));
            if ($product_meta && (int) $product_meta->min_stock_level > 0 && (int) $product_meta->stock_quantity <= (int) $product_meta->min_stock_level) {
                NotificationDispatcher::dashboard_admins('Dashboard.InventoryLowStock', [
                    'event' => 'inventory.low_stock',
                    'product_id' => (int) $product_id,
                    'product_name' => (string) $product_meta->name,
                    'stock_quantity' => (int) $product_meta->stock_quantity,
                    'min_stock_level' => (int) $product_meta->min_stock_level,
                    'title_en' => 'Low stock alert',
                    'message_en' => sprintf('%s is low (%d left)', (string) $product_meta->name, (int) $product_meta->stock_quantity),
                    'title_ar' => '⚠️ تنبيه مخزون منخفض',
                    'message_ar' => sprintf('المنتج %s مخزونه منخفض (%d متبقي)', (string) $product_meta->name, (int) $product_meta->stock_quantity),
                    'action' => [
                        'route' => '/inventory',
                        'query' => ['tab' => 'inventory'],
                    ],
                    'severity' => 'warning',
                ]);
            }

            $wpdb->query('COMMIT');

            return $this->success_response([
                'product_id' => $product_id,
                'before_quantity' => $before_quantity,
                'after_quantity' => $new_quantity,
            ], __('Stock adjusted successfully', 'asmaa-salon'));
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }
}

<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;

use WP_Error;
if (!defined('ABSPATH')) {
    exit;
}

class Products_Controller extends Base_Controller
{
    protected string $rest_base = 'products';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_products_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => $this->permission_callback('asmaa_products_create')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_products_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => $this->permission_callback('asmaa_products_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => $this->permission_callback('asmaa_products_delete')],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_products';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = ['deleted_at IS NULL'];
        
        $search = $request->get_param('search');
        if ($search) {
            $where[] = $wpdb->prepare('(name LIKE %s OR name_ar LIKE %s OR sku LIKE %s)', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }

        $category = $request->get_param('category');
        if ($category) {
            $where[] = $wpdb->prepare('category = %s', $category);
        }

        $is_active = $request->get_param('is_active');
        if ($is_active !== null) {
            $where[] = $wpdb->prepare('is_active = %d', $is_active ? 1 : 0);
        }

        $low_stock = $request->get_param('low_stock');
        if ($low_stock) {
            $where[] = 'stock_quantity <= min_stock_level';
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} {$where_clause} ORDER BY id DESC LIMIT %d OFFSET %d",
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
        $table = $wpdb->prefix . 'asmaa_products';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));

        if (!$item) {
            return $this->error_response(__('Product not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_products';

        $data = [
            'name' => sanitize_text_field($request->get_param('name')),
            'name_ar' => sanitize_text_field($request->get_param('name_ar')),
            'sku' => sanitize_text_field($request->get_param('sku')),
            'barcode' => sanitize_text_field($request->get_param('barcode')),
            'description' => sanitize_textarea_field($request->get_param('description')),
            'category' => sanitize_text_field($request->get_param('category')),
            'brand' => sanitize_text_field($request->get_param('brand')),
            'purchase_price' => (float) $request->get_param('purchase_price'),
            'selling_price' => (float) $request->get_param('selling_price'),
            'stock_quantity' => (int) $request->get_param('stock_quantity'),
            'min_stock_level' => (int) $request->get_param('min_stock_level'),
            'unit' => sanitize_text_field($request->get_param('unit')),
            'image' => esc_url_raw($request->get_param('image')),
            'is_active' => $request->get_param('is_active') ? 1 : 0,
        ];

        if (empty($data['name']) || empty($data['sku'])) {
            return $this->error_response(__('Name and SKU are required', 'asmaa-salon'), 400);
        }

        // Check SKU uniqueness
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE sku = %s AND deleted_at IS NULL", $data['sku']));
        if ($existing) {
            return $this->error_response(__('SKU already exists', 'asmaa-salon'), 400);
        }

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return $this->error_response(__('Failed to create product', 'asmaa-salon'), 500);
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id));
        return $this->success_response($item, __('Product created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_products';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Product not found', 'asmaa-salon'), 404);
        }

        $data = [];
        $fields = ['name', 'name_ar', 'sku', 'barcode', 'description', 'category', 'brand', 'purchase_price', 'selling_price', 'stock_quantity', 'min_stock_level', 'unit', 'image', 'is_active'];

        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if ($field === 'description') {
                    $data[$field] = sanitize_textarea_field($request->get_param($field));
                } elseif (in_array($field, ['purchase_price', 'selling_price'])) {
                    $data[$field] = (float) $request->get_param($field);
                } elseif (in_array($field, ['stock_quantity', 'min_stock_level'])) {
                    $data[$field] = (int) $request->get_param($field);
                } elseif ($field === 'is_active') {
                    $data[$field] = $request->get_param($field) ? 1 : 0;
                } elseif ($field === 'image') {
                    $data[$field] = esc_url_raw($request->get_param($field));
                } else {
                    $data[$field] = sanitize_text_field($request->get_param($field));
                }
            }
        }

        // Check SKU uniqueness if changed
        if (isset($data['sku']) && $data['sku'] !== $existing->sku) {
            $duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE sku = %s AND id != %d AND deleted_at IS NULL", $data['sku'], $id));
            if ($duplicate) {
                return $this->error_response(__('SKU already exists', 'asmaa-salon'), 400);
            }
        }

        if (empty($data)) {
            return $this->error_response(__('No data to update', 'asmaa-salon'), 400);
        }

        $wpdb->update($table, $data, ['id' => $id]);
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        // ✅ FIX: Check for low stock after update
        if (isset($data['stock_quantity']) || isset($data['min_stock_level'])) {
            $current_stock = (int) $item->stock_quantity;
            $min_stock = (int) $item->min_stock_level;
            $old_stock = (int) $existing->stock_quantity;
            
            // Send notification if stock reaches or falls below minimum (including 0)
            if ($current_stock <= $min_stock && $old_stock > $min_stock) {
                // Only send if this is the first time crossing the threshold
                \AsmaaSalon\Services\NotificationDispatcher::low_stock_alert($id, [
                    'name' => $item->name ?? $item->name_ar ?? 'Product',
                    'current_stock' => $current_stock,
                    'min_stock_level' => $min_stock,
                    'sku' => $item->sku ?? '',
                ]);
            }
        }

        return $this->success_response($item, __('Product updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_products';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Product not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
        return $this->success_response(null, __('Product deleted successfully', 'asmaa-salon'));
    }
}

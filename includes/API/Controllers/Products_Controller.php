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

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        global $wpdb;
        $params = $this->get_pagination_params($request);

        // Get WooCommerce products
        $args = [
            'limit' => $params['per_page'],
            'offset' => ($params['page'] - 1) * $params['per_page'],
            'status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Add search filter
        $search = $request->get_param('search');
        if ($search) {
            $args['s'] = $search;
        }

        // Add category filter
        $category = $request->get_param('category');
        if ($category) {
            $args['category'] = [$category];
        }

        // Add status filter
        $is_active = $request->get_param('is_active');
        if ($is_active !== null) {
            // WooCommerce doesn't have is_active, we'll filter by stock status
            // We'll handle this after getting products
        }

        $products = wc_get_products($args);
        $total = wc_get_products(array_merge($args, ['limit' => -1, 'return' => 'ids']));
        $total = is_array($total) ? count($total) : 0;

        // Get extended data for all products
        $extended_table = $wpdb->prefix . 'asmaa_product_extended_data';
        $product_ids = array_map(fn($product) => $product->get_id(), $products);
        
        $extended_data_map = [];
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
            $extended_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$extended_table} WHERE wc_product_id IN ({$placeholders})",
                    ...$product_ids
                )
            );
            
            foreach ($extended_rows as $row) {
                $extended_data_map[$row->wc_product_id] = $row;
            }
        }

        // Format response
        $items = [];
        foreach ($products as $product) {
            $product_data = $this->format_product_data($product, $extended_data_map[$product->get_id()] ?? null);
            
            // Filter by is_active if requested
            if ($is_active !== null) {
                $active = $product->get_stock_status() === 'instock';
                if ($active !== (bool) $is_active) {
                    continue;
                }
            }
            
            // Filter by low_stock if requested
            $low_stock = $request->get_param('low_stock');
            if ($low_stock) {
                $stock_qty = $product->get_stock_quantity();
                $extended = $extended_data_map[$product->get_id()] ?? null;
                $min_stock = $extended ? (int) $extended->min_stock_level : 0;
                if ($stock_qty > $min_stock) {
                    continue;
                }
            }
            
            $items[] = $product_data;
        }

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $id = (int) $request->get_param('id');
        $product = wc_get_product($id);

        if (!$product) {
            return $this->error_response(__('Product not found', 'asmaa-salon'), 404);
        }

        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_product_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_product_id = %d", $id)
        );

        $product_data = $this->format_product_data($product, $extended);

        return $this->success_response($product_data);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $name = sanitize_text_field($request->get_param('name'));
        $sku = sanitize_text_field($request->get_param('sku'));

        if (empty($name) || empty($sku)) {
            return $this->error_response(__('Name and SKU are required', 'asmaa-salon'), 400);
        }

        // Check SKU uniqueness
        $existing_product = wc_get_product_id_by_sku($sku);
        if ($existing_product) {
            return $this->error_response(__('SKU already exists', 'asmaa-salon'), 400);
        }

        // Create WooCommerce product
        $product = new \WC_Product_Simple();
        $product->set_name($name);
        $product->set_sku($sku);
        $product->set_regular_price((float) $request->get_param('selling_price') ?: 0);
        $product->set_manage_stock(true);
        $product->set_stock_quantity((int) $request->get_param('stock_quantity') ?: 0);
        $product->set_status('publish');

        // Set description
        if ($request->has_param('description')) {
            $product->set_description(sanitize_textarea_field($request->get_param('description')));
        }

        // Set short description
        if ($request->has_param('name_ar')) {
            $product->set_short_description(sanitize_text_field($request->get_param('name_ar')));
        }

        // Set category
        if ($request->has_param('category')) {
            $category = sanitize_text_field($request->get_param('category'));
            $term = get_term_by('name', $category, 'product_cat');
            if ($term) {
                $product->set_category_ids([$term->term_id]);
            }
        }

        $product_id = $product->save();

        if (!$product_id) {
            return $this->error_response(__('Failed to create product', 'asmaa-salon'), 500);
        }

        // Create extended data
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_product_extended_data';

        $extended_data = [
            'wc_product_id' => $product_id,
            'purchase_price' => (float) $request->get_param('purchase_price') ?: 0,
            'min_stock_level' => (int) $request->get_param('min_stock_level') ?: 0,
            'barcode' => sanitize_text_field($request->get_param('barcode')),
            'brand' => sanitize_text_field($request->get_param('brand')),
            'unit' => sanitize_text_field($request->get_param('unit')),
        ];

        $wpdb->insert($extended_table, $extended_data);

        // Update product meta for additional fields
        if ($request->has_param('barcode')) {
            update_post_meta($product_id, '_barcode', sanitize_text_field($request->get_param('barcode')));
        }

        $product = wc_get_product($product_id);
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_product_id = %d", $product_id)
        );

        $product_data = $this->format_product_data($product, $extended);

        return $this->success_response($product_data, __('Product created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $id = (int) $request->get_param('id');
        $product = wc_get_product($id);

        if (!$product) {
            return $this->error_response(__('Product not found', 'asmaa-salon'), 404);
        }

        // Update WooCommerce product
        if ($request->has_param('name')) {
            $product->set_name(sanitize_text_field($request->get_param('name')));
        }
        if ($request->has_param('sku')) {
            $sku = sanitize_text_field($request->get_param('sku'));
            // Check SKU uniqueness if changed
            if ($sku !== $product->get_sku()) {
                $existing = wc_get_product_id_by_sku($sku);
                if ($existing && $existing !== $id) {
                    return $this->error_response(__('SKU already exists', 'asmaa-salon'), 400);
                }
            }
            $product->set_sku($sku);
        }
        if ($request->has_param('selling_price')) {
            $product->set_regular_price((float) $request->get_param('selling_price'));
        }
        if ($request->has_param('stock_quantity')) {
            $product->set_stock_quantity((int) $request->get_param('stock_quantity'));
        }
        if ($request->has_param('description')) {
            $product->set_description(sanitize_textarea_field($request->get_param('description')));
        }
        if ($request->has_param('name_ar')) {
            $product->set_short_description(sanitize_text_field($request->get_param('name_ar')));
        }

        $product->save();

        // Update extended data
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_product_extended_data';
        $extended_data = [];

        if ($request->has_param('purchase_price')) {
            $extended_data['purchase_price'] = (float) $request->get_param('purchase_price');
        }
        if ($request->has_param('min_stock_level')) {
            $extended_data['min_stock_level'] = (int) $request->get_param('min_stock_level');
        }
        if ($request->has_param('barcode')) {
            $extended_data['barcode'] = sanitize_text_field($request->get_param('barcode'));
            update_post_meta($id, '_barcode', $extended_data['barcode']);
        }
        if ($request->has_param('brand')) {
            $extended_data['brand'] = sanitize_text_field($request->get_param('brand'));
        }
        if ($request->has_param('unit')) {
            $extended_data['unit'] = sanitize_text_field($request->get_param('unit'));
        }

        if (!empty($extended_data)) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_product_id = %d", $id)
            );

            if ($existing) {
                $wpdb->update($extended_table, $extended_data, ['wc_product_id' => $id]);
            } else {
                $extended_data['wc_product_id'] = $id;
                $wpdb->insert($extended_table, $extended_data);
            }
        }

        // Check for low stock after update
        if ($request->has_param('stock_quantity') || $request->has_param('min_stock_level')) {
            $current_stock = $product->get_stock_quantity();
            $extended = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_product_id = %d", $id)
            );
            $min_stock = $extended ? (int) $extended->min_stock_level : 0;
            
            if ($current_stock <= $min_stock) {
                \AsmaaSalon\Services\NotificationDispatcher::low_stock_alert($id, [
                    'name' => $product->get_name(),
                    'current_stock' => $current_stock,
                    'min_stock_level' => $min_stock,
                    'sku' => $product->get_sku(),
                ]);
            }
        }

        $product = wc_get_product($id);
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_product_id = %d", $id)
        );

        $product_data = $this->format_product_data($product, $extended);

        return $this->success_response($product_data, __('Product updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }

        $id = (int) $request->get_param('id');
        $product = wc_get_product($id);

        if (!$product) {
            return $this->error_response(__('Product not found', 'asmaa-salon'), 404);
        }

        // Delete WooCommerce product (trash, not permanent delete)
        $product->delete();

        return $this->success_response(null, __('Product deleted successfully', 'asmaa-salon'));
    }

    /**
     * Format product data from WooCommerce product and extended data
     */
    private function format_product_data($product, $extended = null): array
    {
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'name_ar' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'barcode' => $extended->barcode ?? get_post_meta($product->get_id(), '_barcode', true),
            'description' => $product->get_description(),
            'category' => $this->get_product_category($product),
            'brand' => $extended->brand ?? null,
            'purchase_price' => $extended ? (float) $extended->purchase_price : 0.0,
            'selling_price' => (float) $product->get_regular_price(),
            'stock_quantity' => $product->get_stock_quantity(),
            'min_stock_level' => $extended ? (int) $extended->min_stock_level : 0,
            'unit' => $extended->unit ?? null,
            'image' => $product->get_image_id() ? wp_get_attachment_image_url($product->get_image_id(), 'full') : null,
            'is_active' => $product->get_status() === 'publish' && $product->get_stock_status() === 'instock',
            'created_at' => $product->get_date_created()->date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get product category name
     */
    private function get_product_category($product): ?string
    {
        $categories = $product->get_category_ids();
        if (empty($categories)) {
            return null;
        }
        
        $category = get_term($categories[0], 'product_cat');
        return $category ? $category->name : null;
    }
}

<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;

use WP_Error;
if (!defined('ABSPATH')) {
    exit;
}

class Services_Controller extends Base_Controller
{
    protected string $rest_base = 'services';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_services_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => $this->permission_callback('asmaa_services_create')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_services_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => $this->permission_callback('asmaa_services_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => $this->permission_callback('asmaa_services_delete')],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_services';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = ['deleted_at IS NULL'];
        $search = $request->get_param('search');
        if ($search) {
            $where[] = $wpdb->prepare('(name LIKE %s OR name_ar LIKE %s)', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }

        $category = $request->get_param('category');
        if ($category) {
            $where[] = $wpdb->prepare('category = %s', $category);
        }

        $is_active = $request->get_param('is_active');
        if ($is_active !== null) {
            $where[] = $wpdb->prepare('is_active = %d', $is_active ? 1 : 0);
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
        $table = $wpdb->prefix . 'asmaa_services';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));

        if (!$item) {
            return $this->error_response(__('Service not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_services';

        $data = [
            'name' => sanitize_text_field($request->get_param('name')),
            'name_ar' => sanitize_text_field($request->get_param('name_ar')),
            'description' => sanitize_textarea_field($request->get_param('description')),
            'price' => $request->get_param('price') ? (float) $request->get_param('price') : 0,
            'duration' => $request->get_param('duration') ? (int) $request->get_param('duration') : 0,
            'category' => sanitize_text_field($request->get_param('category')),
            'is_active' => $request->get_param('is_active') ? 1 : 0,
            'image' => esc_url_raw($request->get_param('image')),
        ];

        if (empty($data['name'])) {
            return $this->error_response(__('Name is required', 'asmaa-salon'), 400);
        }

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return $this->error_response(__('Failed to create service', 'asmaa-salon'), 500);
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id));
        return $this->success_response($item, __('Service created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_services';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Service not found', 'asmaa-salon'), 404);
        }

        $data = [];
        $fields = ['name', 'name_ar', 'description', 'price', 'duration', 'category', 'is_active', 'image'];

        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if ($field === 'description') {
                    $data[$field] = sanitize_textarea_field($request->get_param($field));
                } elseif ($field === 'price') {
                    $data[$field] = (float) $request->get_param($field);
                } elseif ($field === 'duration') {
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

        if (empty($data)) {
            return $this->error_response(__('No data to update', 'asmaa-salon'), 400);
        }

        $wpdb->update($table, $data, ['id' => $id]);
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        return $this->success_response($item, __('Service updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_services';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Service not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
        return $this->success_response(null, __('Service deleted successfully', 'asmaa-salon'));
    }
}

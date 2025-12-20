<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;

use WP_Error;
if (!defined('ABSPATH')) {
    exit;
}

class Staff_Controller extends Base_Controller
{
    protected string $rest_base = 'staff';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => $this->permission_callback('asmaa_staff_view')],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => $this->permission_callback('asmaa_staff_create')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => $this->permission_callback('asmaa_staff_view')],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => $this->permission_callback('asmaa_staff_update')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => $this->permission_callback('asmaa_staff_delete')],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = ['deleted_at IS NULL'];
        $search = $request->get_param('search');
        if ($search) {
            $where[] = $wpdb->prepare('(name LIKE %s OR email LIKE %s OR phone LIKE %s)', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
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

        // Parse service_ids JSON to array
        foreach ($items as $item) {
            if (!empty($item->service_ids)) {
                $item->service_ids = json_decode($item->service_ids, true) ?: [];
            } else {
                $item->service_ids = [];
            }
        }

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));

        if (!$item) {
            return $this->error_response(__('Staff not found', 'asmaa-salon'), 404);
        }

        // Parse service_ids JSON to array
        if (!empty($item->service_ids)) {
            $item->service_ids = json_decode($item->service_ids, true) ?: [];
        } else {
            $item->service_ids = [];
        }

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff';

        $service_ids = $request->get_param('service_ids');
        $service_ids_json = null;
        if ($service_ids && is_array($service_ids)) {
            $service_ids_json = json_encode(array_map('intval', $service_ids));
        } elseif ($service_ids) {
            $service_ids_json = json_encode([(int) $service_ids]);
        }

        $data = [
            'user_id' => $request->get_param('user_id') ? (int) $request->get_param('user_id') : null,
            'name' => sanitize_text_field($request->get_param('name')),
            'phone' => sanitize_text_field($request->get_param('phone')),
            'email' => sanitize_email($request->get_param('email')),
            'position' => sanitize_text_field($request->get_param('position')),
            'hire_date' => $request->get_param('hire_date') ?: null,
            'salary' => $request->get_param('salary') ? (float) $request->get_param('salary') : null,
            'commission_rate' => $request->get_param('commission_rate') ? (float) $request->get_param('commission_rate') : null,
            'photo' => esc_url_raw($request->get_param('photo')),
            'is_active' => $request->get_param('is_active') ? 1 : 0,
            'service_ids' => $service_ids_json,
            'notes' => sanitize_textarea_field($request->get_param('notes')),
        ];

        if (empty($data['name'])) {
            return $this->error_response(__('Name is required', 'asmaa-salon'), 400);
        }

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return $this->error_response(__('Failed to create staff', 'asmaa-salon'), 500);
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id));
        
        // Parse service_ids JSON to array
        if (!empty($item->service_ids)) {
            $item->service_ids = json_decode($item->service_ids, true) ?: [];
        } else {
            $item->service_ids = [];
        }
        
        return $this->success_response($item, __('Staff created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Staff not found', 'asmaa-salon'), 404);
        }

        $data = [];
        $fields = ['user_id', 'name', 'phone', 'email', 'position', 'hire_date', 'salary', 'commission_rate', 'photo', 'is_active', 'service_ids', 'notes'];

        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if ($field === 'user_id') {
                    $data[$field] = $request->get_param($field) ? (int) $request->get_param($field) : null;
                } elseif ($field === 'salary' || $field === 'commission_rate') {
                    $data[$field] = $request->get_param($field) ? (float) $request->get_param($field) : null;
                } elseif ($field === 'is_active') {
                    $data[$field] = $request->get_param($field) ? 1 : 0;
                } elseif ($field === 'photo') {
                    $data[$field] = esc_url_raw($request->get_param($field));
                } elseif ($field === 'service_ids') {
                    $service_ids = $request->get_param($field);
                    if ($service_ids && is_array($service_ids)) {
                        $data[$field] = json_encode(array_map('intval', $service_ids));
                    } elseif ($service_ids) {
                        $data[$field] = json_encode([(int) $service_ids]);
                    } else {
                        $data[$field] = null;
                    }
                } elseif ($field === 'notes') {
                    $data[$field] = sanitize_textarea_field($request->get_param($field));
                } elseif ($field === 'email') {
                    $data[$field] = sanitize_email($request->get_param($field));
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
        
        // Parse service_ids JSON to array
        if (!empty($item->service_ids)) {
            $item->service_ids = json_decode($item->service_ids, true) ?: [];
        } else {
            $item->service_ids = [];
        }

        return $this->success_response($item, __('Staff updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff';
        $id = (int) $request->get_param('id');

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id));
        if (!$existing) {
            return $this->error_response(__('Staff not found', 'asmaa-salon'), 404);
        }

        $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
        return $this->success_response(null, __('Staff deleted successfully', 'asmaa-salon'));
    }
}

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
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        // Get WordPress users with staff roles
        $args = [
            'role__in' => [
                'asmaa_staff', 'asmaa_manager', 'asmaa_admin', 'asmaa_super_admin',
                'asmaa_accountant', 'asmaa_receptionist', 'asmaa_cashier',
                'administrator', 'editor', 'author',
                'huda_admin', 'huda_production', 'huda_tailor', 'huda_accountant',
                'workshop_supervisor', 'customer_service_employee'
            ],
            'number' => $params['per_page'],
            'offset' => $offset,
            'orderby' => 'registered',
            'order' => 'DESC',
        ];

        $search = $request->get_param('search');
        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $users = get_users($args);
        
        // Get total count
        $count_args = $args;
        $count_args['number'] = -1;
        $count_args['offset'] = 0;
        $total = count(get_users($count_args));

        // Get extended data for all staff
        $extended_table = $wpdb->prefix . 'asmaa_staff_extended_data';
        $user_ids = array_map(fn($user) => $user->ID, $users);
        
        $extended_data_map = [];
        if (!empty($user_ids)) {
            $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
            $extended_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$extended_table} WHERE wp_user_id IN ({$placeholders})",
                    ...$user_ids
                )
            );
            
            foreach ($extended_rows as $row) {
                $extended_data_map[$row->wp_user_id] = $row;
            }
        }

        // Filter by is_active if requested
        $is_active = $request->get_param('is_active');
        if ($is_active !== null) {
            $users = array_filter($users, function($user) use ($extended_data_map, $is_active) {
                $extended = $extended_data_map[$user->ID] ?? null;
                $active = $extended ? (bool) $extended->is_active : true;
                return $active === (bool) $is_active;
            });
        }

        // Format response
        $items = [];
        foreach ($users as $user) {
            $staff_data = $this->format_staff_data($user, $extended_data_map[$user->ID] ?? null);
            $items[] = $staff_data;
        }

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');
        $user = get_user_by('ID', $id);

        if (!$user) {
            return $this->error_response(__('Staff not found', 'asmaa-salon'), 404);
        }

        // Check if user has staff role
        $staff_roles = [
            'asmaa_staff', 'asmaa_manager', 'asmaa_admin', 'asmaa_super_admin',
            'asmaa_accountant', 'asmaa_receptionist', 'asmaa_cashier',
            'administrator', 'editor', 'author',
            'huda_admin', 'huda_production', 'huda_tailor', 'huda_accountant',
            'workshop_supervisor', 'customer_service_employee'
        ];
        $user_roles = $user->roles ?? [];
        $has_staff_role = !empty(array_intersect($staff_roles, $user_roles));

        if (!$has_staff_role) {
            return $this->error_response(__('User is not a staff member', 'asmaa-salon'), 403);
        }

        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_staff_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wp_user_id = %d", $id)
        );

        $staff_data = $this->format_staff_data($user, $extended);

        return $this->success_response($staff_data);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $name = sanitize_text_field($request->get_param('name'));
        $email = sanitize_email($request->get_param('email'));
        $phone = sanitize_text_field($request->get_param('phone'));

        if (empty($name) || empty($email)) {
            return $this->error_response(__('Name and email are required', 'asmaa-salon'), 400);
        }

        // Check if email already exists
        if (email_exists($email)) {
            return $this->error_response(__('Email already exists', 'asmaa-salon'), 400);
        }

        // Create WordPress user
        $username = sanitize_user($email);
        $user_id = wp_create_user($username, wp_generate_password(), $email);

        if (is_wp_error($user_id)) {
            return $this->error_response($user_id->get_error_message(), 400);
        }

        // Update user data
        $user_data = [
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name,
        ];
        wp_update_user($user_data);

        // Assign staff role
        $user = new \WP_User($user_id);
        $user->set_role('asmaa_staff');

        // Create extended data
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_staff_extended_data';
        
        $service_ids = $request->get_param('service_ids');
        $service_ids_json = null;
        if ($service_ids && is_array($service_ids)) {
            $service_ids_json = json_encode(array_map('intval', $service_ids));
        } elseif ($service_ids) {
            $service_ids_json = json_encode([(int) $service_ids]);
        }

        $extended_data = [
            'wp_user_id' => $user_id,
            'position' => sanitize_text_field($request->get_param('position')),
            'chair_number' => $request->get_param('chair_number') ? (int) $request->get_param('chair_number') : null,
            'hire_date' => $request->get_param('hire_date') ?: null,
            'salary' => $request->get_param('salary') ? (float) $request->get_param('salary') : null,
            'commission_rate' => $request->get_param('commission_rate') ? (float) $request->get_param('commission_rate') : null,
            'photo' => esc_url_raw($request->get_param('photo')),
            'is_active' => $request->get_param('is_active') ? 1 : 0,
            'service_ids' => $service_ids_json,
            'notes' => sanitize_textarea_field($request->get_param('notes')),
        ];

        $wpdb->insert($extended_table, $extended_data);

        $user = get_user_by('ID', $user_id);
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wp_user_id = %d", $user_id)
        );

        $staff_data = $this->format_staff_data($user, $extended);

        return $this->success_response($staff_data, __('Staff created successfully', 'asmaa-salon'), 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');
        $user = get_user_by('ID', $id);

        if (!$user) {
            return $this->error_response(__('Staff not found', 'asmaa-salon'), 404);
        }

        // Update WordPress user
        $user_data = [];
        if ($request->has_param('name')) {
            $user_data['display_name'] = sanitize_text_field($request->get_param('name'));
            $user_data['first_name'] = sanitize_text_field($request->get_param('name'));
        }
        if ($request->has_param('email')) {
            $email = sanitize_email($request->get_param('email'));
            if ($email && $email !== $user->user_email) {
                if (email_exists($email)) {
                    return $this->error_response(__('Email already exists', 'asmaa-salon'), 400);
                }
                $user_data['user_email'] = $email;
            }
        }

        if (!empty($user_data)) {
            $user_data['ID'] = $id;
            wp_update_user($user_data);
        }

        // Update extended data
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_staff_extended_data';
        $extended_data = [];

        $fields = ['position', 'chair_number', 'hire_date', 'salary', 'commission_rate', 'photo', 'is_active', 'notes'];
        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if ($field === 'salary' || $field === 'commission_rate') {
                    $extended_data[$field] = $request->get_param($field) ? (float) $request->get_param($field) : null;
                } elseif ($field === 'chair_number') {
                    $extended_data[$field] = $request->get_param($field) ? (int) $request->get_param($field) : null;
                } elseif ($field === 'is_active') {
                    $extended_data[$field] = $request->get_param($field) ? 1 : 0;
                } elseif ($field === 'photo') {
                    $extended_data[$field] = esc_url_raw($request->get_param($field));
                } elseif ($field === 'notes') {
                    $extended_data[$field] = sanitize_textarea_field($request->get_param($field));
                } else {
                    $extended_data[$field] = sanitize_text_field($request->get_param($field));
                }
            }
        }

        if ($request->has_param('service_ids')) {
            $service_ids = $request->get_param('service_ids');
            if ($service_ids && is_array($service_ids)) {
                $extended_data['service_ids'] = json_encode(array_map('intval', $service_ids));
            } elseif ($service_ids) {
                $extended_data['service_ids'] = json_encode([(int) $service_ids]);
            } else {
                $extended_data['service_ids'] = null;
            }
        }

        if (!empty($extended_data)) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wp_user_id = %d", $id)
            );

            if ($existing) {
                $wpdb->update($extended_table, $extended_data, ['wp_user_id' => $id]);
            } else {
                $extended_data['wp_user_id'] = $id;
                $wpdb->insert($extended_table, $extended_data);
            }
        }

        $user = get_user_by('ID', $id);
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wp_user_id = %d", $id)
        );

        $staff_data = $this->format_staff_data($user, $extended);

        return $this->success_response($staff_data, __('Staff updated successfully', 'asmaa-salon'));
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');
        $user = get_user_by('ID', $id);

        if (!$user) {
            return $this->error_response(__('Staff not found', 'asmaa-salon'), 404);
        }

        // Delete WordPress user (cascade will delete extended data)
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($id);

        return $this->success_response(null, __('Staff deleted successfully', 'asmaa-salon'));
    }

    /**
     * Format staff data from WordPress user and extended data
     */
    private function format_staff_data($user, $extended = null): array
    {
        $service_ids = [];
        if ($extended && !empty($extended->service_ids)) {
            $service_ids = json_decode($extended->service_ids, true) ?: [];
        }

        return [
            'id' => $user->ID,
            'user_id' => $user->ID,
            'name' => $user->display_name ?: $user->user_login,
            'full_name' => $user->display_name ?: $user->user_login, // For compatibility
            'email' => $user->user_email,
            'phone' => get_user_meta($user->ID, 'phone', true),
            'position' => $extended->position ?? null,
            'chair_number' => $extended->chair_number ? (int) $extended->chair_number : null,
            'hire_date' => $extended->hire_date ?? null,
            'salary' => $extended->salary ? (float) $extended->salary : null,
            'commission_rate' => $extended->commission_rate ? (float) $extended->commission_rate : null,
            'photo' => $extended->photo ?? null,
            'is_active' => $extended ? (bool) $extended->is_active : true,
            'rating' => $extended->rating ? (float) $extended->rating : 0.0,
            'total_ratings' => (int) ($extended->total_ratings ?? 0),
            'total_services' => (int) ($extended->total_services ?? 0),
            'total_revenue' => $extended->total_revenue ? (float) $extended->total_revenue : 0.0,
            'service_ids' => $service_ids,
            'notes' => $extended->notes ?? null,
            'created_at' => $user->user_registered,
        ];
    }
}

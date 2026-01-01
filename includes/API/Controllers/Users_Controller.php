<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Security\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

class Users_Controller extends Base_Controller
{
    protected string $rest_base = 'users';

    public function register_routes(): void
    {
        // Get all users
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_users'],
                'permission_callback' => $this->permission_callback('asmaa_access_view_users'),
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_user'],
                'permission_callback' => $this->permission_callback('asmaa_access_create_users'),
            ],
        ]);

        // Get/Update/Delete specific user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_user'],
                'permission_callback' => $this->permission_callback('asmaa_access_view_users'),
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_user'],
                'permission_callback' => $this->permission_callback('asmaa_access_update_users'),
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_user'],
                'permission_callback' => $this->permission_callback('asmaa_access_delete_users'),
            ],
        ]);

        // Assign role to user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/role', [
            'methods' => 'PUT',
            'callback' => [$this, 'assign_role'],
            'permission_callback' => $this->permission_callback('asmaa_access_assign_roles'),
        ]);

        // Get all available roles
        register_rest_route($this->namespace, '/' . $this->rest_base . '/roles', [
            'methods' => 'GET',
            'callback' => [$this, 'get_roles'],
            'permission_callback' => $this->permission_callback('asmaa_access_view_roles'),
        ]);

        // Get role capabilities
        register_rest_route($this->namespace, '/' . $this->rest_base . '/roles/(?P<role>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_role_capabilities'],
            'permission_callback' => $this->permission_callback('asmaa_access_view_roles'),
        ]);
    }

    /**
     * Get all users
     */
    public function get_users(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $page = $request->get_param('page') ?? 1;
        $per_page = $request->get_param('per_page') ?? 20;
        $search = $request->get_param('search') ?? '';
        $role = $request->get_param('role') ?? '';
        $scope = sanitize_key((string) ($request->get_param('scope') ?? ''));

        $args = [
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'orderby' => 'registered',
            'order' => 'DESC',
        ];

        if (!empty($search)) {
            $args['search'] = '*' . esc_attr($search) . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        // Scope filter (preferred) - allows role__in for "staff" and "customers"
        if ($scope === 'staff') {
            $args['role__in'] = [
                'administrator',
                'asmaa_super_admin',
                'asmaa_admin',
                'asmaa_manager',
                'asmaa_accountant',
                'asmaa_receptionist',
                'asmaa_cashier',
                'asmaa_staff',
                'editor',
                'author',
                'huda_admin',
                'huda_production',
                'huda_tailor',
                'huda_accountant',
                'workshop_supervisor',
                'customer_service_employee'
            ];
        } elseif ($scope === 'customers') {
            // WooCommerce customer role
            $args['role__in'] = ['customer'];
        } elseif (!empty($role)) {
            // Exact role filter
            $args['role'] = $role;
        }

        $user_query = new \WP_User_Query($args);
        $users = $user_query->get_results();
        $total = $user_query->get_total();

        $formatted_users = array_map(function ($user) {
            return $this->format_user($user);
        }, $users);

        return new WP_REST_Response([
            'success' => true,
            'data' => $formatted_users,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page),
            ],
        ]);
    }

    /**
     * Get single user
     */
    public function get_user(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $user_id = (int) $request->get_param('id');
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'asmaa-salon'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->format_user($user),
        ]);
    }

    /**
     * Create new user
     */
    public function create_user(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $username = sanitize_user($request->get_param('username'));
        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $role = $request->get_param('role') ?? 'asmaa_staff';
        $first_name = sanitize_text_field($request->get_param('first_name') ?? '');
        $last_name = sanitize_text_field($request->get_param('last_name') ?? '');

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            return new WP_Error('missing_fields', __('Username, email, and password are required', 'asmaa-salon'), ['status' => 400]);
        }

        if (username_exists($username)) {
            return new WP_Error('username_exists', __('Username already exists', 'asmaa-salon'), ['status' => 400]);
        }

        if (email_exists($email)) {
            return new WP_Error('email_exists', __('Email already exists', 'asmaa-salon'), ['status' => 400]);
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Update user meta
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role,
        ]);

        $user = get_user_by('id', $user_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('User created successfully', 'asmaa-salon'),
            'data' => $this->format_user($user),
        ], 201);
    }

    /**
     * Update user
     */
    public function update_user(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $user_id = (int) $request->get_param('id');
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'asmaa-salon'), ['status' => 404]);
        }

        $data = ['ID' => $user_id];

        if ($request->has_param('email')) {
            $email = sanitize_email($request->get_param('email'));
            if (email_exists($email) && $email !== $user->user_email) {
                return new WP_Error('email_exists', __('Email already exists', 'asmaa-salon'), ['status' => 400]);
            }
            $data['user_email'] = $email;
        }

        if ($request->has_param('first_name')) {
            $data['first_name'] = sanitize_text_field($request->get_param('first_name'));
        }

        if ($request->has_param('last_name')) {
            $data['last_name'] = sanitize_text_field($request->get_param('last_name'));
        }

        if ($request->has_param('password')) {
            $data['user_pass'] = $request->get_param('password');
        }

        $result = wp_update_user($data);

        if (is_wp_error($result)) {
            return $result;
        }

        $updated_user = get_user_by('id', $user_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('User updated successfully', 'asmaa-salon'),
            'data' => $this->format_user($updated_user),
        ]);
    }

    /**
     * Delete user
     */
    public function delete_user(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $user_id = (int) $request->get_param('id');
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'asmaa-salon'), ['status' => 404]);
        }

        // Prevent deleting current user
        if ($user_id === get_current_user_id()) {
            return new WP_Error('cannot_delete_self', __('You cannot delete your own account', 'asmaa-salon'), ['status' => 403]);
        }

        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $result = wp_delete_user($user_id);

        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete user', 'asmaa-salon'), ['status' => 500]);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('User deleted successfully', 'asmaa-salon'),
        ]);
    }

    /**
     * Assign role to user
     */
    public function assign_role(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $user_id = (int) $request->get_param('id');
        $role = $request->get_param('role');

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'asmaa-salon'), ['status' => 404]);
        }

        // Check if role exists
        if (!wp_roles()->is_role($role)) {
            return new WP_Error('invalid_role', __('Invalid role', 'asmaa-salon'), ['status' => 400]);
        }

        // Prevent changing own role
        if ($user_id === get_current_user_id()) {
            return new WP_Error('cannot_change_own_role', __('You cannot change your own role', 'asmaa-salon'), ['status' => 403]);
        }

        $user->set_role($role);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Role assigned successfully', 'asmaa-salon'),
            'data' => $this->format_user($user),
        ]);
    }

    /**
     * Get all available roles
     */
    public function get_roles(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wp_roles;

        $roles = [];
        $asmaa_caps = Capabilities::get_all_capabilities();
        $asmaa_caps_set = array_fill_keys($asmaa_caps, true);
        $scope = sanitize_key((string) ($request->get_param('scope') ?? 'asmaa'));
        
        $preferred_order = [
            'administrator',
            'asmaa_super_admin',
            'asmaa_admin',
            'asmaa_manager',
            'asmaa_accountant',
            'asmaa_receptionist',
            'asmaa_cashier',
            'asmaa_staff',
        ];

        $role_keys = [];
        if ($scope === 'all') {
            $role_keys = array_keys($wp_roles->roles);
            // Keep preferred roles first, then the rest alphabetically by name
            usort($role_keys, function ($a, $b) use ($wp_roles, $preferred_order) {
                $ai = array_search($a, $preferred_order, true);
                $bi = array_search($b, $preferred_order, true);
                if ($ai !== false && $bi !== false) return $ai <=> $bi;
                if ($ai !== false) return -1;
                if ($bi !== false) return 1;
                $an = $wp_roles->role_names[$a] ?? $a;
                $bn = $wp_roles->role_names[$b] ?? $b;
                return strcasecmp((string) $an, (string) $bn);
            });
        } else {
            // Only return Asmaa Salon roles + administrator
            $role_keys = $preferred_order;
        }

        foreach ($role_keys as $role_key) {
            if (!isset($wp_roles->roles[$role_key])) {
                continue;
            }

            $role = $wp_roles->roles[$role_key];
            $role_caps = is_array($role['capabilities'] ?? null) ? $role['capabilities'] : [];
            $asmaa_count = 0;

            foreach ($role_caps as $cap_key => $enabled) {
                if ($enabled && isset($asmaa_caps_set[$cap_key])) {
                    $asmaa_count++;
                }
            }

            $roles[] = [
                'key' => $role_key,
                'name' => $wp_roles->role_names[$role_key] ?? ucfirst(str_replace('_', ' ', $role_key)),
                // Backward compatible: total WP capabilities count
                'capabilities_count' => count($role_caps),
                // Asmaa-only capabilities count (what matters for this dashboard)
                'asmaa_capabilities_count' => $asmaa_count,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Get role capabilities
     */
    public function get_role_capabilities(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $role_key = $request->get_param('role');
        $role = get_role($role_key);

        if (!$role) {
            return new WP_Error('role_not_found', __('Role not found', 'asmaa-salon'), ['status' => 404]);
        }

        $all_capabilities = Capabilities::get_all_capabilities();
        
        // Group capabilities by module
        $grouped_capabilities = [];
        foreach ($all_capabilities as $cap) {
            // Extract module name (e.g., 'asmaa_services_view' => 'services')
            $parts = explode('_', $cap);
            if (count($parts) >= 3) {
                $module = $parts[1];
                $action = implode('_', array_slice($parts, 2));
                
                if (!isset($grouped_capabilities[$module])) {
                    $grouped_capabilities[$module] = [];
                }
                
                $grouped_capabilities[$module][] = [
                    'key' => $cap,
                    'action' => $action,
                    'name' => ucfirst(str_replace('_', ' ', $action)),
                    'has' => $role->has_cap($cap),
                ];
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'role' => $role_key,
                'capabilities' => $grouped_capabilities,
            ],
        ]);
    }

    /**
     * Format user data
     */
    protected function format_user(\WP_User $user): array
    {
        $roles = $user->roles;
        $role = !empty($roles) ? $roles[0] : 'none';

        global $wp_roles;
        $role_name = isset($wp_roles->role_names[$role]) 
            ? translate_user_role($wp_roles->role_names[$role]) 
            : ucfirst(str_replace('_', ' ', $role));

        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'role' => $role,
            'role_name' => $role_name,
            'registered' => $user->user_registered,
            'avatar_url' => get_avatar_url($user->ID),
        ];
    }
}


<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\Apple_Wallet_Service;
use AsmaaSalon\Helpers\QR_Code_Generator;

if (!defined('ABSPATH')) {
    exit;
}

class Apple_Wallet_Controller extends Base_Controller
{
    protected string $rest_base = 'apple-wallet';

    public function register_routes(): void
    {
        // Apple Wallet Web Service endpoints (required by Apple)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/v1/devices/(?P<device_id>[^/]+)/registrations/(?P<pass_type_id>[^/]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_device_registrations'],
                'permission_callback' => [$this, 'verify_apple_wallet_request'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/v1/devices/(?P<device_id>[^/]+)/registrations/(?P<pass_type_id>[^/]+)/(?P<serial_number>[^/]+)', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'register_device'],
                'permission_callback' => [$this, 'verify_apple_wallet_request'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'unregister_device'],
                'permission_callback' => [$this, 'verify_apple_wallet_request'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/v1/passes/(?P<pass_type_id>[^/]+)/(?P<serial_number>[^/]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_updated_pass'],
                'permission_callback' => [$this, 'verify_apple_wallet_request'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/v1/log', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'log_error'],
                'permission_callback' => [$this, 'verify_apple_wallet_request'],
            ],
        ]);

        // Custom endpoints for creating/updating passes
        register_rest_route($this->namespace, '/' . $this->rest_base . '/create/(?P<customer_id>\d+)', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_pass'],
                'permission_callback' => $this->permission_callback('asmaa_customers_view'),
            ],
        ]);
        
        // Create pass by type
        register_rest_route($this->namespace, '/' . $this->rest_base . '/create/(?P<customer_id>\d+)/(?P<pass_type>[a-z]+)', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_pass_by_type'],
                'permission_callback' => $this->permission_callback('asmaa_customers_view'),
            ],
        ]);
        
        // Get all passes for customer/staff
        register_rest_route($this->namespace, '/' . $this->rest_base . '/passes/(?P<customer_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_all_passes'],
                'permission_callback' => $this->permission_callback('asmaa_customers_view'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/pass/(?P<serial_number>[^/]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'download_pass'],
                'permission_callback' => '__return_true',
            ],
        ]);

        // QR Code scan endpoint
        register_rest_route($this->namespace, '/loyalty/scan/(?P<encoded_data>[^/]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'scan_qr_code'],
                'permission_callback' => $this->permission_callback('asmaa_loyalty_view'),
            ],
        ]);
    }

    /**
     * Verify Apple Wallet request authentication
     */
    public function verify_apple_wallet_request(WP_REST_Request $request): bool
    {
        // Get authentication token from header
        $auth_token = $request->get_header('Authorization');
        if (!$auth_token) {
            return false;
        }

        // Remove "ApplePass " prefix if present
        $auth_token = str_replace('ApplePass ', '', $auth_token);
        $auth_token = trim($auth_token);

        // Verify token exists in database
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $pass = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE authentication_token = %s", $auth_token)
        );

        return !empty($pass);
    }

    /**
     * Get list of passes registered on a device
     */
    public function get_device_registrations(WP_REST_Request $request): WP_REST_Response
    {
        $device_id = $request->get_param('device_id');
        $pass_type_id = $request->get_param('pass_type_id');

        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_apple_wallet_device_registrations';
        
        // Check if table exists, if not return empty array
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
        
        if (!$table_exists) {
            return $this->success_response(['lastUpdated' => '', 'serialNumbers' => []]);
        }

        $registrations = $wpdb->get_col($wpdb->prepare(
            "SELECT serial_number FROM {$table} WHERE device_id = %s AND pass_type_id = %s",
            $device_id,
            $pass_type_id
        ));

        // Get last updated timestamp
        $last_updated = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(last_updated) FROM {$table} WHERE device_id = %s AND pass_type_id = %s",
            $device_id,
            $pass_type_id
        ));

        return $this->success_response([
            'lastUpdated' => $last_updated ?: '',
            'serialNumbers' => $registrations,
        ]);
    }

    /**
     * Register device for pass updates
     */
    public function register_device(WP_REST_Request $request): WP_REST_Response
    {
        $device_id = $request->get_param('device_id');
        $pass_type_id = $request->get_param('pass_type_id');
        $serial_number = $request->get_param('serial_number');
        $push_token = $request->get_param('pushToken');

        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_apple_wallet_device_registrations';
        
        // Create table if doesn't exist
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
        if (!$table_exists) {
            $charset = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id VARCHAR(255) NOT NULL,
                pass_type_id VARCHAR(255) NOT NULL,
                serial_number VARCHAR(255) NOT NULL,
                push_token TEXT NULL,
                last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY idx_device_pass_serial (device_id, pass_type_id, serial_number),
                KEY idx_serial_number (serial_number)
            ) {$charset};";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        // Insert or update registration
        $wpdb->replace($table, [
            'device_id' => $device_id,
            'pass_type_id' => $pass_type_id,
            'serial_number' => $serial_number,
            'push_token' => $push_token,
        ]);

        return $this->success_response(['status' => 'registered'], '', 201);
    }

    /**
     * Unregister device for pass updates
     */
    public function unregister_device(WP_REST_Request $request): WP_REST_Response
    {
        $device_id = $request->get_param('device_id');
        $pass_type_id = $request->get_param('pass_type_id');
        $serial_number = $request->get_param('serial_number');

        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_apple_wallet_device_registrations';
        
        $wpdb->delete($table, [
            'device_id' => $device_id,
            'pass_type_id' => $pass_type_id,
            'serial_number' => $serial_number,
        ]);

        return $this->success_response(['status' => 'unregistered']);
    }

    /**
     * Get updated pass data
     */
    public function get_updated_pass(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $serial_number = $request->get_param('serial_number');

        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $pass = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE serial_number = %s", $serial_number)
        );

        if (!$pass) {
            return new WP_Error('pass_not_found', __('Pass not found', 'asmaa-salon'), ['status' => 404]);
        }

        // Check if pass was updated since last check
        $last_modified = $request->get_header('If-Modified-Since');
        if ($last_modified) {
            $pass_updated = strtotime($pass->last_updated);
            $client_updated = strtotime($last_modified);
            
            if ($pass_updated <= $client_updated) {
                // Pass hasn't been updated
                return new WP_REST_Response(null, 304);
            }
        }

        $pass_data = json_decode($pass->pass_data, true);
        
        $response = new WP_REST_Response($pass_data, 200);
        $response->header('Last-Modified', gmdate('D, d M Y H:i:s', strtotime($pass->last_updated)) . ' GMT');
        
        return $response;
    }

    /**
     * Log error from Apple Wallet
     */
    public function log_error(WP_REST_Request $request): WP_REST_Response
    {
        $logs = $request->get_json_params();
        
        if (is_array($logs)) {
            foreach ($logs as $log) {
                error_log('Apple Wallet Error: ' . json_encode($log));
            }
        }

        return $this->success_response(['status' => 'logged']);
    }

    /**
     * Create pass for customer (legacy - creates loyalty pass)
     */
    public function create_pass(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $customer_id = (int) $request->get_param('customer_id');

        try {
            $result = Apple_Wallet_Service::create_pass($customer_id);
            return $this->success_response($result, __('Pass created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }
    
    /**
     * Create pass by type
     */
    public function create_pass_by_type(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $customer_id = (int) $request->get_param('customer_id');
        $pass_type = sanitize_text_field($request->get_param('pass_type'));
        
        // Validate pass type
        $valid_types = [
            Apple_Wallet_Service::PASS_TYPE_LOYALTY,
            Apple_Wallet_Service::PASS_TYPE_MEMBERSHIP,
            Apple_Wallet_Service::PASS_TYPE_PROGRAMS,
            Apple_Wallet_Service::PASS_TYPE_COMMISSIONS,
        ];
        
        if (!in_array($pass_type, $valid_types)) {
            return $this->error_response(__('Invalid pass type', 'asmaa-salon'), 400);
        }

        try {
            $result = Apple_Wallet_Service::create_pass_by_type($customer_id, $pass_type);
            return $this->success_response($result, __('Pass created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }
    
    /**
     * Get all passes for customer/staff
     */
    public function get_all_passes(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $customer_id = (int) $request->get_param('customer_id');

        try {
            $passes = Apple_Wallet_Service::get_all_passes($customer_id);
            return $this->success_response($passes);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Download pass file (.pkpass)
     */
    public function download_pass(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $serial_number = $request->get_param('serial_number');

        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $pass = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE serial_number = %s", $serial_number)
        );

        if (!$pass) {
            return $this->error_response(__('Pass not found', 'asmaa-salon'), 404);
        }

        // In a real implementation, you would:
        // 1. Sign the pass with Apple certificates
        // 2. Create .pkpass file (ZIP archive)
        // 3. Return the file
        
        // For now, return pass data as JSON
        $pass_data = json_decode($pass->pass_data, true);
        
        return $this->success_response([
            'pass_data' => $pass_data,
            'download_url' => rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number . '/download'),
        ]);
    }

    /**
     * Scan QR code and get customer info
     */
    public function scan_qr_code(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $encoded_data = $request->get_param('encoded_data');
        
        $data = QR_Code_Generator::validate_and_decode($encoded_data);
        
        if (!$data) {
            return $this->error_response(__('Invalid QR code', 'asmaa-salon'), 400);
        }
        
        $customer_id = (int) $data['customer_id'];
        
        // Get customer data
        $user = get_user_by('ID', $customer_id);
        if (!$user) {
            return $this->error_response(__('Customer not found', 'asmaa-salon'), 404);
        }
        
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $customer_id)
        );
        
        return $this->success_response([
            'customer_id' => $customer_id,
            'name' => $user->display_name ?: $user->user_login,
            'loyalty_points' => (int) ($extended->loyalty_points ?? 0),
            'total_visits' => (int) ($extended->total_visits ?? 0),
            'valid' => true,
        ]);
    }
}


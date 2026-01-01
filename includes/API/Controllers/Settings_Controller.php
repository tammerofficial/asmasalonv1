<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class Settings_Controller extends Base_Controller
{
    protected string $rest_base = 'settings';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/woocommerce', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_woocommerce_settings'],
                'permission_callback' => $this->permission_callback('asmaa_settings_view'),
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_woocommerce_settings'],
                'permission_callback' => $this->permission_callback('asmaa_settings_update'),
            ],
        ]);
        
        register_rest_route($this->namespace, '/' . $this->rest_base . '/apple-wallet', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_apple_wallet_settings'],
                'permission_callback' => $this->permission_callback('asmaa_settings_view'),
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_apple_wallet_settings'],
                'permission_callback' => $this->permission_callback('asmaa_settings_update'),
            ],
        ]);
    }

    public function get_woocommerce_settings(WP_REST_Request $request): WP_REST_Response
    {
        $settings = \AsmaaSalon\Services\WooCommerce_Integration_Service::get_settings();
        $is_active = \AsmaaSalon\Services\WooCommerce_Integration_Service::is_woocommerce_active();

        return $this->success_response([
            'woocommerce_active' => $is_active,
            'settings' => $settings,
        ]);
    }

    public function update_woocommerce_settings(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $settings = $request->get_json_params();

        // Validate settings
        $allowed_keys = [
            'woocommerce_enabled',
            'sync_products',
            'sync_orders',
            'sync_customers',
            'sync_direction',
            'auto_sync',
            'sync_on_create',
            'sync_on_update',
        ];

        $validated = [];
        foreach ($allowed_keys as $key) {
            if (isset($settings[$key])) {
                if ($key === 'sync_direction') {
                    $validated[$key] = in_array($settings[$key], ['bidirectional', 'asmaa_to_wc', 'wc_to_asmaa'])
                        ? $settings[$key]
                        : 'bidirectional';
                } else {
                    $validated[$key] = (bool) $settings[$key];
                }
            }
        }

        if (empty($validated)) {
            return $this->error_response(__('No valid settings provided', 'asmaa-salon'), 400);
        }

        $result = \AsmaaSalon\Services\WooCommerce_Integration_Service::update_settings($validated);

        if ($result) {
            $updated = \AsmaaSalon\Services\WooCommerce_Integration_Service::get_settings();
            return $this->success_response($updated, __('Settings updated successfully', 'asmaa-salon'));
        }

        return $this->error_response(__('Failed to update settings', 'asmaa-salon'), 500);
    }
    
    /**
     * Get Apple Wallet settings
     */
    public function get_apple_wallet_settings(WP_REST_Request $request): WP_REST_Response
    {
        $upload_dir = wp_upload_dir();
        $certs_dir = $upload_dir['basedir'] . '/asmaa-salon/certs';
        
        $settings = [
            'team_id' => get_option('asmaa_salon_apple_team_id', ''),
            'pass_type_id' => get_option('asmaa_salon_apple_pass_type_id', 'pass.com.asmaasalon.loyalty'),
            'certificate_path' => get_option('asmaa_salon_apple_certificate_path', ''),
            'certificate_password' => '', // Never return password
            'wwdr_certificate_path' => get_option('asmaa_salon_apple_wwdr_certificate_path', ''),
            'sandbox_mode' => get_option('asmaa_salon_apple_wallet_sandbox', false),
            'certs_directory' => $certs_dir,
            'certs_url' => $upload_dir['baseurl'] . '/asmaa-salon/certs',
        ];
        
        // Check if certificates exist
        $settings['certificate_exists'] = false;
        $settings['wwdr_certificate_exists'] = false;
        
        if ($settings['certificate_path']) {
            $cert_file = $certs_dir . '/' . basename($settings['certificate_path']);
            $settings['certificate_exists'] = file_exists($cert_file) || file_exists($settings['certificate_path']);
        }
        
        if ($settings['wwdr_certificate_path']) {
            $wwdr_file = $certs_dir . '/' . basename($settings['wwdr_certificate_path']);
            $settings['wwdr_certificate_exists'] = file_exists($wwdr_file) || file_exists($settings['wwdr_certificate_path']);
        }
        
        return $this->success_response($settings);
    }
    
    /**
     * Update Apple Wallet settings
     */
    public function update_apple_wallet_settings(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $settings = $request->get_json_params();
        
        // Validate and sanitize settings
        $allowed_keys = [
            'team_id',
            'pass_type_id',
            'certificate_path',
            'certificate_password',
            'wwdr_certificate_path',
            'sandbox_mode',
        ];
        
        $validated = [];
        foreach ($allowed_keys as $key) {
            if (isset($settings[$key])) {
                switch ($key) {
                    case 'team_id':
                    case 'pass_type_id':
                    case 'certificate_path':
                    case 'wwdr_certificate_path':
                        $validated[$key] = sanitize_text_field($settings[$key]);
                        break;
                    case 'certificate_password':
                        $validated[$key] = sanitize_text_field($settings[$key]);
                        break;
                    case 'sandbox_mode':
                        $validated[$key] = (bool) $settings[$key];
                        break;
                }
            }
        }
        
        if (empty($validated)) {
            return $this->error_response(__('No valid settings provided', 'asmaa-salon'), 400);
        }
        
        // Update options
        foreach ($validated as $key => $value) {
            $option_name = 'asmaa_salon_apple_' . str_replace('_', '_', $key);
            if ($key === 'team_id') {
                update_option('asmaa_salon_apple_team_id', $value);
            } elseif ($key === 'pass_type_id') {
                update_option('asmaa_salon_apple_pass_type_id', $value);
            } elseif ($key === 'certificate_path') {
                update_option('asmaa_salon_apple_certificate_path', $value);
            } elseif ($key === 'certificate_password') {
                update_option('asmaa_salon_apple_certificate_password', $value);
            } elseif ($key === 'wwdr_certificate_path') {
                update_option('asmaa_salon_apple_wwdr_certificate_path', $value);
            } elseif ($key === 'sandbox_mode') {
                update_option('asmaa_salon_apple_wallet_sandbox', $value);
            }
        }
        
        // Return updated settings
        $controller = new self();
        return $controller->get_apple_wallet_settings($request);
    }
}


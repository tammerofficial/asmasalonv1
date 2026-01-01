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
        ]);
    }

    public function get_woocommerce_settings(WP_REST_Request $request): WP_REST_Response|WP_Error
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
    public function get_apple_wallet_settings(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $upload_dir = wp_upload_dir();
        $certs_dir = $upload_dir['basedir'] . '/asmaa-salon/certs';
        
        $settings = [
            'team_id' => \AsmaaSalon\Config\Apple_Wallet_Config::TEAM_ID,
            'pass_type_id' => \AsmaaSalon\Config\Apple_Wallet_Config::PASS_TYPE_ID,
            'certificate_path' => \AsmaaSalon\Config\Apple_Wallet_Config::CERTIFICATE_PATH,
            'certificate_password' => '***', // Hidden for security
            'wwdr_certificate_path' => \AsmaaSalon\Config\Apple_Wallet_Config::WWDR_CERTIFICATE_PATH,
            'sandbox_mode' => \AsmaaSalon\Config\Apple_Wallet_Config::SANDBOX_MODE,
            'certificates_exist' => \AsmaaSalon\Config\Apple_Wallet_Config::certificates_exist(),
            'certs_directory' => $certs_dir,
            'certs_url' => $upload_dir['baseurl'] . '/asmaa-salon/certs',
        ];
        
        // Check if certificates exist (already set in config)
        
        return $this->success_response($settings);
    }
    
    /**
     * Note: Apple Wallet settings are now managed via Apple_Wallet_Config.php
     * To change settings, edit includes/Config/Apple_Wallet_Config.php directly
     */
}


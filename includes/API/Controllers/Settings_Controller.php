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
}


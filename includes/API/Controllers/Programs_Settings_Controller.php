<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AsmaaSalon\Services\Apple_Wallet_Service;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Programs Settings (Loyalty + Commissions)
 *
 * Persisted in wp_options as:
 * - asmaa_salon_programs_settings
 */
class Programs_Settings_Controller extends Base_Controller
{
    protected string $rest_base = 'programs/settings';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => $this->permission_callback('asmaa_programs_settings_view'),
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'update_settings'],
                'permission_callback' => $this->permission_callback('asmaa_programs_settings_update'),
            ],
        ]);
        
        // Apple Wallet pass creation
        register_rest_route($this->namespace, '/' . $this->rest_base . '/apple-wallet/(?P<customer_id>\d+)', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_apple_wallet_pass'],
                'permission_callback' => $this->permission_callback('asmaa_programs_settings_view'),
            ],
        ]);
    }

    public function get_settings(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $settings = get_option('asmaa_salon_programs_settings', null);
        $settings = is_array($settings) ? $settings : $this->defaults();

        // Merge defaults (forward-compatible)
        $settings = array_replace_recursive($this->defaults(), $settings);

        return $this->success_response($settings);
    }

    public function update_settings(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }

        $merged = array_replace_recursive($this->defaults(), $payload);
        update_option('asmaa_salon_programs_settings', $merged, false);

        return $this->success_response($merged, __('Settings updated successfully', 'asmaa-salon'));
    }

    private function defaults(): array
    {
        return [
            'loyalty' => [
                'enabled' => true,
                // Points per line item (default). Can be overridden per service/product.
                'default_service_points' => 1,
                'default_product_points' => 1,
                // Optional: KWD value per point (for reporting only)
                'point_value_kwd' => 0.000,
                // Overrides: { "service:12": 2, "product:7": 3 }
                'item_overrides' => [],
            ],
            'commissions' => [
                'enabled' => true,
                // Default commission rate (percent)
                'default_service_rate' => 10.00,
                'default_product_rate' => 5.00,
                // Staff overrides: { "3": { "service_rate": 12.5, "product_rate": 6 } }
                'staff_overrides' => [],
            ],
        ];
    }
    
    /**
     * Create Apple Wallet pass for programs
     */
    public function create_apple_wallet_pass(WP_REST_Request $request): WP_REST_Response|WP_Error|WP_Error
    {
        $customer_id = (int) $request->get_param('customer_id');
        
        try {
            $result = Apple_Wallet_Service::create_programs_pass($customer_id);
            return $this->success_response($result, __('Programs pass created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }
}



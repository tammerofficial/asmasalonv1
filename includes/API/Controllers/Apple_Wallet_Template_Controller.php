<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use AsmaaSalon\Config\Apple_Wallet_Config;

class Apple_Wallet_Template_Controller extends Base_Controller
{
    protected string $rest_base = 'apple-wallet/templates';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<type>[a-z_]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_template'],
                'permission_callback' => $this->permission_callback('asmaa_settings_view'),
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'update_template'],
                'permission_callback' => $this->permission_callback('asmaa_settings_update'),
            ],
        ]);
    }

    public function get_template(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $type = $request->get_param('type');
        $option_name = 'asmaa_salon_apple_wallet_' . $type . '_template';
        
        $template = get_option($option_name);
        
        if (!$template) {
            $template = Apple_Wallet_Config::get_default_loyalty_template();
        }

        return $this->success_response($template);
    }

    public function update_template(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $type = $request->get_param('type');
        $template = $request->get_json_params();
        $option_name = 'asmaa_salon_apple_wallet_' . $type . '_template';
        
        update_option($option_name, $template);

        return $this->success_response(['message' => 'Template updated successfully']);
    }
}


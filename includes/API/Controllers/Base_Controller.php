<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Base_Controller
{
    protected string $namespace = 'asmaa-salon/v1';
    protected string $rest_base;

    /**
     * Standardize REST response
     */
    protected function success_response($data = null, string $message = '', int $code = 200): WP_REST_Response
    {
        $response = [
            'success' => true,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message) {
            $response['message'] = $message;
        }

        return new WP_REST_Response($response, $code);
    }

    /**
     * Standardize error response
     */
    protected function error_response(string $message, int $code = 400, $data = null): WP_Error
    {
        return new WP_Error(
            'asmaa_salon_error',
            $message,
            [
                'status' => $code,
                'data'   => $data,
            ]
        );
    }

    /**
     * Get pagination parameters
     */
    protected function get_pagination_params(WP_REST_Request $request): array
    {
        return [
            'page'     => max(1, (int) $request->get_param('page') ?: 1),
            'per_page' => min(100, max(1, (int) $request->get_param('per_page') ?: 20)),
        ];
    }

    /**
     * Build pagination meta
     */
    protected function build_pagination_meta(int $total, int $page, int $per_page): array
    {
        $total_pages = (int) ceil($total / $per_page);

        return [
            'total'       => $total,
            'per_page'    => $per_page,
            'current_page' => $page,
            'total_pages' => $total_pages,
        ];
    }

    /**
     * Check permission
     */
    protected function check_permission(string $capability): bool
    {
        // Allow if user is logged in and has manage_options (admin)
        // For development: allow all logged in users
        return is_user_logged_in() && current_user_can('manage_options');
    }

    /**
     * Permission callback wrapper
     */
    protected function permission_callback(string $capability): callable
    {
        return function () use ($capability) {
            // For development: just check if user is logged in
            // (standalone dashboard is intended for admins/staff)
            return is_user_logged_in();
        };
    }
}

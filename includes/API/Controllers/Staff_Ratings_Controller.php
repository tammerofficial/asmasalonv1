<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class Staff_Ratings_Controller extends Base_Controller
{
    protected string $rest_base = 'ratings';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_items'],
                'permission_callback' => $this->permission_callback('asmaa_ratings_view'),
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'create_item'],
                'permission_callback' => $this->permission_callback('asmaa_ratings_create'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_item'],
                'permission_callback' => $this->permission_callback('asmaa_ratings_view'),
            ],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff_ratings';
        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $where = [];

        $staff_id = $request->get_param('staff_id');
        if ($staff_id) {
            $where[] = $wpdb->prepare('wp_user_id = %d', (int) $staff_id);
        }

        $customer_id = $request->get_param('customer_id');
        if ($customer_id) {
            $where[] = $wpdb->prepare('wc_customer_id = %d', (int) $customer_id);
        }

        $booking_id = $request->get_param('booking_id');
        if ($booking_id) {
            $where[] = $wpdb->prepare('booking_id = %d', (int) $booking_id);
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $params['per_page'],
            $offset
        ));

        return $this->success_response([
            'items'      => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
        ]);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff_ratings';
        $id = (int) $request->get_param('id');

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        if (!$item) {
            return $this->error_response(__('Rating not found', 'asmaa-salon'), 404);
        }

        return $this->success_response($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            $table = $wpdb->prefix . 'asmaa_staff_ratings';

            $data = [
                'wp_user_id'    => (int) $request->get_param('staff_id'),
                'wc_customer_id' => $request->get_param('customer_id') ? (int) $request->get_param('customer_id') : null,
                'booking_id'  => $request->get_param('booking_id') ? (int) $request->get_param('booking_id') : null,
                'rating'      => min(5, max(1, (int) $request->get_param('rating'))),
                'comment'     => sanitize_textarea_field($request->get_param('comment')),
            ];

            if (empty($data['wp_user_id']) || empty($data['rating'])) {
                throw new \Exception(__('Staff ID and rating are required', 'asmaa-salon'));
            }

            $result = $wpdb->insert($table, $data);
            if ($result === false) {
                throw new \Exception(__('Failed to create rating', 'asmaa-salon'));
            }

            // Update staff aggregated stats
            $this->update_staff_rating_stats($data['wp_user_id']);

            $wpdb->query('COMMIT');

            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id));
            return $this->success_response($item, __('Rating created successfully', 'asmaa-salon'), 201);
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return $this->error_response($e->getMessage(), 500);
        }
    }

    /**
     * Update staff aggregated rating, total_ratings, and total_services.
     */
    protected function update_staff_rating_stats(int $wp_user_id): void
    {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'asmaa_staff_ratings';
        $extended_table = $wpdb->prefix . 'asmaa_staff_extended_data';

        // Calculate average rating
        $avg_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM {$ratings_table} WHERE wp_user_id = %d",
            $wp_user_id
        ));

        // Count total ratings
        $total_ratings = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$ratings_table} WHERE wp_user_id = %d",
            $wp_user_id
        ));

        // Count total services (from completed bookings or queue tickets)
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $total_services = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$bookings_table} WHERE wp_user_id = %d AND status = 'completed' AND deleted_at IS NULL",
            $wp_user_id
        ));

        // Update extended data
        $extended = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$extended_table} WHERE wp_user_id = %d", $wp_user_id));
        if ($extended) {
            $wpdb->update(
                $extended_table,
                [
                    'rating'         => round((float) $avg_rating, 2),
                    'total_ratings'  => $total_ratings,
                    'total_services' => $total_services,
                ],
                ['wp_user_id' => $wp_user_id]
            );
        } else {
            // Create extended data if doesn't exist
            $wpdb->insert($extended_table, [
                'wp_user_id' => $wp_user_id,
                'rating' => round((float) $avg_rating, 2),
                'total_ratings' => $total_ratings,
                'total_services' => $total_services,
            ]);
        }
    }
}

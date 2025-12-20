<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class Notifications_Controller extends Base_Controller
{
    protected string $rest_base = 'notifications';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_items'],
                'permission_callback' => $this->permission_callback('asmaa_notifications_view'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/read', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'mark_read'],
                'permission_callback' => $this->permission_callback('asmaa_notifications_update'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/read-all', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'mark_all_read'],
                'permission_callback' => $this->permission_callback('asmaa_notifications_update'),
            ],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!is_user_logged_in()) {
            return $this->error_response(__('Unauthorized', 'asmaa-salon'), 401);
        }

        global $wpdb;

        $table   = $wpdb->prefix . 'asmaa_notifications';
        $user_id = (int) get_current_user_id();

        $params = $this->get_pagination_params($request);
        $offset = ($params['page'] - 1) * $params['per_page'];

        $unread_only = $request->get_param('unread_only');
        $type        = sanitize_text_field((string) ($request->get_param('type') ?? ''));
        $since_id    = (int) ($request->get_param('since_id') ?? 0);

        $where = [
            $wpdb->prepare('notifiable_type = %s', 'WP_User'),
            $wpdb->prepare('notifiable_id = %d', $user_id),
            $wpdb->prepare('channel = %s', 'dashboard'),
        ];

        if ($unread_only === true || $unread_only === '1' || $unread_only === 1) {
            $where[] = 'read_at IS NULL';
        }

        if ($type !== '') {
            $where[] = $wpdb->prepare('type = %s', $type);
        }

        if ($since_id > 0) {
            $where[] = $wpdb->prepare('id > %d', $since_id);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM {$table}
                 {$where_clause}
                 ORDER BY id DESC
                 LIMIT %d OFFSET %d",
                $params['per_page'],
                $offset
            )
        );

        $unread_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$table}
                 WHERE notifiable_type = %s
                   AND notifiable_id = %d
                   AND channel = %s
                   AND read_at IS NULL",
                'WP_User',
                $user_id,
                'dashboard'
            )
        );

        return $this->success_response([
            'items' => $items,
            'pagination' => $this->build_pagination_meta($total, $params['page'], $params['per_page']),
            'unread_count' => $unread_count,
        ]);
    }

    public function mark_read(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!is_user_logged_in()) {
            return $this->error_response(__('Unauthorized', 'asmaa-salon'), 401);
        }

        global $wpdb;

        $table   = $wpdb->prefix . 'asmaa_notifications';
        $user_id = (int) get_current_user_id();
        $id      = (int) $request->get_param('id');

        $notification = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                 FROM {$table}
                 WHERE id = %d
                   AND notifiable_type = %s
                   AND notifiable_id = %d
                   AND channel = %s",
                $id,
                'WP_User',
                $user_id,
                'dashboard'
            )
        );

        if (!$notification) {
            return $this->error_response(__('Notification not found', 'asmaa-salon'), 404);
        }

        if ($notification->read_at === null) {
            $wpdb->update(
                $table,
                ['read_at' => current_time('mysql')],
                ['id' => $id]
            );
        }

        $unread_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$table}
                 WHERE notifiable_type = %s
                   AND notifiable_id = %d
                   AND channel = %s
                   AND read_at IS NULL",
                'WP_User',
                $user_id,
                'dashboard'
            )
        );

        return $this->success_response([
            'id' => $id,
            'unread_count' => $unread_count,
        ], __('Notification marked as read', 'asmaa-salon'));
    }

    public function mark_all_read(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!is_user_logged_in()) {
            return $this->error_response(__('Unauthorized', 'asmaa-salon'), 401);
        }

        global $wpdb;

        $table   = $wpdb->prefix . 'asmaa_notifications';
        $user_id = (int) get_current_user_id();

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                 SET read_at = %s
                 WHERE notifiable_type = %s
                   AND notifiable_id = %d
                   AND channel = %s
                   AND read_at IS NULL",
                current_time('mysql'),
                'WP_User',
                $user_id,
                'dashboard'
            )
        );

        return $this->success_response([
            'unread_count' => 0,
        ], __('All notifications marked as read', 'asmaa-salon'));
    }
}


<?php

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class ActivityLogger
{
    /**
     * Log an activity.
     */
    public static function log(
        string $log_name,
        string $description = '',
        ?string $subject_type = null,
        ?int $subject_id = null,
        ?string $causer_type = null,
        ?int $causer_id = null,
        ?array $properties = null
    ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_activity_log';

        $data = [
            'log_name'     => sanitize_text_field($log_name),
            'description'  => sanitize_textarea_field($description),
            'subject_type' => $subject_type ? sanitize_text_field($subject_type) : null,
            'subject_id'   => $subject_id ? (int) $subject_id : null,
            'causer_type'  => $causer_type ? sanitize_text_field($causer_type) : null,
            'causer_id'    => $causer_id ? (int) $causer_id : get_current_user_id(),
            'properties'   => $properties ? wp_json_encode($properties) : null,
        ];

        $wpdb->insert($table, $data);
    }

    /**
     * Log booking activity.
     */
    public static function log_booking(string $action, int $booking_id, ?int $customer_id = null, array $properties = []): void
    {
        self::log(
            'booking',
            sprintf('Booking #%d: %s', $booking_id, $action),
            'App\\Models\\Booking',
            $booking_id,
            'App\\Models\\Customer',
            $customer_id,
            $properties
        );
    }

    /**
     * Log queue ticket activity.
     */
    public static function log_queue_ticket(string $action, int $ticket_id, ?int $customer_id = null, array $properties = []): void
    {
        self::log(
            'queue',
            sprintf('Queue ticket #%d: %s', $ticket_id, $action),
            'App\\Models\\QueueTicket',
            $ticket_id,
            'App\\Models\\Customer',
            $customer_id,
            $properties
        );
    }

    /**
     * Log order activity.
     */
    public static function log_order(string $action, int $order_id, ?int $customer_id = null, array $properties = []): void
    {
        self::log(
            'order',
            sprintf('Order #%d: %s', $order_id, $action),
            'App\\Models\\Order',
            $order_id,
            'App\\Models\\Customer',
            $customer_id,
            $properties
        );
    }

    /**
     * Log payment activity.
     */
    public static function log_payment(string $action, int $payment_id, ?int $customer_id = null, array $properties = []): void
    {
        self::log(
            'payment',
            sprintf('Payment #%d: %s', $payment_id, $action),
            'App\\Models\\Payment',
            $payment_id,
            'App\\Models\\Customer',
            $customer_id,
            $properties
        );
    }

    /**
     * Log loyalty activity.
     */
    public static function log_loyalty(string $action, int $customer_id, array $properties = []): void
    {
        self::log(
            'loyalty',
            sprintf('Loyalty transaction for customer #%d: %s', $customer_id, $action),
            'App\\Models\\Customer',
            $customer_id,
            null,
            get_current_user_id(),
            $properties
        );
    }
}

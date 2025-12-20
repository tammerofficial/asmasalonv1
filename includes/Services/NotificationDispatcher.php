<?php

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class NotificationDispatcher
{
    /**
     * Dispatch a notification (store in DB and fire WordPress hooks).
     */
    public static function dispatch(
        string $type,
        string $notifiable_type,
        int $notifiable_id,
        array $data = [],
        string $channel = 'sms'
    ): int {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_notifications';

        $status = ($channel === 'dashboard') ? 'sent' : 'pending';
        $sent_at = ($channel === 'dashboard') ? current_time('mysql') : null;

        $notification_data = [
            'type'           => sanitize_text_field($type),
            'notifiable_type' => sanitize_text_field($notifiable_type),
            'notifiable_id'  => (int) $notifiable_id,
            'data'           => wp_json_encode($data),
            'status'         => $status,
            'channel'        => sanitize_text_field($channel),
            'sent_at'        => $sent_at,
        ];

        $wpdb->insert($table, $notification_data);
        $notification_id = (int) $wpdb->insert_id;

        // Fire WordPress hooks for actual delivery
        do_action('asmaa_salon_notification_dispatched', $notification_id, $type, $notifiable_type, $notifiable_id, $data, $channel);

        // Dashboard notifications are stored only (no external delivery).
        if ($channel === 'dashboard') {
            return $notification_id;
        }

        if ($channel === 'sms') {
            do_action('asmaa_salon_send_sms', $notifiable_id, $data, $notification_id);
        } elseif ($channel === 'email') {
            do_action('asmaa_salon_send_email', $notifiable_id, $data, $notification_id);
        }

        return $notification_id;
    }

    /**
     * Create a dashboard notification for a WordPress user.
     */
    public static function dashboard_user(int $wp_user_id, string $type, array $data = []): int
    {
        return self::dispatch(
            $type,
            'WP_User',
            $wp_user_id,
            $data,
            'dashboard'
        );
    }

    /**
     * Create a dashboard notification for all admins (manage_options).
     */
    public static function dashboard_admins(string $type, array $data = []): array
    {
        $user_ids = get_users([
            'fields' => 'ID',
            'capability' => 'manage_options',
        ]);

        $created_ids = [];
        foreach ($user_ids as $id) {
            $created_ids[] = self::dashboard_user((int) $id, $type, $data);
        }

        return $created_ids;
    }

    /**
     * Mark notification as sent.
     */
    public static function mark_sent(int $notification_id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_notifications';

        $wpdb->update(
            $table,
            [
                'status' => 'sent',
                'sent_at' => current_time('mysql'),
            ],
            ['id' => $notification_id]
        );
    }

    /**
     * Send booking confirmation notification.
     */
    public static function booking_confirmation(int $customer_id, array $booking_data): int
    {
        return self::dispatch(
            'BookingConfirmation',
            'App\\Models\\Customer',
            $customer_id,
            $booking_data,
            'sms'
        );
    }

    /**
     * Send staff alert notification.
     */
    public static function staff_alert(int $staff_id, array $alert_data): int
    {
        return self::dispatch(
            'StaffAlert',
            'App\\Models\\Staff',
            $staff_id,
            $alert_data,
            'sms'
        );
    }

    /**
     * Send post-visit thank you notification.
     */
    public static function post_visit_thank_you(int $customer_id, array $visit_data): int
    {
        return self::dispatch(
            'PostVisitThankYou',
            'App\\Models\\Customer',
            $customer_id,
            $visit_data,
            'sms'
        );
    }

    /**
     * Send commission summary notification.
     */
    public static function commission_summary(int $staff_id, array $commission_data): int
    {
        return self::dispatch(
            'CommissionSummary',
            'App\\Models\\Staff',
            $staff_id,
            $commission_data,
            'sms'
        );
    }

    /**
     * ✅ NEW: Send low stock alert to all admins.
     * This is triggered when product stock falls below min_stock_level.
     */
    public static function low_stock_alert(int $product_id, array $product_data): array
    {
        $product_name = $product_data['name'] ?? 'Product';
        $current_stock = $product_data['current_stock'] ?? 0;
        $min_stock_level = $product_data['min_stock_level'] ?? 0;
        $sku = $product_data['sku'] ?? '';

        // Build detailed notification messages
        $notification_data = [
            'type' => 'low_stock',
            'product_id' => $product_id,
            'product_name' => $product_name,
            'current_stock' => $current_stock,
            'min_stock_level' => $min_stock_level,
            'sku' => $sku,
            
            // ✅ Arabic title & message
            'title_ar' => '⚠️ تنبيه: مخزون منخفض',
            'message_ar' => sprintf(
                'المنتج "%s" (SKU: %s) أصبح المخزون منخفض! الكمية الحالية: %d (الحد الأدنى: %d)',
                $product_name,
                $sku ?: 'N/A',
                $current_stock,
                $min_stock_level
            ),
            
            // ✅ English title & message
            'title_en' => '⚠️ Low Stock Alert',
            'message_en' => sprintf(
                'Product "%s" (SKU: %s) is running low! Current stock: %d (Min level: %d)',
                $product_name,
                $sku ?: 'N/A',
                $current_stock,
                $min_stock_level
            ),
            
            // ✅ Action: redirect to products page
            'action' => [
                'route' => '/products',
                'query' => [
                    'low_stock' => '1',
                    'product_id' => $product_id,
                ],
            ],
        ];

        return self::dashboard_admins('low_stock', $notification_data);
    }

    /**
     * ✅ NEW: Send membership expiry alert to admins.
     * This is triggered when membership is about to expire or has expired.
     */
    public static function membership_expiry_alert(int $membership_id, array $membership_data, int $days_until_expiry): array
    {
        $customer_name = $membership_data['customer_name'] ?? 'Customer';
        $customer_phone = $membership_data['customer_phone'] ?? '';
        $plan_name = $membership_data['plan_name'] ?? 'Membership';
        $end_date = $membership_data['end_date'] ?? '';

        // Determine the alert type
        if ($days_until_expiry > 0) {
            $alert_type = 'expiring_soon'; // قبل الانتهاء بـ5 أيام
            $title_ar = '⚠️ تنبيه: عضوية قاربت على الانتهاء';
            $title_en = '⚠️ Membership Expiring Soon';
            $message_ar = sprintf(
                'عضوية العميلة "%s" (%s) في باقة "%s" ستنتهي بعد %d يوم (تاريخ الانتهاء: %s)',
                $customer_name,
                $customer_phone ?: 'N/A',
                $plan_name,
                $days_until_expiry,
                $end_date
            );
            $message_en = sprintf(
                'Membership for customer "%s" (%s) in plan "%s" will expire in %d day(s) (Expiry date: %s)',
                $customer_name,
                $customer_phone ?: 'N/A',
                $plan_name,
                $days_until_expiry,
                $end_date
            );
        } else {
            $alert_type = 'expired_today'; // اليوم نفسه
            $title_ar = '🔴 تنبيه: عضوية انتهت اليوم';
            $title_en = '🔴 Membership Expired Today';
            $message_ar = sprintf(
                'عضوية العميلة "%s" (%s) في باقة "%s" انتهت اليوم (%s)',
                $customer_name,
                $customer_phone ?: 'N/A',
                $plan_name,
                $end_date
            );
            $message_en = sprintf(
                'Membership for customer "%s" (%s) in plan "%s" expired today (%s)',
                $customer_name,
                $customer_phone ?: 'N/A',
                $plan_name,
                $end_date
            );
        }

        // Build notification data
        $notification_data = [
            'type' => 'membership_expiry',
            'alert_type' => $alert_type,
            'membership_id' => $membership_id,
            'customer_id' => $membership_data['customer_id'] ?? null,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'plan_name' => $plan_name,
            'end_date' => $end_date,
            'days_until_expiry' => $days_until_expiry,
            
            'title_ar' => $title_ar,
            'message_ar' => $message_ar,
            
            'title_en' => $title_en,
            'message_en' => $message_en,
            
            // Action: redirect to memberships page
            'action' => [
                'route' => '/memberships',
                'query' => [
                    'tab' => 'members',
                    'customer_id' => $membership_data['customer_id'] ?? null,
                ],
            ],
        ];

        return self::dashboard_admins('membership_expiry', $notification_data);
    }
}

<?php
/**
 * Membership Expiry Checker Service
 * 
 * هذا الكلاس مسؤول عن فحص العضويات اللي قاربت على الانتهاء وإرسال إشعارات
 * 
 * @package AsmaaSalon\Services
 */

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class MembershipExpiryChecker
{
    /**
     * Schedule the daily membership check.
     * يتم استدعاء هذه الوظيفة عند تفعيل البلجن
     */
    public static function schedule_daily_check(): void
    {
        if (!wp_next_scheduled('asmaa_salon_check_membership_expiry')) {
            // Schedule daily at 9:00 AM
            wp_schedule_event(
                strtotime('tomorrow 09:00:00'),
                'daily',
                'asmaa_salon_check_membership_expiry'
            );
        }
    }

    /**
     * Clear the scheduled event.
     * يتم استدعاء هذه الوظيفة عند إلغاء تفعيل البلجن
     */
    public static function clear_schedule(): void
    {
        $timestamp = wp_next_scheduled('asmaa_salon_check_membership_expiry');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'asmaa_salon_check_membership_expiry');
        }
    }

    /**
     * Check all memberships and send notifications.
     * هذه الوظيفة تشتغل يومياً عن طريق WP-Cron
     */
    public static function check_and_notify(): void
    {
        global $wpdb;

        $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
        $notifications_table = $wpdb->prefix . 'asmaa_notifications';

        $today = current_time('Y-m-d');
        $five_days_later = date('Y-m-d', strtotime($today . ' +5 days'));

        // 1. Find memberships expiring in 5 days (قبل 5 أيام)
        $expiring_soon = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                m.id as membership_id,
                m.customer_id,
                m.end_date,
                c.name as customer_name,
                c.phone as customer_phone,
                p.name_ar as plan_name_ar,
                p.name as plan_name,
                DATEDIFF(m.end_date, %s) as days_until_expiry
            FROM {$memberships_table} m
            LEFT JOIN {$customers_table} c ON m.customer_id = c.id
            LEFT JOIN {$plans_table} p ON m.membership_plan_id = p.id
            WHERE m.status = 'active'
            AND m.end_date = %s
            AND c.deleted_at IS NULL",
            $today,
            $five_days_later
        ));

        // 2. Find memberships expiring today (اليوم نفسه)
        $expiring_today = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                m.id as membership_id,
                m.customer_id,
                m.end_date,
                c.name as customer_name,
                c.phone as customer_phone,
                p.name_ar as plan_name_ar,
                p.name as plan_name,
                0 as days_until_expiry
            FROM {$memberships_table} m
            LEFT JOIN {$customers_table} c ON m.customer_id = c.id
            LEFT JOIN {$plans_table} p ON m.membership_plan_id = p.id
            WHERE m.status = 'active'
            AND m.end_date = %s
            AND c.deleted_at IS NULL",
            $today
        ));

        // 3. Process memberships expiring in 5 days
        foreach ($expiring_soon as $membership) {
            // Check if we already sent notification for this membership
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$notifications_table} 
                 WHERE type = 'membership_expiry' 
                 AND JSON_EXTRACT(data, '$.membership_id') = %d
                 AND JSON_EXTRACT(data, '$.alert_type') = 'expiring_soon'
                 AND DATE(created_at) = %s",
                $membership->membership_id,
                $today
            ));

            if (!$existing) {
                // Send notification
                NotificationDispatcher::membership_expiry_alert(
                    $membership->membership_id,
                    [
                        'customer_id' => $membership->customer_id,
                        'customer_name' => $membership->customer_name,
                        'customer_phone' => $membership->customer_phone,
                        'plan_name' => $membership->plan_name_ar ?: $membership->plan_name,
                        'end_date' => $membership->end_date,
                    ],
                    5 // 5 days until expiry
                );

                error_log("Asmaa Salon: Sent expiring soon notification for membership #{$membership->membership_id}");
            }
        }

        // 4. Process memberships expiring today
        foreach ($expiring_today as $membership) {
            // Check if we already sent notification for this membership today
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$notifications_table} 
                 WHERE type = 'membership_expiry' 
                 AND JSON_EXTRACT(data, '$.membership_id') = %d
                 AND JSON_EXTRACT(data, '$.alert_type') = 'expired_today'
                 AND DATE(created_at) = %s",
                $membership->membership_id,
                $today
            ));

            if (!$existing) {
                // Send notification
                NotificationDispatcher::membership_expiry_alert(
                    $membership->membership_id,
                    [
                        'customer_id' => $membership->customer_id,
                        'customer_name' => $membership->customer_name,
                        'customer_phone' => $membership->customer_phone,
                        'plan_name' => $membership->plan_name_ar ?: $membership->plan_name,
                        'end_date' => $membership->end_date,
                    ],
                    0 // Expiring today
                );

                // Update membership status to 'expired'
                $wpdb->update(
                    $memberships_table,
                    ['status' => 'expired'],
                    ['id' => $membership->membership_id]
                );

                error_log("Asmaa Salon: Sent expired today notification for membership #{$membership->membership_id}");
            }
        }

        error_log(sprintf(
            'Asmaa Salon: Membership expiry check completed. Expiring soon: %d, Expiring today: %d',
            count($expiring_soon),
            count($expiring_today)
        ));
    }
}

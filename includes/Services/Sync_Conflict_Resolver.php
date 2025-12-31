<?php

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class Sync_Conflict_Resolver
{
    /**
     * Resolve conflict when both systems have different data
     * Strategy: Last modified wins (timestamp-based)
     */
    public static function resolve_product_conflict(int $asmaa_product_id, int $wc_product_id): string
    {
        global $wpdb;
        
        // Get timestamps
        $asmaa_product = $wpdb->get_row($wpdb->prepare(
            "SELECT updated_at, wc_synced_at FROM {$wpdb->prefix}asmaa_products WHERE id = %d",
            $asmaa_product_id
        ));

        $wc_product = wc_get_product($wc_product_id);
        $wc_modified = $wc_product ? $wc_product->get_date_modified()->date('Y-m-d H:i:s') : null;

        // Compare timestamps
        $asmaa_time = $asmaa_product->updated_at ?? $asmaa_product->wc_synced_at ?? '1970-01-01 00:00:00';
        
        if ($wc_modified && strtotime($wc_modified) > strtotime($asmaa_time)) {
            // WooCommerce is newer - sync from WC to Asmaa
            return 'from_wc';
        } else {
            // Asmaa is newer - sync from Asmaa to WC
            return 'to_wc';
        }
    }

    /**
     * Resolve conflict for orders
     */
    public static function resolve_order_conflict(int $asmaa_order_id, int $wc_order_id): string
    {
        global $wpdb;
        
        $asmaa_order = $wpdb->get_row($wpdb->prepare(
            "SELECT updated_at, wc_synced_at FROM {$wpdb->prefix}asmaa_orders WHERE id = %d",
            $asmaa_order_id
        ));

        $wc_order = wc_get_order($wc_order_id);
        $wc_modified = $wc_order ? $wc_order->get_date_modified()->date('Y-m-d H:i:s') : null;

        $asmaa_time = $asmaa_order->updated_at ?? $asmaa_order->wc_synced_at ?? '1970-01-01 00:00:00';
        
        if ($wc_modified && strtotime($wc_modified) > strtotime($asmaa_time)) {
            return 'from_wc';
        } else {
            return 'to_wc';
        }
    }

    /**
     * Resolve conflict for customers
     */
    public static function resolve_customer_conflict(int $asmaa_customer_id, int $wc_customer_id): string
    {
        global $wpdb;
        
        $asmaa_customer = $wpdb->get_row($wpdb->prepare(
            "SELECT updated_at, wc_synced_at FROM {$wpdb->prefix}asmaa_customers WHERE id = %d",
            $asmaa_customer_id
        ));

        $wc_customer = new \WC_Customer($wc_customer_id);
        $wc_modified = $wc_customer->get_date_modified() ? $wc_customer->get_date_modified()->date('Y-m-d H:i:s') : null;

        $asmaa_time = $asmaa_customer->updated_at ?? $asmaa_customer->wc_synced_at ?? '1970-01-01 00:00:00';
        
        if ($wc_modified && strtotime($wc_modified) > strtotime($asmaa_time)) {
            return 'from_wc';
        } else {
            return 'to_wc';
        }
    }

    /**
     * Notify admin about conflict
     */
    public static function notify_conflict(string $entity_type, int $entity_id, string $resolution): void
    {
        NotificationDispatcher::dashboard_admins('Dashboard.SyncConflict', [
            'event' => 'sync.conflict',
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'resolution' => $resolution,
            'title_en' => 'Sync Conflict Resolved',
            'message_en' => sprintf('Conflict in %s #%d resolved: %s', $entity_type, $entity_id, $resolution),
            'title_ar' => 'تم حل تعارض المزامنة',
            'message_ar' => sprintf('تم حل تعارض في %s #%d: %s', $entity_type, $entity_id, $resolution),
            'severity' => 'warning',
        ]);
    }
}


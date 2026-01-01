<?php

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class Commission_Service
{
    /**
     * Process commissions for order items
     * 
     * @param int $order_id WooCommerce order ID
     * @param array $order_items Array of order items with structure: ['staff_id', 'item_type', 'total', 'item_name', 'id']
     * @param int|null $booking_id Optional booking ID for rating bonus
     * @return array Array of created commission records
     * @throws \Exception
     */
    public static function process_order_commissions(int $order_id, array $order_items, ?int $booking_id = null): array
    {
        global $wpdb;

        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';
        $commissions = [];

        // Get commission settings
        $programs = get_option('asmaa_salon_programs_settings', []);
        $comm = is_array($programs) && isset($programs['commissions']) && is_array($programs['commissions']) ? $programs['commissions'] : [];
        $enabled = array_key_exists('enabled', $comm) ? (bool) $comm['enabled'] : true;
        
        if (!$enabled) {
            return [];
        }

        $default_service_rate = (float) ($comm['default_service_rate'] ?? 10.00);
        $default_product_rate = (float) ($comm['default_product_rate'] ?? 5.00);
        $staff_overrides = isset($comm['staff_overrides']) && is_array($comm['staff_overrides']) ? $comm['staff_overrides'] : [];

        // Get rating bonus settings from commission settings table
        $settings_table = $wpdb->prefix . 'asmaa_commission_settings';
        $settings = $wpdb->get_row("SELECT * FROM {$settings_table} ORDER BY id DESC LIMIT 1");
        $bonus_5_star = $settings ? (float) ($settings->rating_bonus_5_star ?? 0.0) : 0.0;
        $bonus_4_star = $settings ? (float) ($settings->rating_bonus_4_star ?? 0.0) : 0.0;

        foreach ($order_items as $row) {
            $staff_id = !empty($row['staff_id']) ? (int) $row['staff_id'] : null;
            if (!$staff_id) {
                continue;
            }

            $item_type = (string) ($row['item_type'] ?? '');
            $base_amount = (float) ($row['total'] ?? 0);
            
            if ($base_amount <= 0) {
                continue;
            }

            // Get commission rate (check staff overrides first)
            $rate = $item_type === 'product' ? $default_product_rate : $default_service_rate;
            if (isset($staff_overrides[(string) $staff_id]) && is_array($staff_overrides[(string) $staff_id])) {
                $ov = $staff_overrides[(string) $staff_id];
                $rate = $item_type === 'product'
                    ? (float) ($ov['product_rate'] ?? $rate)
                    : (float) ($ov['service_rate'] ?? $rate);
            }
            
            if ($rate < 0) {
                $rate = 0;
            }

            $commission_amount = round($base_amount * ($rate / 100), 3);
            
            if ($commission_amount <= 0) {
                continue;
            }

            // Calculate rating bonus if booking_id is provided
            $rating_bonus = 0.0;
            if ($booking_id) {
                $ratings_table = $wpdb->prefix . 'asmaa_staff_ratings';
                $rating = $wpdb->get_row($wpdb->prepare(
                    "SELECT rating FROM {$ratings_table} WHERE booking_id = %d ORDER BY created_at DESC LIMIT 1",
                    (int) $booking_id
                ));
                
                if ($rating) {
                    $rating_value = (int) $rating->rating;
                    if ($rating_value === 5 && $bonus_5_star > 0) {
                        $rating_bonus = $bonus_5_star;
                    } elseif ($rating_value === 4 && $bonus_4_star > 0) {
                        $rating_bonus = $bonus_4_star;
                    }
                }
            }

            $final_amount = $commission_amount + $rating_bonus;

            // Insert commission record
            $wpdb->insert($commissions_table, [
                'wp_user_id' => $staff_id,
                'order_id' => $order_id,
                'order_item_id' => (int) ($row['id'] ?? 0),
                'booking_id' => $booking_id ?: null,
                'base_amount' => $base_amount,
                'commission_rate' => $rate,
                'commission_amount' => $commission_amount,
                'rating_bonus' => $rating_bonus,
                'final_amount' => $final_amount,
                'status' => 'pending',
                'notes' => (string) ($row['item_name'] ?? ''),
            ]);

            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create staff commission', 'asmaa-salon'));
            }

            $commissions[] = [
                'staff_id' => $staff_id,
                'item_id' => (int) ($row['id'] ?? 0),
                'amount' => $final_amount,
                'commission_id' => $wpdb->insert_id,
            ];
        }

        return $commissions;
    }

    /**
     * Calculate commissions from WooCommerce order directly
     * This method reads staff_id from WC order item meta
     * 
     * @param int $wc_order_id WooCommerce order ID
     * @param int|null $booking_id Optional booking ID for rating bonus
     * @return array Array of created commission records
     * @throws \Exception
     */
    public static function calculate_from_wc_order(int $wc_order_id, ?int $booking_id = null): array
    {
        if (!class_exists('WooCommerce')) {
            return [];
        }

        $wc_order = wc_get_order($wc_order_id);
        if (!$wc_order) {
            return [];
        }
        
        global $wpdb;
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';
        $extended_table = $wpdb->prefix . 'asmaa_staff_extended_data';
        $ratings_table = $wpdb->prefix . 'asmaa_staff_ratings';
        $commissions = [];
        
        // Get commission settings from database
        $settings_table = $wpdb->prefix . 'asmaa_commission_settings';
        $settings = $wpdb->get_row("SELECT * FROM {$settings_table} ORDER BY id DESC LIMIT 1");
        $default_service_rate = $settings ? (float) ($settings->service_commission_rate ?? 0.0) : 0.0;
        $default_product_rate = $settings ? (float) ($settings->product_commission_rate ?? 0.0) : 0.0;
        $bonus_5_star = $settings ? (float) ($settings->rating_bonus_5_star ?? 0.0) : 0.0;
        $bonus_4_star = $settings ? (float) ($settings->rating_bonus_4_star ?? 0.0) : 0.0;
        
        foreach ($wc_order->get_items() as $item) {
            $staff_id = $item->get_meta('_asmaa_staff_id');
            
            if (!$staff_id) {
                continue; // Skip items without staff
            }
            
            $item_total = (float) $item->get_total();
            if ($item_total <= 0) {
                continue;
            }
            
            // Determine item type
            $is_service = false;
            if ($item instanceof \WC_Order_Item_Product) {
                $product = $item->get_product();
                $is_service = $product && $product->is_virtual();
            }
            
            // Get staff commission rate (from extended table or default)
            $extended = $wpdb->get_row($wpdb->prepare(
                "SELECT commission_rate FROM {$extended_table} WHERE wp_user_id = %d",
                (int) $staff_id
            ));
            
            $rate = $extended && $extended->commission_rate !== null
                ? (float) $extended->commission_rate
                : ($is_service ? $default_service_rate : $default_product_rate);
            
            if ($rate <= 0) {
                continue;
            }
            
            $commission_amount = round($item_total * ($rate / 100), 3);
            
            if ($commission_amount <= 0) {
                continue;
            }
            
            // Rating bonus based on booking rating (latest)
            $rating_bonus = 0.0;
            if ($booking_id) {
                $rating = $wpdb->get_row($wpdb->prepare(
                    "SELECT rating FROM {$ratings_table} WHERE booking_id = %d ORDER BY created_at DESC LIMIT 1",
                    (int) $booking_id
                ));
                
                if ($rating) {
                    $rating_value = (int) $rating->rating;
                    if ($rating_value === 5 && $bonus_5_star > 0) {
                        $rating_bonus = $bonus_5_star;
                    } elseif ($rating_value === 4 && $bonus_4_star > 0) {
                        $rating_bonus = $bonus_4_star;
                    }
                }
            }
            
            $final_amount = $commission_amount + $rating_bonus;
            
            // Check if commission already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$commissions_table} WHERE order_id = %d AND order_item_id = %d",
                $wc_order_id,
                $item->get_id()
            ));

            if ($existing) {
                continue; // Skip if already exists
            }
            
            // Insert commission record
            $wpdb->insert($commissions_table, [
                'wp_user_id' => (int) $staff_id,
                'order_id' => $wc_order_id,
                'order_item_id' => $item->get_id(),
                'booking_id' => $booking_id ?: null,
                'base_amount' => $item_total,
                'commission_rate' => $rate,
                'commission_amount' => $commission_amount,
                'rating_bonus' => $rating_bonus,
                'final_amount' => $final_amount,
                'status' => 'pending',
                'notes' => sprintf(
                    'Auto commission from WC order #%d for %s: %s',
                    $wc_order_id,
                    $is_service ? 'service' : 'product',
                    $item->get_name()
                ),
            ]);
            
            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create staff commission', 'asmaa-salon'));
            }
            
            $commissions[] = [
                'staff_id' => (int) $staff_id,
                'item_id' => $item->get_id(),
                'amount' => $final_amount,
                'commission_id' => $wpdb->insert_id,
            ];
        }
        
        return $commissions;
    }

    /**
     * Get staff total commissions
     * 
     * @param int $staff_id Staff user ID
     * @param string $status Commission status (pending, approved, paid)
     * @param string|null $start_date Start date (Y-m-d)
     * @param string|null $end_date End date (Y-m-d)
     * @return array ['total' => float, 'count' => int]
     */
    public static function get_staff_total(int $staff_id, string $status = 'pending', ?string $start_date = null, ?string $end_date = null): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'asmaa_staff_commissions';

        $where = [$wpdb->prepare('wp_user_id = %d', $staff_id)];
        $where[] = $wpdb->prepare('status = %s', $status);

        if ($start_date && $end_date) {
            $where[] = $wpdb->prepare('DATE(created_at) BETWEEN %s AND %s', $start_date, $end_date);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);

        $total = (float) $wpdb->get_var("SELECT SUM(final_amount) FROM {$table} {$where_clause}");
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_clause}");

        return [
            'total' => $total ?: 0.0,
            'count' => $count,
        ];
    }
}


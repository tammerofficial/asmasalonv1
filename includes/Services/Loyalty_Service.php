<?php

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Service
{
    /**
     * Process loyalty points for a customer order
     * 
     * @param int $customer_id WooCommerce customer ID
     * @param int $order_id WooCommerce order ID
     * @param string $order_number Order number
     * @param array $order_items Array of order items with structure: ['item_name', 'quantity', 'item_type', 'id']
     * @param float $total Order total amount
     * @return void
     * @throws \Exception
     */
    public static function process_order_points(int $customer_id, int $order_id, string $order_number, array $order_items, float $total): void
    {
        global $wpdb;

        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';

        // Get or create extended data
        $extended = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$extended_table} WHERE wc_customer_id = %d",
            $customer_id
        ));
        
        if (!$extended) {
            // Create extended data if doesn't exist
            $wpdb->insert($extended_table, [
                'wc_customer_id' => $customer_id,
                'total_visits' => 1,
                'total_spent' => $total,
                'loyalty_points' => 0,
            ]);
            $extended = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$extended_table} WHERE wc_customer_id = %d",
                $customer_id
            ));
        }

        // Always update customer totals for purchases
        $wpdb->query($wpdb->prepare(
            "UPDATE {$extended_table}
             SET total_visits = COALESCE(total_visits, 0) + 1,
                 total_spent = COALESCE(total_spent, 0) + %f
             WHERE wc_customer_id = %d",
            $total,
            $customer_id
        ));

        // Get loyalty program settings
        $programs = get_option('asmaa_salon_programs_settings', []);
        $loyalty = is_array($programs) && isset($programs['loyalty']) && is_array($programs['loyalty']) ? $programs['loyalty'] : [];
        $enabled = array_key_exists('enabled', $loyalty) ? (bool) $loyalty['enabled'] : true;
        
        if (!$enabled) {
            return;
        }

        $points_per_item = (int) ($loyalty['default_service_points'] ?? 1);
        if ($points_per_item < 0) {
            $points_per_item = 0;
        }

        $balance_before = (int) ($extended->loyalty_points ?? 0);
        $balance_after = $balance_before;

        // Process points for each item
        foreach ($order_items as $row) {
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $points = $points_per_item * $qty;
            
            if ($points <= 0) {
                continue;
            }

            $next_balance = $balance_after + $points;
            $desc = sprintf(
                'طلب رقم %s: %s (عدد %d)',
                $order_number,
                (string) ($row['item_name'] ?? 'خدمة'),
                $qty
            );

            $wpdb->insert($transactions_table, [
                'wc_customer_id' => $customer_id,
                'type' => 'earned',
                'points' => $points,
                'balance_before' => $balance_after,
                'balance_after' => $next_balance,
                'reference_type' => 'order_item',
                'reference_id' => (int) ($row['id'] ?? 0),
                'description' => $desc,
                'wp_user_id' => get_current_user_id(),
            ]);

            if ($wpdb->last_error) {
                throw new \Exception(__('Failed to create loyalty transaction', 'asmaa-salon'));
            }

            $balance_after = $next_balance;
        }

        // Update customer balance if changed
        if ($balance_after !== $balance_before) {
            $wpdb->update($extended_table, ['loyalty_points' => $balance_after], ['wc_customer_id' => $customer_id]);
            
            // Update Apple Wallet pass
            try {
                Apple_Wallet_Service::update_loyalty_pass($customer_id);
            } catch (\Exception $e) {
                error_log('Apple Wallet update failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Redeem loyalty points for a discount
     * 
     * @param int $customer_id WooCommerce customer ID
     * @param int $points Points to redeem
     * @param string $reference_type Reference type (e.g., 'order', 'booking')
     * @param int|null $reference_id Reference ID
     * @return array ['success' => bool, 'discount_amount' => float, 'balance_after' => int]
     * @throws \Exception
     */
    public static function redeem_points(int $customer_id, int $points, string $reference_type = 'order', ?int $reference_id = null): array
    {
        global $wpdb;

        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $transactions_table = $wpdb->prefix . 'asmaa_loyalty_transactions';

        // Get customer balance
        $extended = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$extended_table} WHERE wc_customer_id = %d",
            $customer_id
        ));

        if (!$extended) {
            throw new \Exception(__('Customer loyalty data not found', 'asmaa-salon'));
        }

        $balance_before = (int) ($extended->loyalty_points ?? 0);
        
        if ($balance_before < $points) {
            throw new \Exception(__('Insufficient loyalty points', 'asmaa-salon'));
        }

        $balance_after = $balance_before - $points;

        // Get redemption rate from settings
        $programs = get_option('asmaa_salon_programs_settings', []);
        $loyalty = is_array($programs) && isset($programs['loyalty']) && is_array($programs['loyalty']) ? $programs['loyalty'] : [];
        $points_per_kwd = (float) ($loyalty['points_per_kwd'] ?? 1.0);
        
        if ($points_per_kwd <= 0) {
            $points_per_kwd = 1.0;
        }

        $discount_amount = round($points / $points_per_kwd, 3);

        // Create redemption transaction
        $wpdb->insert($transactions_table, [
            'wc_customer_id' => $customer_id,
            'type' => 'redeemed',
            'points' => -$points,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'description' => sprintf('استبدال %d نقطة مقابل خصم بقيمة %.3f د.ك', $points, $discount_amount),
            'wp_user_id' => get_current_user_id(),
        ]);

        if ($wpdb->last_error) {
            throw new \Exception(__('Failed to create loyalty redemption transaction', 'asmaa-salon'));
        }

        // Update customer balance
        $wpdb->update($extended_table, ['loyalty_points' => $balance_after], ['wc_customer_id' => $customer_id]);

        // Update Apple Wallet pass
        try {
            Apple_Wallet_Service::update_loyalty_pass($customer_id);
        } catch (\Exception $e) {
            error_log('Apple Wallet update failed: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'discount_amount' => $discount_amount,
            'balance_after' => $balance_after,
        ];
    }

    /**
     * Get customer loyalty balance
     * 
     * @param int $customer_id WooCommerce customer ID
     * @return array ['points' => int, 'total_spent' => float, 'total_visits' => int]
     */
    public static function get_customer_balance(int $customer_id): array
    {
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';

        $extended = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$extended_table} WHERE wc_customer_id = %d",
            $customer_id
        ));

        if (!$extended) {
            return [
                'points' => 0,
                'total_spent' => 0.0,
                'total_visits' => 0,
            ];
        }

        return [
            'points' => (int) ($extended->loyalty_points ?? 0),
            'total_spent' => (float) ($extended->total_spent ?? 0),
            'total_visits' => (int) ($extended->total_visits ?? 0),
        ];
    }
}


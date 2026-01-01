<?php

namespace AsmaaSalon\Services;

use AsmaaSalon\Helpers\QR_Code_Generator;
use AsmaaSalon\Helpers\Apple_Push_Notification_Service;
use AsmaaSalon\Config\Apple_Wallet_Config;

if (!defined('ABSPATH')) {
    exit;
}

class Apple_Wallet_Service
{
    // Pass types
    const PASS_TYPE_LOYALTY = 'loyalty';
    const PASS_TYPE_MEMBERSHIP = 'membership';
    const PASS_TYPE_PROGRAMS = 'programs';
    const PASS_TYPE_COMMISSIONS = 'commissions';
    
    /**
     * Create Apple Wallet pass for customer (legacy - creates loyalty pass)
     * 
     * @param int $wc_customer_id WooCommerce customer ID (WordPress user ID)
     * @return array Pass data with pass URL
     */
    public static function create_pass(int $wc_customer_id): array
    {
        return self::create_loyalty_pass($wc_customer_id);
    }
    
    /**
     * Create Apple Wallet pass for customer by type
     * 
     * @param int $wc_customer_id WooCommerce customer ID (WordPress user ID)
     * @param string $pass_type Pass type: loyalty, membership, programs, commissions
     * @return array Pass data with pass URL
     */
    public static function create_pass_by_type(int $wc_customer_id, string $pass_type): array
    {
        switch ($pass_type) {
            case self::PASS_TYPE_LOYALTY:
                return self::create_loyalty_pass($wc_customer_id);
            case self::PASS_TYPE_MEMBERSHIP:
                return self::create_membership_pass($wc_customer_id);
            case self::PASS_TYPE_PROGRAMS:
                return self::create_programs_pass($wc_customer_id);
            case self::PASS_TYPE_COMMISSIONS:
                return self::create_commissions_pass($wc_customer_id);
            default:
                throw new \Exception(__('Invalid pass type', 'asmaa-salon'));
        }
    }
    
    /**
     * Create Apple Wallet loyalty pass for customer
     * 
     * @param int $wc_customer_id WooCommerce customer ID (WordPress user ID)
     * @return array Pass data with pass URL
     */
    public static function create_loyalty_pass(int $wc_customer_id): array
    {
        global $wpdb;
        
        // Get customer data
        $user = get_user_by('ID', $wc_customer_id);
        if (!$user) {
            throw new \Exception(__('Customer not found', 'asmaa-salon'));
        }

        // Security check: only customers can have loyalty passes
        if (!in_array('customer', (array) $user->roles)) {
            throw new \Exception(__('Loyalty passes can only be created for customers', 'asmaa-salon'));
        }
        
        // Check if pass already exists
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        
        // Get extended data
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $wc_customer_id)
        );
        
        // Get current membership
        $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, p.name AS plan_name, p.name_ar AS plan_name_ar
             FROM {$memberships_table} m
             LEFT JOIN {$plans_table} p ON p.id = m.membership_plan_id
             WHERE m.wc_customer_id = %d AND m.status = 'active'
             ORDER BY m.end_date DESC LIMIT 1",
            $wc_customer_id
        ));
        
        // Generate QR code
        $qr_data = QR_Code_Generator::generate_for_customer($wc_customer_id);
        
        // Generate pass identifiers
        $serial_number = 'LOYALTY-' . $wc_customer_id . '-' . time();
        $auth_token = wp_generate_password(32, false);
        $pass_type_id = Apple_Wallet_Config::PASS_TYPE_ID;
        
        // Get template from database
        $template = get_option('asmaa_salon_apple_wallet_loyalty_template', Apple_Wallet_Config::get_default_loyalty_template());
        
        // Build pass data
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $serial_number,
            'teamIdentifier' => Apple_Wallet_Config::TEAM_ID,
            'organizationName' => Apple_Wallet_Config::ORGANIZATION_NAME,
            'description' => __('Loyalty Card', 'asmaa-salon'),
            'logoText' => $template['logoText'] ?? get_bloginfo('name'),
            'foregroundColor' => $template['foregroundColor'] ?? 'rgb(255, 255, 255)',
            'backgroundColor' => $template['backgroundColor'] ?? 'rgb(187, 160, 122)',
            'labelColor' => $template['labelColor'] ?? 'rgb(255, 255, 255)',
            'storeCard' => [
                'headerFields' => array_map(function($f) use ($extended) {
                    if ($f['key'] === 'points') $f['value'] = (string)($extended->loyalty_points ?? 0);
                    return $f;
                }, $template['headerFields'] ?? []),
                'primaryFields' => array_map(function($f) use ($user) {
                    if ($f['key'] === 'customer_name') $f['value'] = $user->display_name;
                    return $f;
                }, $template['primaryFields'] ?? []),
                'secondaryFields' => array_map(function($f) use ($membership) {
                    if ($f['key'] === 'membership') {
                        $f['value'] = $membership ? ($membership->plan_name_ar ?: $membership->plan_name) : __('None', 'asmaa-salon');
                    }
                    return $f;
                }, $template['secondaryFields'] ?? []),
                'backFields' => $template['backFields'] ?? [],
                'barcode' => [
                    'message' => $qr_data['json'],
                    'format' => 'PKBarcodeFormatQR',
                    'messageEncoding' => 'iso-8859-1',
                ],
            ],
            'webServiceURL' => rest_url('asmaa-salon/v1/apple-wallet/'),
            'authenticationToken' => $auth_token,
        ];
        
        // Save to database
        $wpdb->insert($table, [
            'wc_customer_id' => $wc_customer_id,
            'pass_type' => self::PASS_TYPE_LOYALTY,
            'pass_type_identifier' => $pass_type_id,
            'serial_number' => $serial_number,
            'authentication_token' => $auth_token,
            'pass_data' => json_encode($pass_data),
            'qr_code_data' => $qr_data['json'],
            'qr_code_url' => QR_Code_Generator::generate_image_url($qr_data['json']),
            'last_updated' => current_time('mysql'),
        ]);
        
        // Generate and sign .pkpass file
        $pkpass_path = self::generate_signed_pkpass($pass_data, $serial_number, $wc_customer_id);
        
        // Generate pass file URL
        $pass_url = rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number);
        
        return [
            'success' => true,
            'pass_id' => $wpdb->insert_id,
            'serial_number' => $serial_number,
            'pass_url' => $pass_url,
            'pkpass_path' => $pkpass_path,
            'pass_data' => $pass_data,
        ];
    }
    
    /**
     * Update Apple Wallet pass for customer (legacy - updates loyalty pass)
     * 
     * @param int $wc_customer_id WooCommerce customer ID
     * @return array Updated pass data
     */
    public static function update_pass(int $wc_customer_id): array
    {
        return self::update_loyalty_pass($wc_customer_id);
    }
    
    /**
     * Update Apple Wallet pass by type
     * 
     * @param int $wc_customer_id WooCommerce customer ID
     * @param string $pass_type Pass type
     * @return array Updated pass data
     */
    public static function update_pass_by_type(int $wc_customer_id, string $pass_type): array
    {
        switch ($pass_type) {
            case self::PASS_TYPE_LOYALTY:
                return self::update_loyalty_pass($wc_customer_id);
            case self::PASS_TYPE_MEMBERSHIP:
                return self::update_membership_pass($wc_customer_id);
            case self::PASS_TYPE_PROGRAMS:
                return self::update_programs_pass($wc_customer_id);
            case self::PASS_TYPE_COMMISSIONS:
                return self::update_commissions_pass($wc_customer_id);
            default:
                throw new \Exception(__('Invalid pass type', 'asmaa-salon'));
        }
    }
    
    /**
     * Update Apple Wallet loyalty pass for customer
     * 
     * @param int $wc_customer_id WooCommerce customer ID
     * @return array Updated pass data
     */
    public static function update_loyalty_pass(int $wc_customer_id): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $pass = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                $wc_customer_id,
                self::PASS_TYPE_LOYALTY
            )
        );
        
        if (!$pass) {
            // Create new pass if doesn't exist
            return self::create_loyalty_pass($wc_customer_id);
        }
        
        // Get updated customer data
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $wc_customer_id)
        );
        
        // Get updated membership
        $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, p.name AS plan_name, p.name_ar AS plan_name_ar
             FROM {$memberships_table} m
             LEFT JOIN {$plans_table} p ON p.id = m.membership_plan_id
             WHERE m.wc_customer_id = %d AND m.status = 'active'
             ORDER BY m.end_date DESC LIMIT 1",
            $wc_customer_id
        ));
        
        // Generate new QR code
        $qr_data = QR_Code_Generator::generate_for_customer($wc_customer_id);
        
        $pass_type_id = Apple_Wallet_Config::PASS_TYPE_ID;
        
        // Get template from database
        $template = get_option('asmaa_salon_apple_wallet_loyalty_template', Apple_Wallet_Config::get_default_loyalty_template());
        
        // Build pass data from template
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $pass->serial_number,
            'teamIdentifier' => Apple_Wallet_Config::TEAM_ID,
            'organizationName' => Apple_Wallet_Config::ORGANIZATION_NAME,
            'description' => __('Loyalty Card', 'asmaa-salon'),
            'logoText' => $template['logoText'] ?? get_bloginfo('name'),
            'foregroundColor' => $template['foregroundColor'] ?? 'rgb(255, 255, 255)',
            'backgroundColor' => $template['backgroundColor'] ?? 'rgb(187, 160, 122)',
            'labelColor' => $template['labelColor'] ?? 'rgb(255, 255, 255)',
            'storeCard' => [
                'headerFields' => array_map(function($f) use ($extended) {
                    if ($f['key'] === 'points') $f['value'] = (string)($extended->loyalty_points ?? 0);
                    return $f;
                }, $template['headerFields'] ?? []),
                'primaryFields' => array_map(function($f) use ($user) {
                    if ($f['key'] === 'customer_name') $f['value'] = $user->display_name;
                    return $f;
                }, $template['primaryFields'] ?? []),
                'secondaryFields' => array_map(function($f) use ($membership) {
                    if ($f['key'] === 'membership') {
                        $f['value'] = $membership ? ($membership->plan_name_ar ?: $membership->plan_name) : __('None', 'asmaa-salon');
                    }
                    return $f;
                }, $template['secondaryFields'] ?? []),
                'backFields' => $template['backFields'] ?? [],
                'barcode' => [
                    'message' => $qr_data['json'],
                    'format' => 'PKBarcodeFormatQR',
                    'messageEncoding' => 'iso-8859-1',
                ],
            ],
            'webServiceURL' => rest_url('asmaa-salon/v1/apple-wallet/'),
            'authenticationToken' => $pass->authentication_token,
        ];
        
        // Update database
        $wpdb->update($table, [
            'pass_data' => json_encode($pass_data),
            'qr_code_data' => $qr_data['json'],
            'qr_code_url' => QR_Code_Generator::generate_image_url($qr_data['json']),
            'last_updated' => current_time('mysql'),
        ], [
            'wc_customer_id' => $wc_customer_id,
            'pass_type' => self::PASS_TYPE_LOYALTY,
        ]);
        
        // Regenerate signed .pkpass file
        $pkpass_path = self::generate_signed_pkpass($pass_data, $pass->serial_number, $wc_customer_id);
        
        // Send push notification to update pass on devices
        try {
            Apple_Push_Notification_Service::send_push_notification($pass->serial_number);
        } catch (\Exception $e) {
            error_log('Apple Wallet: Push notification failed: ' . $e->getMessage());
        }
        
        return [
            'success' => true,
            'serial_number' => $pass->serial_number,
            'pass_data' => $pass_data,
            'pkpass_path' => $pkpass_path,
            'updated' => true,
        ];
    }
    
    /**
     * Create Apple Wallet membership pass for customer
     * 
     * @param int $wc_customer_id WooCommerce customer ID
     * @return array Pass data with pass URL
     */
    public static function create_membership_pass(int $wc_customer_id): array
    {
        global $wpdb;
        
        $user = get_user_by('ID', $wc_customer_id);
        if (!$user) {
            throw new \Exception(__('Customer not found', 'asmaa-salon'));
        }

        // Security check: only customers can have membership passes
        if (!in_array('customer', (array) $user->roles)) {
            throw new \Exception(__('Membership passes can only be created for customers', 'asmaa-salon'));
        }

        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        
        // Get active membership
        $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, p.name AS plan_name, p.name_ar AS plan_name_ar, p.discount_percentage, p.free_services_count, p.points_multiplier
             FROM {$memberships_table} m
             LEFT JOIN {$plans_table} p ON p.id = m.membership_plan_id
             WHERE m.wc_customer_id = %d AND m.status = 'active'
             ORDER BY m.end_date DESC LIMIT 1",
            $wc_customer_id
        ));
        
        if (!$membership) {
            throw new \Exception(__('No active membership found', 'asmaa-salon'));
        }
        
        $qr_data = QR_Code_Generator::generate_for_customer($wc_customer_id);
        
        $serial_number = 'MEMBERSHIP-' . $wc_customer_id . '-' . time();
        $auth_token = wp_generate_password(32, false);
        $pass_type_id = Apple_Wallet_Config::PASS_TYPE_ID;
        
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $serial_number,
            'teamIdentifier' => Apple_Wallet_Config::TEAM_ID,
            'organizationName' => get_bloginfo('name'),
            'description' => __('Membership Card', 'asmaa-salon'),
            'logoText' => __('Asmaa Salon', 'asmaa-salon'),
            'foregroundColor' => 'rgb(255, 255, 255)',
            'backgroundColor' => 'rgb(139, 69, 19)',
            'storeCard' => [
                'primaryFields' => [
                    [
                        'key' => 'plan',
                        'label' => __('Membership Plan', 'asmaa-salon'),
                        'value' => $membership->plan_name_ar ?: $membership->plan_name,
                    ],
                ],
                'secondaryFields' => [
                    [
                        'key' => 'discount',
                        'label' => __('Discount', 'asmaa-salon'),
                        'value' => ($membership->discount_percentage ?? 0) . '%',
                    ],
                    [
                        'key' => 'expires',
                        'label' => __('Expires', 'asmaa-salon'),
                        'value' => date_i18n(get_option('date_format'), strtotime($membership->end_date)),
                    ],
                ],
                'auxiliaryFields' => [
                    [
                        'key' => 'services',
                        'label' => __('Free Services', 'asmaa-salon'),
                        'value' => ($membership->services_used ?? 0) . '/' . ($membership->services_limit ?? $membership->free_services_count ?? 0),
                    ],
                    [
                        'key' => 'multiplier',
                        'label' => __('Points Multiplier', 'asmaa-salon'),
                        'value' => 'x' . ($membership->points_multiplier ?? 1),
                    ],
                ],
                'barcode' => [
                    'message' => $qr_data['json'],
                    'format' => 'PKBarcodeFormatQR',
                    'messageEncoding' => 'iso-8859-1',
                ],
            ],
            'webServiceURL' => rest_url('asmaa-salon/v1/apple-wallet/'),
            'authenticationToken' => $auth_token,
        ];
        
        $wpdb->insert($table, [
            'wc_customer_id' => $wc_customer_id,
            'pass_type' => self::PASS_TYPE_MEMBERSHIP,
            'pass_type_identifier' => $pass_type_id,
            'serial_number' => $serial_number,
            'authentication_token' => $auth_token,
            'pass_data' => json_encode($pass_data),
            'qr_code_data' => $qr_data['json'],
            'qr_code_url' => QR_Code_Generator::generate_image_url($qr_data['json']),
            'last_updated' => current_time('mysql'),
        ]);
        
        // Generate and sign .pkpass file
        $pkpass_path = self::generate_signed_pkpass($pass_data, $serial_number, $wc_customer_id);
        
        $pass_url = rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number);
        
        return [
            'success' => true,
            'pass_id' => $wpdb->insert_id,
            'serial_number' => $serial_number,
            'pass_url' => $pass_url,
            'pkpass_path' => $pkpass_path,
            'pass_data' => $pass_data,
        ];
    }
    
    /**
     * Update Apple Wallet membership pass
     */
    public static function update_membership_pass(int $wc_customer_id): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $pass = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                $wc_customer_id,
                self::PASS_TYPE_MEMBERSHIP
            )
        );
        
        if (!$pass) {
            return self::create_membership_pass($wc_customer_id);
        }
        
        $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, p.name AS plan_name, p.name_ar AS plan_name_ar, p.discount_percentage, p.free_services_count, p.points_multiplier
             FROM {$memberships_table} m
             LEFT JOIN {$plans_table} p ON p.id = m.membership_plan_id
             WHERE m.wc_customer_id = %d AND m.status = 'active'
             ORDER BY m.end_date DESC LIMIT 1",
            $wc_customer_id
        ));
        
        $qr_data = QR_Code_Generator::generate_for_customer($wc_customer_id);
        $pass_data = json_decode($pass->pass_data, true);
        
        if ($membership) {
            $pass_data['storeCard']['primaryFields'][0]['value'] = $membership->plan_name_ar ?: $membership->plan_name;
            $pass_data['storeCard']['secondaryFields'][0]['value'] = ($membership->discount_percentage ?? 0) . '%';
            $pass_data['storeCard']['secondaryFields'][1]['value'] = date_i18n(get_option('date_format'), strtotime($membership->end_date));
            $pass_data['storeCard']['auxiliaryFields'][0]['value'] = ($membership->services_used ?? 0) . '/' . ($membership->services_limit ?? $membership->free_services_count ?? 0);
            $pass_data['storeCard']['auxiliaryFields'][1]['value'] = 'x' . ($membership->points_multiplier ?? 1);
        }
        $pass_data['storeCard']['barcode']['message'] = $qr_data['json'];
        
        $wpdb->update($table, [
            'pass_data' => json_encode($pass_data),
            'qr_code_data' => $qr_data['json'],
            'qr_code_url' => QR_Code_Generator::generate_image_url($qr_data['json']),
            'last_updated' => current_time('mysql'),
        ], [
            'wc_customer_id' => $wc_customer_id,
            'pass_type' => self::PASS_TYPE_MEMBERSHIP,
        ]);
        
        // Regenerate signed .pkpass file
        $pkpass_path = self::generate_signed_pkpass($pass_data, $pass->serial_number, $wc_customer_id);
        
        // Send push notification
        try {
            Apple_Push_Notification_Service::send_push_notification($pass->serial_number);
        } catch (\Exception $e) {
            error_log('Apple Wallet: Push notification failed: ' . $e->getMessage());
        }
        
        return [
            'success' => true,
            'serial_number' => $pass->serial_number,
            'pass_data' => $pass_data,
            'pkpass_path' => $pkpass_path,
            'updated' => true,
        ];
    }
    
    /**
     * Create Apple Wallet programs pass (combines loyalty + commissions info)
     */
    public static function create_programs_pass(int $wc_customer_id): array
    {
        global $wpdb;
        
        $user = get_user_by('ID', $wc_customer_id);
        if (!$user) {
            throw new \Exception(__('Customer not found', 'asmaa-salon'));
        }

        // Security check: only customers can have programs passes
        if (!in_array('customer', (array) $user->roles)) {
            throw new \Exception(__('Programs passes can only be created for customers', 'asmaa-salon'));
        }

        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        
        // Get loyalty points
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $wc_customer_id)
        );
        
        // Get programs settings
        $programs_settings = get_option('asmaa_salon_programs_settings', []);
        $loyalty_enabled = $programs_settings['loyalty']['enabled'] ?? true;
        $commissions_enabled = $programs_settings['commissions']['enabled'] ?? true;
        
        $qr_data = QR_Code_Generator::generate_for_customer($wc_customer_id);
        
        $serial_number = 'PROGRAMS-' . $wc_customer_id . '-' . time();
        $auth_token = wp_generate_password(32, false);
        $pass_type_id = Apple_Wallet_Config::PASS_TYPE_ID;
        
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $serial_number,
            'teamIdentifier' => Apple_Wallet_Config::TEAM_ID,
            'organizationName' => get_bloginfo('name'),
            'description' => __('Programs Card', 'asmaa-salon'),
            'logoText' => __('Asmaa Salon', 'asmaa-salon'),
            'foregroundColor' => 'rgb(255, 255, 255)',
            'backgroundColor' => 'rgb(75, 0, 130)',
            'storeCard' => [
                'primaryFields' => [
                    [
                        'key' => 'points',
                        'label' => __('Loyalty Points', 'asmaa-salon'),
                        'value' => (string) ($extended->loyalty_points ?? 0),
                    ],
                ],
                'secondaryFields' => [
                    [
                        'key' => 'loyalty',
                        'label' => __('Loyalty Program', 'asmaa-salon'),
                        'value' => $loyalty_enabled ? __('Active', 'asmaa-salon') : __('Inactive', 'asmaa-salon'),
                    ],
                    [
                        'key' => 'commissions',
                        'label' => __('Commissions', 'asmaa-salon'),
                        'value' => $commissions_enabled ? __('Active', 'asmaa-salon') : __('Inactive', 'asmaa-salon'),
                    ],
                ],
                'auxiliaryFields' => [
                    [
                        'key' => 'visits',
                        'label' => __('Total Visits', 'asmaa-salon'),
                        'value' => (string) ($extended->total_visits ?? 0),
                    ],
                ],
                'barcode' => [
                    'message' => $qr_data['json'],
                    'format' => 'PKBarcodeFormatQR',
                    'messageEncoding' => 'iso-8859-1',
                ],
            ],
            'webServiceURL' => rest_url('asmaa-salon/v1/apple-wallet/'),
            'authenticationToken' => $auth_token,
        ];
        
        $wpdb->insert($table, [
            'wc_customer_id' => $wc_customer_id,
            'pass_type' => self::PASS_TYPE_PROGRAMS,
            'pass_type_identifier' => $pass_type_id,
            'serial_number' => $serial_number,
            'authentication_token' => $auth_token,
            'pass_data' => json_encode($pass_data),
            'qr_code_data' => $qr_data['json'],
            'qr_code_url' => QR_Code_Generator::generate_image_url($qr_data['json']),
            'last_updated' => current_time('mysql'),
        ]);
        
        // Generate and sign .pkpass file
        $pkpass_path = self::generate_signed_pkpass($pass_data, $serial_number, $wc_customer_id);
        
        $pass_url = rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number);
        
        return [
            'success' => true,
            'pass_id' => $wpdb->insert_id,
            'serial_number' => $serial_number,
            'pass_url' => $pass_url,
            'pkpass_path' => $pkpass_path,
            'pass_data' => $pass_data,
        ];
    }
    
    /**
     * Update Apple Wallet programs pass
     */
    public static function update_programs_pass(int $wc_customer_id): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $pass = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                $wc_customer_id,
                self::PASS_TYPE_PROGRAMS
            )
        );
        
        if (!$pass) {
            return self::create_programs_pass($wc_customer_id);
        }
        
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$extended_table} WHERE wc_customer_id = %d", $wc_customer_id)
        );
        
        $programs_settings = get_option('asmaa_salon_programs_settings', []);
        $loyalty_enabled = $programs_settings['loyalty']['enabled'] ?? true;
        $commissions_enabled = $programs_settings['commissions']['enabled'] ?? true;
        
        $qr_data = QR_Code_Generator::generate_for_customer($wc_customer_id);
        $pass_data = json_decode($pass->pass_data, true);
        
        $pass_data['storeCard']['primaryFields'][0]['value'] = (string) ($extended->loyalty_points ?? 0);
        $pass_data['storeCard']['secondaryFields'][0]['value'] = $loyalty_enabled ? __('Active', 'asmaa-salon') : __('Inactive', 'asmaa-salon');
        $pass_data['storeCard']['secondaryFields'][1]['value'] = $commissions_enabled ? __('Active', 'asmaa-salon') : __('Inactive', 'asmaa-salon');
        $pass_data['storeCard']['auxiliaryFields'][0]['value'] = (string) ($extended->total_visits ?? 0);
        $pass_data['storeCard']['barcode']['message'] = $qr_data['json'];
        
        $wpdb->update($table, [
            'pass_data' => json_encode($pass_data),
            'qr_code_data' => $qr_data['json'],
            'qr_code_url' => QR_Code_Generator::generate_image_url($qr_data['json']),
            'last_updated' => current_time('mysql'),
        ], [
            'wc_customer_id' => $wc_customer_id,
            'pass_type' => self::PASS_TYPE_PROGRAMS,
        ]);
        
        // Regenerate signed .pkpass file
        $pkpass_path = self::generate_signed_pkpass($pass_data, $pass->serial_number, $wc_customer_id);
        
        // Send push notification
        try {
            Apple_Push_Notification_Service::send_push_notification($pass->serial_number);
        } catch (\Exception $e) {
            error_log('Apple Wallet: Push notification failed: ' . $e->getMessage());
        }
        
        return [
            'success' => true,
            'serial_number' => $pass->serial_number,
            'pass_data' => $pass_data,
            'pkpass_path' => $pkpass_path,
            'updated' => true,
        ];
    }
    
    /**
     * Create Apple Wallet commissions pass for staff member
     */
    public static function create_commissions_pass(int $wp_user_id): array
    {
        global $wpdb;
        
        $user = get_user_by('ID', $wp_user_id);
        if (!$user) {
            throw new \Exception(__('Staff member not found', 'asmaa-salon'));
        }

        // Security check: ensure user has staff/admin role
        $is_staff = false;
        $staff_roles = ['administrator', 'editor', 'author', 'huda_manager', 'huda_receptionist', 'huda_tailor', 'huda_worker'];
        foreach ($staff_roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                $is_staff = true;
                break;
            }
        }
        if (!$is_staff) {
            throw new \Exception(__('Commissions passes can only be created for staff members', 'asmaa-salon'));
        }

        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        
        // Get commissions stats
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_commissions,
                SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END) as approved_amount,
                SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount
             FROM {$commissions_table}
             WHERE wp_user_id = %d",
            $wp_user_id
        ));
        
        $qr_data = QR_Code_Generator::generate_for_customer($wp_user_id);
        
        $serial_number = 'COMMISSIONS-' . $wp_user_id . '-' . time();
        $auth_token = wp_generate_password(32, false);
        $pass_type_id = Apple_Wallet_Config::PASS_TYPE_ID;
        
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $serial_number,
            'teamIdentifier' => Apple_Wallet_Config::TEAM_ID,
            'organizationName' => get_bloginfo('name'),
            'description' => __('Commissions Card', 'asmaa-salon'),
            'logoText' => __('Asmaa Salon', 'asmaa-salon'),
            'foregroundColor' => 'rgb(255, 255, 255)',
            'backgroundColor' => 'rgb(0, 100, 0)',
            'storeCard' => [
                'primaryFields' => [
                    [
                        'key' => 'pending',
                        'label' => __('Pending Commissions', 'asmaa-salon'),
                        'value' => number_format($stats->pending_amount ?? 0, 3) . ' KWD',
                    ],
                ],
                'secondaryFields' => [
                    [
                        'key' => 'approved',
                        'label' => __('Approved', 'asmaa-salon'),
                        'value' => number_format($stats->approved_amount ?? 0, 3) . ' KWD',
                    ],
                    [
                        'key' => 'paid',
                        'label' => __('Paid', 'asmaa-salon'),
                        'value' => number_format($stats->paid_amount ?? 0, 3) . ' KWD',
                    ],
                ],
                'auxiliaryFields' => [
                    [
                        'key' => 'total',
                        'label' => __('Total Commissions', 'asmaa-salon'),
                        'value' => (string) ($stats->total_commissions ?? 0),
                    ],
                ],
                'barcode' => [
                    'message' => $qr_data['json'],
                    'format' => 'PKBarcodeFormatQR',
                    'messageEncoding' => 'iso-8859-1',
                ],
            ],
            'webServiceURL' => rest_url('asmaa-salon/v1/apple-wallet/'),
            'authenticationToken' => $auth_token,
        ];
        
        $wpdb->insert($table, [
            'wc_customer_id' => $wp_user_id,
            'pass_type' => self::PASS_TYPE_COMMISSIONS,
            'pass_type_identifier' => $pass_type_id,
            'serial_number' => $serial_number,
            'authentication_token' => $auth_token,
            'pass_data' => json_encode($pass_data),
            'qr_code_data' => $qr_data['json'],
            'qr_code_url' => QR_Code_Generator::generate_image_url($qr_data['json']),
            'last_updated' => current_time('mysql'),
        ]);
        
        // Generate and sign .pkpass file
        $pkpass_path = self::generate_signed_pkpass($pass_data, $serial_number, $wp_user_id);
        
        $pass_url = rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number);
        
        return [
            'success' => true,
            'pass_id' => $wpdb->insert_id,
            'serial_number' => $serial_number,
            'pass_url' => $pass_url,
            'pkpass_path' => $pkpass_path,
            'pass_data' => $pass_data,
        ];
    }
    
    /**
     * Update Apple Wallet commissions pass
     */
    public static function update_commissions_pass(int $wp_user_id): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $pass = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                $wp_user_id,
                self::PASS_TYPE_COMMISSIONS
            )
        );
        
        if (!$pass) {
            return self::create_commissions_pass($wp_user_id);
        }
        
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_commissions,
                SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END) as approved_amount,
                SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount
             FROM {$commissions_table}
             WHERE wp_user_id = %d",
            $wp_user_id
        ));
        
        $qr_data = QR_Code_Generator::generate_for_customer($wp_user_id);
        $pass_data = json_decode($pass->pass_data, true);
        
        $pass_data['storeCard']['primaryFields'][0]['value'] = number_format($stats->pending_amount ?? 0, 3) . ' KWD';
        $pass_data['storeCard']['secondaryFields'][0]['value'] = number_format($stats->approved_amount ?? 0, 3) . ' KWD';
        $pass_data['storeCard']['secondaryFields'][1]['value'] = number_format($stats->paid_amount ?? 0, 3) . ' KWD';
        $pass_data['storeCard']['auxiliaryFields'][0]['value'] = (string) ($stats->total_commissions ?? 0);
        $pass_data['storeCard']['barcode']['message'] = $qr_data['json'];
        
        $wpdb->update($table, [
            'pass_data' => json_encode($pass_data),
            'qr_code_data' => $qr_data['json'],
            'qr_code_url' => QR_Code_Generator::generate_image_url($qr_data['json']),
            'last_updated' => current_time('mysql'),
        ], [
            'wc_customer_id' => $wp_user_id,
            'pass_type' => self::PASS_TYPE_COMMISSIONS,
        ]);
        
        // Regenerate signed .pkpass file
        $pkpass_path = self::generate_signed_pkpass($pass_data, $pass->serial_number, $wp_user_id);
        
        // Send push notification
        try {
            Apple_Push_Notification_Service::send_push_notification($pass->serial_number);
        } catch (\Exception $e) {
            error_log('Apple Wallet: Push notification failed: ' . $e->getMessage());
        }
        
        return [
            'success' => true,
            'serial_number' => $pass->serial_number,
            'pass_data' => $pass_data,
            'pkpass_path' => $pkpass_path,
            'updated' => true,
        ];
    }
    
    /**
     * Get pass data for customer
     * 
     * @param int $wc_customer_id WooCommerce customer ID
     * @param string|null $pass_type Optional pass type filter
     * @return array|null Pass data or null if not found
     */
    public static function get_pass_data(int $wc_customer_id, ?string $pass_type = null): ?array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        
        if ($pass_type) {
            $pass = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                    $wc_customer_id,
                    $pass_type
                )
            );
        } else {
        $pass = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE wc_customer_id = %d ORDER BY created_at DESC LIMIT 1", $wc_customer_id)
        );
        }
        
        if (!$pass) {
            return null;
        }
        
        return [
            'id' => $pass->id,
            'pass_type' => $pass->pass_type ?? self::PASS_TYPE_LOYALTY,
            'serial_number' => $pass->serial_number,
            'pass_data' => json_decode($pass->pass_data, true),
            'qr_code_url' => $pass->qr_code_url,
            'last_updated' => $pass->last_updated,
        ];
    }
    
    /**
     * Get all passes for customer/staff
     */
    public static function get_all_passes(int $wc_customer_id): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $passes = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE wc_customer_id = %d ORDER BY created_at DESC", $wc_customer_id)
        );
        
        $result = [];
        foreach ($passes as $pass) {
            $result[] = [
                'id' => $pass->id,
                'pass_type' => $pass->pass_type ?? self::PASS_TYPE_LOYALTY,
                'serial_number' => $pass->serial_number,
                'pass_data' => json_decode($pass->pass_data, true),
                'qr_code_url' => $pass->qr_code_url,
                'last_updated' => $pass->last_updated,
            ];
        }
        
        return $result;
    }
    
    /**
     * Generate signed .pkpass file
     * 
     * @param array $pass_data Pass JSON data
     * @param string $serial_number Pass serial number
     * @param int $wc_customer_id Customer ID
     * @return string Path to generated .pkpass file
     */
    public static function generate_signed_pkpass(array $pass_data, string $serial_number, int $wc_customer_id): string
    {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/asmaa-salon';
        $passes_dir = $base_dir . '/passes';
        $certs_dir = $base_dir . '/certs';
        
        // Ensure directories exist
        if (!file_exists($passes_dir)) {
            wp_mkdir_p($passes_dir);
        }
        if (!file_exists($certs_dir)) {
            wp_mkdir_p($certs_dir);
        }
        
        // Create temporary directory for pass files
        $temp_dir = $passes_dir . '/temp-' . $serial_number;
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        // Write pass.json
        file_put_contents($temp_dir . '/pass.json', json_encode($pass_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Create placeholder images if they don't exist
        self::create_pass_images($temp_dir);
        
        // Create manifest
        $manifest = self::create_manifest($temp_dir);
        
        // Create signature
        self::create_signature($temp_dir, $manifest);
        
        // Create .pkpass ZIP file
        $pkpass_path = $passes_dir . '/' . $serial_number . '.pkpass';
        self::create_pkpass_zip($temp_dir, $pkpass_path);
        
        // Clean up temporary directory
        self::delete_directory($temp_dir);
        
        return $pkpass_path;
    }
    
    /**
     * Create placeholder images for pass
     */
    private static function create_pass_images(string $pass_dir): void
    {
        // Create a simple transparent PNG as placeholder
        $logo_content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        
        // Standard logo sizes for Apple Wallet
        file_put_contents($pass_dir . '/logo.png', $logo_content);
        file_put_contents($pass_dir . '/logo@2x.png', $logo_content);
        file_put_contents($pass_dir . '/logo@3x.png', $logo_content);
        
        // Optional: Add icon images
        file_put_contents($pass_dir . '/icon.png', $logo_content);
        file_put_contents($pass_dir . '/icon@2x.png', $logo_content);
        file_put_contents($pass_dir . '/icon@3x.png', $logo_content);
    }
    
    /**
     * Create manifest.json with SHA1 hashes
     */
    private static function create_manifest(string $pass_dir): array
    {
        $manifest = [];
        $files = glob($pass_dir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'manifest.json' && basename($file) !== 'signature') {
                $manifest[basename($file)] = sha1_file($file);
            }
        }
        
        file_put_contents($pass_dir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        return $manifest;
    }
    
    /**
     * Create PKCS7 signature
     */
    private static function create_signature(string $pass_dir, array $manifest): void
    {
        $manifest_json = $pass_dir . '/manifest.json';
        $signature_path = $pass_dir . '/signature';
        
        // Get certificate paths from config
        $cert_path = Apple_Wallet_Config::get_certificate_path();
        $cert_password = Apple_Wallet_Config::CERTIFICATE_PASSWORD;
        $wwdr_cert_path = Apple_Wallet_Config::get_wwdr_certificate_path();
        
        // Verify certificate exists
        if (!file_exists($cert_path)) {
            throw new \Exception('Apple Wallet certificate not found at: ' . $cert_path);
        }
        
        // Verify WWDR certificate exists
        if (!file_exists($wwdr_cert_path)) {
            throw new \Exception('Apple WWDR certificate not found at: ' . $wwdr_cert_path);
        }
        
        try {
            // Read certificate
            $cert_data = file_get_contents($cert_path);
            $pkcs12 = [];
            
            if (!openssl_pkcs12_read($cert_data, $pkcs12, $cert_password)) {
                $error = openssl_error_string();
                throw new \Exception('Failed to read certificate: ' . ($error ?: 'Invalid password or certificate format'));
            }
            
            // Read WWDR certificate
            $wwdr_cert = file_get_contents($wwdr_cert_path);
            
            // Convert WWDR from DER to PEM if needed
            if (strpos($wwdr_cert, '-----BEGIN CERTIFICATE-----') === false) {
                // It's in DER format, convert to PEM
                $wwdr_pem = "-----BEGIN CERTIFICATE-----\n";
                $wwdr_pem .= chunk_split(base64_encode($wwdr_cert), 64, "\n");
                $wwdr_pem .= "-----END CERTIFICATE-----\n";
                $wwdr_cert = $wwdr_pem;
            }
            
            // Create certificate chain (pass cert first, then WWDR)
            $cert_chain = [$pkcs12['cert'], $wwdr_cert];
            
            // Create signature using command line (more reliable)
            $signature_temp = $signature_path . '.tmp';
            $cert_pem_file = $pass_dir . '/cert.pem';
            $key_pem_file = $pass_dir . '/key.pem';
            $wwdr_pem_file = $pass_dir . '/wwdr.pem';
            
            // Write certificate and key to temporary files
            file_put_contents($cert_pem_file, $pkcs12['cert']);
            file_put_contents($key_pem_file, $pkcs12['pkey']);
            file_put_contents($wwdr_pem_file, $wwdr_cert);
            
            // Create certificate chain file
            $chain_file = $pass_dir . '/chain.pem';
            file_put_contents($chain_file, $pkcs12['cert'] . "\n" . $wwdr_cert);
            
            // Use openssl command line for signing (more reliable)
            $openssl_cmd = sprintf(
                'openssl smime -binary -sign -certfile %s -signer %s -inkey %s -in %s -out %s -outform DER -nodetach',
                escapeshellarg($wwdr_pem_file),
                escapeshellarg($cert_pem_file),
                escapeshellarg($key_pem_file),
                escapeshellarg($manifest_json),
                escapeshellarg($signature_temp)
            );
            
            $output = [];
            $return_var = 0;
            exec($openssl_cmd . ' 2>&1', $output, $return_var);
            
            if ($return_var === 0 && file_exists($signature_temp) && filesize($signature_temp) > 0) {
                // Signature created successfully
                copy($signature_temp, $signature_path);
                unlink($signature_temp);
                
                // Clean up temporary files
                @unlink($cert_pem_file);
                @unlink($key_pem_file);
                @unlink($wwdr_pem_file);
                @unlink($chain_file);
                
                return;
            } else {
                // Fallback to PHP openssl_pkcs7_sign
                $signature_temp2 = $signature_path . '.tmp2';
                $result = openssl_pkcs7_sign(
                    $manifest_json,
                    $signature_temp2,
                    $pkcs12['cert'],
                    $pkcs12['pkey'],
                    $cert_chain,
                    PKCS7_BINARY | PKCS7_DETACHED | PKCS7_NOATTR
                );
                
                if ($result && file_exists($signature_temp2)) {
                    // Extract DER signature from PKCS7
                    $pkcs7_data = file_get_contents($signature_temp2);
                    
                    // Try to extract DER signature
                    if (preg_match('/Content-Disposition: attachment; filename="smime\.p7s"\s*(.*?)(?=------)/s', $pkcs7_data, $matches)) {
                        $signature_der = base64_decode(trim($matches[1]));
                    } else {
                        // Alternative: decode entire PKCS7
                        $signature_der = base64_decode(preg_replace('/[^A-Za-z0-9+\/=]/', '', $pkcs7_data));
                    }
                    
                    if ($signature_der && strlen($signature_der) > 100) {
                        file_put_contents($signature_path, $signature_der);
                        unlink($signature_temp2);
                        
                        // Clean up
                        @unlink($cert_pem_file);
                        @unlink($key_pem_file);
                        @unlink($wwdr_pem_file);
                        @unlink($chain_file);
                        
                        return;
                    }
                }
                
                // Clean up on failure
                @unlink($signature_temp);
                @unlink($signature_temp2);
                @unlink($cert_pem_file);
                @unlink($key_pem_file);
                @unlink($wwdr_pem_file);
                @unlink($chain_file);
                
                throw new \Exception('Failed to create signature. OpenSSL error: ' . implode("\n", $output));
            }
        } catch (\Exception $e) {
            error_log('Apple Wallet signature error: ' . $e->getMessage());
            throw $e; // Re-throw to prevent creating invalid pass
        }
    }
    
    /**
     * Create .pkpass ZIP file
     */
    private static function create_pkpass_zip(string $pass_dir, string $pkpass_path): void
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception(__('ZipArchive class is required for Apple Wallet pass generation', 'asmaa-salon'));
        }
        
        // Verify required files exist
        $required_files = ['pass.json', 'manifest.json', 'signature'];
        foreach ($required_files as $file) {
            $file_path = $pass_dir . '/' . $file;
            if (!file_exists($file_path)) {
                throw new \Exception(__('Required file missing: ', 'asmaa-salon') . $file);
            }
        }
        
        // Verify signature is valid (not dummy and has reasonable size)
        $signature_path = $pass_dir . '/signature';
        $signature_size = filesize($signature_path);
        if ($signature_size < 100) {
            throw new \Exception(__('Invalid signature file. Size: ', 'asmaa-salon') . $signature_size . ' bytes. Certificate signing may have failed.');
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($pkpass_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception(__('Failed to create pkpass file', 'asmaa-salon'));
        }
        
        // Add files in correct order (important for Apple Wallet)
        $files_to_add = ['pass.json', 'manifest.json', 'signature'];
        
        // Add image files if they exist
        $image_files = ['logo.png', 'logo@2x.png', 'logo@3x.png', 'icon.png', 'icon@2x.png', 'icon@3x.png'];
        foreach ($image_files as $img) {
            $img_path = $pass_dir . '/' . $img;
            if (file_exists($img_path)) {
                $files_to_add[] = $img;
            }
        }
        
        // Add files to ZIP
        foreach ($files_to_add as $file) {
            $file_path = $pass_dir . '/' . $file;
            if (file_exists($file_path)) {
                $zip->addFile($file_path, $file);
            }
        }
        
        $zip->close();
        
        // Verify ZIP was created and has reasonable size (> 1KB)
        if (!file_exists($pkpass_path)) {
            throw new \Exception(__('Generated pkpass file was not created', 'asmaa-salon'));
        }
        
        $pkpass_size = filesize($pkpass_path);
        if ($pkpass_size < 1024) {
            throw new \Exception(__('Generated pkpass file is too small. Size: ', 'asmaa-salon') . $pkpass_size . ' bytes. This usually indicates a signature problem.');
        }
    }
    
    /**
     * Delete directory recursively
     */
    private static function delete_directory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        
        if (!is_dir($dir)) {
            unlink($dir);
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $file_path = $dir . '/' . $file;
            if (is_dir($file_path)) {
                self::delete_directory($file_path);
            } else {
                unlink($file_path);
            }
        }
        
        rmdir($dir);
    }
}


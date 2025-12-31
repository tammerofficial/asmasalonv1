<?php

namespace AsmaaSalon\Services;

use AsmaaSalon\Helpers\QR_Code_Generator;

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
        
        // Check if pass already exists
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                $wc_customer_id,
                self::PASS_TYPE_LOYALTY
            )
        );
        
        if ($existing) {
            // Update existing pass
            return self::update_loyalty_pass($wc_customer_id);
        }
        
        // Get customer data
        $user = get_user_by('ID', $wc_customer_id);
        if (!$user) {
            throw new \Exception(__('Customer not found', 'asmaa-salon'));
        }
        
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
        $pass_type_id = 'pass.com.asmaasalon.loyalty';
        
        // Build pass data
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $serial_number,
            'teamIdentifier' => get_option('asmaa_salon_apple_team_id', 'TEAM_ID'),
            'organizationName' => get_bloginfo('name'),
            'description' => __('Loyalty Card', 'asmaa-salon'),
            'logoText' => __('Asmaa Salon', 'asmaa-salon'),
            'foregroundColor' => 'rgb(255, 255, 255)',
            'backgroundColor' => 'rgb(187, 160, 122)',
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
                        'key' => 'membership',
                        'label' => __('Membership', 'asmaa-salon'),
                        'value' => $membership ? ($membership->plan_name_ar ?: $membership->plan_name) : __('None', 'asmaa-salon'),
                    ],
                    [
                        'key' => 'expires',
                        'label' => __('Expires', 'asmaa-salon'),
                        'value' => $membership ? $membership->end_date : __('N/A', 'asmaa-salon'),
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
        
        // Generate pass file URL (would need to sign and create .pkpass file)
        $pass_url = rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number);
        
        return [
            'success' => true,
            'pass_id' => $wpdb->insert_id,
            'serial_number' => $serial_number,
            'pass_url' => $pass_url,
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
        
        // Decode existing pass data
        $pass_data = json_decode($pass->pass_data, true);
        
        // Update pass data
        $pass_data['storeCard']['primaryFields'][0]['value'] = (string) ($extended->loyalty_points ?? 0);
        $pass_data['storeCard']['secondaryFields'][0]['value'] = $membership ? ($membership->plan_name_ar ?: $membership->plan_name) : __('None', 'asmaa-salon');
        $pass_data['storeCard']['secondaryFields'][1]['value'] = $membership ? $membership->end_date : __('N/A', 'asmaa-salon');
        $pass_data['storeCard']['auxiliaryFields'][0]['value'] = (string) ($extended->total_visits ?? 0);
        $pass_data['storeCard']['barcode']['message'] = $qr_data['json'];
        
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
        
        return [
            'success' => true,
            'serial_number' => $pass->serial_number,
            'pass_data' => $pass_data,
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
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                $wc_customer_id,
                self::PASS_TYPE_MEMBERSHIP
            )
        );
        
        if ($existing) {
            return self::update_membership_pass($wc_customer_id);
        }
        
        $user = get_user_by('ID', $wc_customer_id);
        if (!$user) {
            throw new \Exception(__('Customer not found', 'asmaa-salon'));
        }
        
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
        $pass_type_id = 'pass.com.asmaasalon.membership';
        
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $serial_number,
            'teamIdentifier' => get_option('asmaa_salon_apple_team_id', 'TEAM_ID'),
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
        
        $pass_url = rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number);
        
        return [
            'success' => true,
            'pass_id' => $wpdb->insert_id,
            'serial_number' => $serial_number,
            'pass_url' => $pass_url,
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
        
        return [
            'success' => true,
            'serial_number' => $pass->serial_number,
            'pass_data' => $pass_data,
            'updated' => true,
        ];
    }
    
    /**
     * Create Apple Wallet programs pass (combines loyalty + commissions info)
     */
    public static function create_programs_pass(int $wc_customer_id): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                $wc_customer_id,
                self::PASS_TYPE_PROGRAMS
            )
        );
        
        if ($existing) {
            return self::update_programs_pass($wc_customer_id);
        }
        
        $user = get_user_by('ID', $wc_customer_id);
        if (!$user) {
            throw new \Exception(__('Customer not found', 'asmaa-salon'));
        }
        
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
        $pass_type_id = 'pass.com.asmaasalon.programs';
        
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $serial_number,
            'teamIdentifier' => get_option('asmaa_salon_apple_team_id', 'TEAM_ID'),
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
        
        $pass_url = rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number);
        
        return [
            'success' => true,
            'pass_id' => $wpdb->insert_id,
            'serial_number' => $serial_number,
            'pass_url' => $pass_url,
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
        
        return [
            'success' => true,
            'serial_number' => $pass->serial_number,
            'pass_data' => $pass_data,
            'updated' => true,
        ];
    }
    
    /**
     * Create Apple Wallet commissions pass for staff member
     */
    public static function create_commissions_pass(int $wp_user_id): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE wc_customer_id = %d AND pass_type = %s",
                $wp_user_id,
                self::PASS_TYPE_COMMISSIONS
            )
        );
        
        if ($existing) {
            return self::update_commissions_pass($wp_user_id);
        }
        
        $user = get_user_by('ID', $wp_user_id);
        if (!$user) {
            throw new \Exception(__('Staff member not found', 'asmaa-salon'));
        }
        
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
        $pass_type_id = 'pass.com.asmaasalon.commissions';
        
        $pass_data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $pass_type_id,
            'serialNumber' => $serial_number,
            'teamIdentifier' => get_option('asmaa_salon_apple_team_id', 'TEAM_ID'),
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
        
        $pass_url = rest_url('asmaa-salon/v1/apple-wallet/pass/' . $serial_number);
        
        return [
            'success' => true,
            'pass_id' => $wpdb->insert_id,
            'serial_number' => $serial_number,
            'pass_url' => $pass_url,
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
        
        return [
            'success' => true,
            'serial_number' => $pass->serial_number,
            'pass_data' => $pass_data,
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
}


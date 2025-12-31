<?php

namespace AsmaaSalon;

use AsmaaSalon\Database\Schema;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    protected static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    protected function init_hooks(): void
    {
        add_action('plugins_loaded', [$this, 'init'], 0);
        
        // ✅ WP-Cron: Daily membership expiry check
        add_action('asmaa_salon_check_membership_expiry', ['\AsmaaSalon\Services\MembershipExpiryChecker', 'check_and_notify']);
    }

    public function init(): void
    {
        // Text domain
        load_plugin_textdomain(
            'asmaa-salon',
            false,
            dirname(ASMAA_SALON_PLUGIN_BASENAME) . '/languages'
        );

        // Ensure DB schema is up-to-date (safe, runs only when version changes)
        $this->maybe_upgrade_database();

        // Run WooCommerce migration if needed
        $this->maybe_migrate_woocommerce_fields();
        
        // Run extended data tables migration if needed
        $this->maybe_migrate_extended_data_tables();
        
        // Run Apple Wallet table migration if needed
        $this->maybe_migrate_apple_wallet_table();
        
        // Optimize database indexes if needed
        $this->maybe_optimize_database_indexes();
        
        // Update related tables columns if needed
        $this->maybe_update_related_tables_columns();
        
        // Drop legacy tables if needed
        $this->maybe_drop_legacy_tables();
        
        // Add sync and extended tables if needed
        $this->maybe_add_sync_and_extended_tables();

        // Admin menu (redirects to standalone page)
        if (is_admin()) {
            $this->init_admin();
        }

        // Standalone dashboard page
        $this->init_public();

        // REST API namespace
        add_action('rest_api_init', [$this, 'init_rest_api']);

        // WooCommerce Integration
        $this->init_woocommerce_integration();
    }

    protected function maybe_upgrade_database(): void
    {
        $current = get_option('asmaa_salon_db_version', '0.0.0');
        $target  = defined('ASMAA_SALON_VERSION') ? ASMAA_SALON_VERSION : '0.0.0';

        if (version_compare((string) $current, (string) $target, '>=')) {
            return;
        }

        // Run dbDelta to add new tables/columns (idempotent)
        if (class_exists(Schema::class)) {
            Schema::create_core_tables();
        }

        update_option('asmaa_salon_db_version', $target, false);
    }

    protected function maybe_migrate_woocommerce_fields(): void
    {
        $migrated = get_option('asmaa_salon_wc_fields_migrated', false);
        
        if (!$migrated && class_exists('\AsmaaSalon\Database\Migrations\Add_WooCommerce_Fields')) {
            if (\AsmaaSalon\Database\Migrations\Add_WooCommerce_Fields::needs_migration()) {
                \AsmaaSalon\Database\Migrations\Add_WooCommerce_Fields::migrate();
                update_option('asmaa_salon_wc_fields_migrated', true, false);
            }
        }
    }

    protected function maybe_migrate_extended_data_tables(): void
    {
        $migrated = get_option('asmaa_salon_extended_data_migrated', false);
        
        if (!$migrated && class_exists('\AsmaaSalon\Database\Migrations\Create_Extended_Data_Tables')) {
            if (\AsmaaSalon\Database\Migrations\Create_Extended_Data_Tables::needs_migration()) {
                \AsmaaSalon\Database\Migrations\Create_Extended_Data_Tables::migrate();
                update_option('asmaa_salon_extended_data_migrated', true, false);
            }
        }
    }

    protected function maybe_migrate_apple_wallet_table(): void
    {
        $migrated = get_option('asmaa_salon_apple_wallet_migrated', false);
        
        if (!$migrated && class_exists('\AsmaaSalon\Database\Migrations\Create_Apple_Wallet_Table')) {
            if (\AsmaaSalon\Database\Migrations\Create_Apple_Wallet_Table::needs_migration()) {
                \AsmaaSalon\Database\Migrations\Create_Apple_Wallet_Table::migrate();
                update_option('asmaa_salon_apple_wallet_migrated', true, false);
            } else {
                // Table exists but may need pass_type column
                \AsmaaSalon\Database\Migrations\Create_Apple_Wallet_Table::add_pass_type_column();
            }
        } else {
            // Always check for pass_type column
            \AsmaaSalon\Database\Migrations\Create_Apple_Wallet_Table::add_pass_type_column();
        }
    }

    protected function maybe_optimize_database_indexes(): void
    {
        $optimized = get_option('asmaa_salon_indexes_optimized', false);
        
        if (!$optimized && class_exists('\AsmaaSalon\Database\Migrations\Optimize_Database_Indexes')) {
            if (\AsmaaSalon\Database\Migrations\Optimize_Database_Indexes::needs_migration()) {
                \AsmaaSalon\Database\Migrations\Optimize_Database_Indexes::migrate();
                update_option('asmaa_salon_indexes_optimized', true, false);
            }
        }
    }

    protected function maybe_update_related_tables_columns(): void
    {
        $updated = get_option('asmaa_salon_related_tables_updated', false);
        
        if (!$updated && class_exists('\AsmaaSalon\Database\Migrations\Update_Related_Tables_Columns')) {
            if (\AsmaaSalon\Database\Migrations\Update_Related_Tables_Columns::needs_migration()) {
                \AsmaaSalon\Database\Migrations\Update_Related_Tables_Columns::migrate();
                update_option('asmaa_salon_related_tables_updated', true, false);
            }
        }
    }

    protected function maybe_drop_legacy_tables(): void
    {
        $dropped = get_option('asmaa_salon_legacy_tables_dropped', false);
        
        if (!$dropped && class_exists('\AsmaaSalon\Database\Migrations\Drop_Legacy_Tables')) {
            if (\AsmaaSalon\Database\Migrations\Drop_Legacy_Tables::needs_migration()) {
                \AsmaaSalon\Database\Migrations\Drop_Legacy_Tables::migrate();
                update_option('asmaa_salon_legacy_tables_dropped', true, false);
            }
        }
    }

    protected function maybe_add_sync_and_extended_tables(): void
    {
        $migrated = get_option('asmaa_salon_sync_tables_migrated', false);
        
        if (!$migrated && class_exists('\AsmaaSalon\Database\Migrations\Add_Sync_And_Extended_Tables')) {
            if (\AsmaaSalon\Database\Migrations\Add_Sync_And_Extended_Tables::needs_migration()) {
                \AsmaaSalon\Database\Migrations\Add_Sync_And_Extended_Tables::migrate();
                update_option('asmaa_salon_sync_tables_migrated', true, false);
            }
        }
    }

    protected function init_admin(): void
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);

        // Redirect earlier in the load cycle so headers are not already sent.
        // This fixes cases where clicking the admin menu item does nothing.
        add_action('load-toplevel_page_asmaa-salon', [$this, 'redirect_to_standalone']);

        // Handle direct admin.php?asmaa_salon_dashboard=1 requests
        add_action('admin_init', [$this, 'handle_admin_dashboard_request'], 1);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    protected function init_public(): void
    {
        // Register standalone dashboard page
        add_action('init', [$this, 'add_rewrite_rules']);
        // Use 'wp' hook instead of 'template_redirect' for earlier execution
        add_action('wp', [$this, 'handle_dashboard_request'], 1);
        add_filter('query_vars', [$this, 'add_query_vars']);

        // Hide WP Admin Bar only on our standalone screens (front-end)
        add_filter('show_admin_bar', [$this, 'maybe_hide_admin_bar_on_standalone'], 1000);
    }
    
    public function add_rewrite_rules(): void
    {
        add_rewrite_rule('^asmaa-salon-dashboard/?$', 'index.php?asmaa_salon_dashboard=1', 'top');
        add_rewrite_rule('^asmaa-salon-display/?$', 'index.php?asmaa_salon_display=1', 'top');
    }
    
    public function add_query_vars(array $vars): array
    {
        $vars[] = 'asmaa_salon_dashboard';
        $vars[] = 'asmaa_salon_display';
        return $vars;
    }

    /**
     * Hide WP admin bar on standalone dashboard/display pages only.
     */
    public function maybe_hide_admin_bar_on_standalone(bool $show): bool
    {
        if (get_query_var('asmaa_salon_dashboard') || get_query_var('asmaa_salon_display')) {
            return false;
        }

        return $show;
    }
    
    public function handle_dashboard_request(): void
    {
        // Check both get_query_var (for rewrite rules) and $_GET (for direct query string)
        $dashboard_requested = get_query_var('asmaa_salon_dashboard') || 
                               (isset($_GET['asmaa_salon_dashboard']) && $_GET['asmaa_salon_dashboard'] === '1');
        
        $display_requested = get_query_var('asmaa_salon_display') || 
                            (isset($_GET['asmaa_salon_display']) && $_GET['asmaa_salon_display'] === '1');
        
        if ($dashboard_requested) {
            // Check if user is logged in and has permission
            if (!is_user_logged_in() || !current_user_can('manage_options')) {
                wp_redirect(wp_login_url());
                exit;
            }
            
            $this->render_standalone_dashboard();
            exit;
        }

        if ($display_requested) {
            // Display screen is public (no auth required for TV/monitor)
            $this->render_standalone_dashboard('display');
            exit;
        }
    }
    
    protected function render_standalone_dashboard(string $mode = 'dashboard'): void
    {
        // Debug: Force output to check if function is called
        // Remove this after testing
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        
        // Ensure WP Admin Bar never shows on standalone screens.
        // This prevents the top black bar and removes the margin-top bump.
        if (function_exists('show_admin_bar')) {
            show_admin_bar(false);
        }
        remove_action('wp_footer', 'wp_admin_bar_render', 1000);
        remove_action('wp_head', '_admin_bar_bump_cb');

        // Enqueue assets
        $this->enqueue_standalone_assets();
        
        $is_display = ($mode === 'display');
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?> dir="rtl">
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $is_display ? esc_html__('Queue Display - Asmaa Salon', 'asmaa-salon') : esc_html__('لوحة تحكم صالون أسماء الجارالله', 'asmaa-salon'); ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                html { margin-top: 0 !important; }
                body { margin-top: 0 !important; }
                body.asmaa-salon-standalone {
                    width: 100%;
                    height: 100vh;
                    overflow: hidden;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    background: #f5f5f5;
                }
                #asmaa-salon-vue-root {
                    width: 100%;
                    height: 100vh;
                    margin: 0;
                    padding: 0;
                }
                .debug-loading {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    text-align: center;
                    font-size: 18px;
                }
                <?php if ($is_display): ?>
                body.asmaa-salon-standalone {
                    background: #1a1a1a;
                }
                <?php endif; ?>
            </style>
            <?php wp_head(); ?>
        </head>
        <body class="asmaa-salon-standalone <?php echo $is_display ? 'display-mode' : ''; ?>">
            <div id="asmaa-salon-vue-root" data-mode="<?php echo esc_attr($mode); ?>">
                <div class="debug-loading">
                    <p>جاري تحميل لوحة التحكم...</p>
                    <p style="font-size: 14px; color: #666; margin-top: 10px;">إذا استمر هذا النص، تحقق من Console (F12)</p>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    protected function enqueue_standalone_assets(): void
    {
        // Check if built assets exist
        $build_path = ASMAA_SALON_PLUGIN_DIR . 'assets/build/main.js';
        $has_build = file_exists($build_path);

        if ($has_build) {
            // Add filter to make Vite build script a module (ES6)
            add_filter('script_loader_tag', function($tag, $handle, $src) {
                if ($handle === 'asmaa-salon-main') {
                    // Replace <script> with <script type="module">
                    $tag = str_replace('<script ', '<script type="module" ', $tag);
                }
                return $tag;
            }, 10, 3);

            wp_enqueue_script(
                'asmaa-salon-main',
                ASMAA_SALON_PLUGIN_URL . 'assets/build/main.js',
                [],
                (string) filemtime($build_path),
                true
            );

            $css_files = glob(ASMAA_SALON_PLUGIN_DIR . 'assets/build/assets/*.css');
            if (!empty($css_files)) {
                foreach ($css_files as $css_file) {
                    $css_url = ASMAA_SALON_PLUGIN_URL . 'assets/build/assets/' . basename($css_file);
                    wp_enqueue_style(
                        'asmaa-salon-style-' . basename($css_file, '.css'),
                        $css_url,
                        [],
                        (string) filemtime($css_file)
                    );
                }
            }
        } else {
            wp_enqueue_script(
                'asmaa-salon-admin',
                ASMAA_SALON_PLUGIN_URL . 'assets/js/admin-placeholder.js',
                [],
                ASMAA_SALON_VERSION,
                true
            );
        }

        wp_localize_script(
            $has_build ? 'asmaa-salon-main' : 'asmaa-salon-admin',
            'AsmaaSalonConfig',
            [
                'restUrl'      => esc_url_raw(rest_url('asmaa-salon/v1/')),
                'nonce'        => wp_create_nonce('wp_rest'),
                'version'      => ASMAA_SALON_VERSION,
                'primaryColor' => '#BBA07A',
                'siteName'     => get_bloginfo('name'),
                'siteUrl'      => home_url('/'),
                'woocommerceActive' => class_exists('WooCommerce') && function_exists('wc_get_product'),
                // Prefer WP Custom Logo, fallback to Site Icon
                'logoUrl'      => (function () {
                    $logo_id = (int) get_theme_mod('custom_logo');
                    if ($logo_id) {
                        $logo = wp_get_attachment_image_url($logo_id, 'full');
                        if ($logo) {
                            return esc_url_raw($logo);
                        }
                    }
                    $icon = get_site_icon_url(512);
                    return $icon ? esc_url_raw($icon) : '';
                })(),
            ]
        );
    }

    public function register_admin_menu(): void
    {
        // Main Asmaa Salon menu item in wp-admin sidebar.
        // The actual redirect is handled in the `load-toplevel_page_asmaa-salon` hook
        // to ensure headers can still be sent.
        add_menu_page(
            __('Asmaa Salon', 'asmaa-salon'),
            __('Asmaa Salon', 'asmaa-salon'),
            'manage_options',
            'asmaa-salon',
            '__return_null',
            'dashicons-scissors',
            26
        );
    }

    /**
     * Handle direct admin.php requests with asmaa_salon_dashboard parameter
     */
    public function handle_admin_dashboard_request(): void
    {
        // Check if this is an admin.php request with our dashboard parameter
        if (isset($_GET['asmaa_salon_dashboard']) && $_GET['asmaa_salon_dashboard'] === '1') {
            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have permission to access this page.', 'asmaa-salon'), __('Access Denied', 'asmaa-salon'), ['response' => 403]);
                return;
            }

            // Redirect to standalone dashboard page
            $url = add_query_arg(
                'asmaa_salon_dashboard',
                '1',
                home_url('/')
            );

            wp_safe_redirect($url);
            exit;
        }
    }

    public function redirect_to_standalone(): void
    {
        // Redirect to standalone dashboard page.
        //
        // نستخدم query string مباشرة لضمان العمل حتى لو الـ rewrite rules فيها مشكلة:
        // مثال: http://localhost/workshop20226/?asmaa_salon_dashboard=1
        $url = add_query_arg(
            'asmaa_salon_dashboard',
            '1',
            home_url('/')
        );

        wp_safe_redirect($url);
        exit;
    }

    public function enqueue_admin_assets(string $hook): void
    {
        // No need to enqueue here - standalone page handles it
    }

    public function init_rest_api(): void
    {
        // Basic ping endpoint
        register_rest_route(
            'asmaa-salon/v1',
            '/ping',
            [
                'methods'             => 'GET',
                'callback'            => static function () {
                    return [
                        'success' => true,
                        'message' => 'Asmaa Salon API is alive',
                        'version' => ASMAA_SALON_VERSION,
                    ];
                },
                'permission_callback' => '__return_true',
            ]
        );

        // Register REST Controllers
        $controllers = [
            \AsmaaSalon\API\Controllers\Customers_Controller::class,
            \AsmaaSalon\API\Controllers\Services_Controller::class,
            \AsmaaSalon\API\Controllers\Staff_Controller::class,
            \AsmaaSalon\API\Controllers\Bookings_Controller::class,
            \AsmaaSalon\API\Controllers\Booking_Settings_Controller::class,
            \AsmaaSalon\API\Controllers\Orders_Controller::class,
            \AsmaaSalon\API\Controllers\Queue_Controller::class,
            \AsmaaSalon\API\Controllers\Invoices_Controller::class,
            \AsmaaSalon\API\Controllers\Payments_Controller::class,
            \AsmaaSalon\API\Controllers\Products_Controller::class,
            \AsmaaSalon\API\Controllers\Notifications_Controller::class,
            \AsmaaSalon\API\Controllers\Reports_Controller::class,
            \AsmaaSalon\API\Controllers\Loyalty_Controller::class,
            \AsmaaSalon\API\Controllers\Memberships_Controller::class,
            \AsmaaSalon\API\Controllers\Commissions_Controller::class,
            \AsmaaSalon\API\Controllers\Programs_Settings_Controller::class,
            \AsmaaSalon\API\Controllers\Inventory_Controller::class,
            \AsmaaSalon\API\Controllers\Worker_Calls_Controller::class,
            \AsmaaSalon\API\Controllers\Staff_Ratings_Controller::class,
            \AsmaaSalon\API\Controllers\POS_Controller::class,
            \AsmaaSalon\API\Controllers\Users_Controller::class,
            \AsmaaSalon\API\Controllers\Settings_Controller::class,
            \AsmaaSalon\API\Controllers\Apple_Wallet_Controller::class,
            // More controllers will be added as we build them
        ];

        foreach ($controllers as $controller_class) {
            if (class_exists($controller_class)) {
                $controller = new $controller_class();
                if (method_exists($controller, 'register_routes')) {
                    $controller->register_routes();
                }
            }
        }
    }

    /**
     * Initialize WooCommerce integration hooks
     * Always enabled automatically if WooCommerce is active
     */
    protected function init_woocommerce_integration(): void
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Products - Auto-sync from WooCommerce to Asmaa Salon
        add_action('woocommerce_new_product', [$this, 'sync_wc_product_to_asmaa'], 10, 1);
        add_action('woocommerce_update_product', [$this, 'sync_wc_product_to_asmaa'], 10, 1);
        add_action('woocommerce_product_quick_edit_save', [$this, 'sync_wc_product_to_asmaa'], 10, 1);

        // Orders - Auto-sync from WooCommerce to Asmaa Salon
        add_action('woocommerce_new_order', [$this, 'sync_wc_order_to_asmaa'], 10, 1);
        add_action('woocommerce_update_order', [$this, 'sync_wc_order_to_asmaa'], 10, 1);
        add_action('woocommerce_order_status_changed', [$this, 'sync_wc_order_to_asmaa'], 10, 1);
        
        // Payments - Auto-sync when order payment status changes
        add_action('woocommerce_payment_complete', [$this, 'sync_wc_payment_to_asmaa'], 10, 1);
        add_action('woocommerce_order_status_changed', [$this, 'sync_wc_payment_to_asmaa'], 10, 1);

        // Customers - Auto-sync from WooCommerce to Asmaa Salon
        add_action('woocommerce_created_customer', [$this, 'sync_wc_customer_to_asmaa'], 10, 1);
        add_action('woocommerce_update_customer', [$this, 'sync_wc_customer_to_asmaa'], 10, 1);
        add_action('woocommerce_customer_save_address', [$this, 'sync_wc_customer_to_asmaa'], 10, 1);
    }

    /**
     * Sync WooCommerce product to Asmaa Salon
     * Always enabled automatically
     */
    public function sync_wc_product_to_asmaa(int $product_id): void
    {
        // Prevent infinite loop - check if this is from our own sync
        if (did_action('asmaa_salon_syncing_product')) {
            return;
        }

        do_action('asmaa_salon_syncing_product');
        // Product metadata is updated on demand, no sync needed from WC to Asmaa
    }

    /**
     * Sync WooCommerce order to Asmaa Salon
     * Always enabled automatically
     */
    public function sync_wc_order_to_asmaa(int $order_id): void
    {
        // Prevent infinite loop - check if this is from our own sync
        if (did_action('asmaa_salon_syncing_order')) {
            return;
        }

        // Orders are read directly from WooCommerce, no sync needed
    }

    /**
     * Sync WooCommerce customer to Asmaa Salon
     * Always enabled automatically
     */
    public function sync_wc_customer_to_asmaa(int $customer_id): void
    {
        // Prevent infinite loop - check if this is from our own sync
        if (did_action('asmaa_salon_syncing_customer')) {
            return;
        }

        // Customers are WordPress users, extended data is created on demand, no sync needed
    }

    /**
     * Sync WooCommerce payment to Asmaa Salon
     * Always enabled automatically
     */
    public function sync_wc_payment_to_asmaa(int $order_id): void
    {
        // Prevent infinite loop - check if this is from our own sync
        if (did_action('asmaa_salon_syncing_payment')) {
            return;
        }

        do_action('asmaa_salon_syncing_payment');
        \AsmaaSalon\Services\WooCommerce_Integration_Service::sync_payment_from_wc($order_id);
    }
}



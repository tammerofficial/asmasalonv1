<?php
/**
 * Asmaa Salon System Health Check CLI Tool
 * 
 * Usage: php check-system.php
 * 
 * This script checks all system components:
 * - REST API Endpoints
 * - Vue Router Routes
 * - Controllers
 * - Stores
 * - Views
 * - Database Tables
 * - Capabilities & Permissions
 */

// Load WordPress
// Find wp-load.php by going up directories
$plugin_dir = dirname(__FILE__);
$wp_load = null;

// Go up to 5 levels to find wp-load.php
for ($i = 0; $i < 5; $i++) {
    $test_path = $plugin_dir . str_repeat('/..', $i) . '/wp-load.php';
    $real_path = realpath($test_path);
    if ($real_path && file_exists($real_path)) {
        $wp_load = $real_path;
        break;
    }
}

// Also try absolute path
if (!$wp_load) {
    $absolute_paths = [
        '/Users/alialalawi/Sites/localhost/workshop/wp-load.php',
    ];
    foreach ($absolute_paths as $path) {
        if (file_exists($path)) {
            $wp_load = $path;
            break;
        }
    }
}

if ($wp_load && file_exists($wp_load)) {
    require_once $wp_load;
}

if (!defined('ABSPATH')) {
    die("WordPress not found. Please run this script from WordPress root directory.\n" .
        "Plugin directory: {$plugin_dir}\n" .
        "Looking for wp-load.php in parent directories...\n");
}

// Load plugin
require_once __DIR__ . '/asmaa-salon.php';

class AsmaaSalon_SystemChecker
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;
    private int $warnings = 0;

    // System components mapping
    private array $components = [
        'Dashboard' => [
            'route' => '/',
            'api' => 'reports/dashboard',
            'view' => 'Dashboard.vue',
        ],
        'Operations' => [
            'routes' => ['/bookings', '/queue', '/worker-calls'],
            'apis' => ['bookings', 'queue', 'worker-calls'],
        ],
        'Bookings' => [
            'route' => '/bookings',
            'api' => 'bookings',
            'view' => 'Bookings/Index.vue',
            'sub_routes' => ['/bookings/categories', '/bookings/settings', '/bookings/appearance'],
        ],
        'Queue' => [
            'route' => '/queue',
            'api' => 'queue',
            'view' => 'Queue/Index.vue',
        ],
        'Staff Room' => [
            'route' => '/display/staff-room',
            'view' => 'Display/StaffRoom.vue',
        ],
        'Transactions' => [
            'routes' => ['/pos', '/orders', '/invoices', '/payments'],
            'apis' => ['pos', 'orders', 'invoices', 'payments'],
        ],
        'POS' => [
            'route' => '/pos',
            'api' => 'pos',
            'view' => 'POS/Index.vue',
        ],
        'Orders' => [
            'route' => '/orders',
            'api' => 'orders',
            'view' => 'Orders/Index.vue',
        ],
        'Invoices' => [
            'route' => '/invoices',
            'api' => 'invoices',
            'view' => 'Invoices/Index.vue',
        ],
        'Payments' => [
            'route' => '/payments',
            'api' => 'payments',
            'view' => 'Payments/Index.vue',
        ],
        'Catalog' => [
            'routes' => ['/services', '/products'],
            'apis' => ['services', 'products'],
        ],
        'Services' => [
            'route' => '/services',
            'api' => 'services',
            'view' => 'Services/Index.vue',
        ],
        'Products' => [
            'route' => '/products',
            'api' => 'products',
            'view' => 'Products/Index.vue',
        ],
        'People' => [
            'routes' => ['/customers', '/staff'],
            'apis' => ['customers', 'staff'],
        ],
        'Customers' => [
            'route' => '/customers',
            'api' => 'customers',
            'view' => 'Customers/Index.vue',
        ],
        'Staff' => [
            'route' => '/staff',
            'api' => 'staff',
            'view' => 'Staff/Index.vue',
        ],
        'Programs' => [
            'routes' => ['/loyalty', '/memberships', '/commissions'],
            'apis' => ['loyalty', 'memberships', 'commissions'],
        ],
        'Loyalty' => [
            'route' => '/loyalty',
            'api' => 'loyalty',
            'view' => 'Loyalty/Index.vue',
        ],
        'Memberships' => [
            'route' => '/memberships',
            'api' => 'memberships',
            'view' => 'Memberships/Index.vue',
        ],
        'Commissions' => [
            'route' => '/commissions',
            'api' => 'commissions',
            'view' => 'Commissions/Index.vue',
        ],
        'Analytics' => [
            'routes' => ['/notifications', '/reports'],
            'apis' => ['notifications', 'reports'],
        ],
        'Notifications' => [
            'route' => '/notifications',
            'api' => 'notifications',
            'view' => 'Notifications/Index.vue',
        ],
        'Reports' => [
            'route' => '/reports',
            'api' => 'reports',
            'view' => 'Reports/Index.vue',
        ],
        'Settings' => [
            'routes' => ['/core', '/programs/settings'],
            'apis' => ['booking-settings', 'programs-settings'],
        ],
        'Core' => [
            'route' => '/core',
            'view' => 'Core/Index.vue',
        ],
        'Programs Settings' => [
            'route' => '/programs/settings',
            'api' => 'programs-settings',
            'view' => 'Programs/Settings.vue',
        ],
    ];

    private array $controllers = [
        'Customers_Controller',
        'Services_Controller',
        'Staff_Controller',
        'Bookings_Controller',
        'Booking_Settings_Controller',
        'Orders_Controller',
        'Queue_Controller',
        'Invoices_Controller',
        'Payments_Controller',
        'Products_Controller',
        'Notifications_Controller',
        'Reports_Controller',
        'Loyalty_Controller',
        'Memberships_Controller',
        'Commissions_Controller',
        'Programs_Settings_Controller',
        'Inventory_Controller',
        'Worker_Calls_Controller',
        'Staff_Ratings_Controller',
        'POS_Controller',
        'Users_Controller',
    ];

    private array $database_tables = [
        'asmaa_customers',
        'asmaa_services',
        'asmaa_staff',
        'asmaa_bookings',
        'asmaa_booking_settings', // New table for structured settings
        'asmaa_orders',
        'asmaa_order_items',
        'asmaa_queue_tickets', // Main queue table
        'asmaa_queue', // Alias table for compatibility
        'asmaa_invoices',
        'asmaa_invoice_items',
        'asmaa_payments',
        'asmaa_products',
        'asmaa_inventory_movements',
        'asmaa_loyalty_transactions',
        'asmaa_customer_memberships', // Main memberships table
        'asmaa_memberships', // Alias table for compatibility
        'asmaa_membership_plans',
        'asmaa_membership_service_usage',
        'asmaa_membership_extensions',
        'asmaa_staff_commissions', // Main commissions table
        'asmaa_commissions', // Alias table for compatibility
        'asmaa_commission_settings',
        'asmaa_pos_sessions',
        'asmaa_activity_log',
        'asmaa_notifications',
        'asmaa_worker_calls',
        'asmaa_staff_ratings',
    ];

    public function run(): void
    {
        $this->printHeader();
        
        $this->checkWordPress();
        $this->checkPlugin();
        $this->checkDatabase();
        $this->checkControllers();
        $this->checkRESTAPI();
        $this->checkVueRoutes();
        $this->checkViews();
        $this->checkStores();
        $this->checkCapabilities();
        $this->checkAssets();
        
        $this->printSummary();
    }

    private function checkWordPress(): void
    {
        $this->printSection('WordPress Environment');
        
        $this->check('WordPress Loaded', defined('ABSPATH'), 'WordPress core not loaded');
        $this->check('PHP Version', version_compare(PHP_VERSION, '8.0', '>='), 'PHP 8.0+ required', PHP_VERSION);
        $this->check('WordPress Version', version_compare(get_bloginfo('version'), '6.0', '>='), 'WordPress 6.0+ required', get_bloginfo('version'));
        $this->check('REST API Enabled', rest_url() !== false, 'REST API not available');
    }

    private function checkPlugin(): void
    {
        $this->printSection('Plugin Status');
        
        $this->check('Plugin File Exists', file_exists(__DIR__ . '/asmaa-salon.php'), 'Plugin main file missing');
        $this->check('Plugin Active', is_plugin_active('asmasalonv1/asmaa-salon.php'), 'Plugin not activated');
        $this->check('Plugin Version', defined('ASMAA_SALON_VERSION'), 'Version constant not defined', ASMAA_SALON_VERSION ?? 'N/A');
        $this->check('Plugin Class', class_exists('\AsmaaSalon\Plugin'), 'Plugin class not found');
    }

    private function checkDatabase(): void
    {
        $this->printSection('Database Tables');
        
        global $wpdb;
        
        foreach ($this->database_tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table;
            $this->check("Table: {$table}", $exists, "Table {$full_table} does not exist");
        }
    }

    private function checkControllers(): void
    {
        $this->printSection('REST API Controllers');
        
        foreach ($this->controllers as $controller) {
            $class = "\\AsmaaSalon\\API\\Controllers\\{$controller}";
            $file = __DIR__ . "/includes/API/Controllers/{$controller}.php";
            
            $file_exists = file_exists($file);
            $class_exists = class_exists($class);
            
            if ($file_exists && $class_exists) {
                try {
                    $instance = new $class();
                    $has_register = method_exists($instance, 'register_routes');
                    $this->check("Controller: {$controller}", $has_register, "Missing register_routes() method");
                } catch (\Exception $e) {
                    $this->check("Controller: {$controller}", false, "Error instantiating: " . $e->getMessage());
                }
            } else {
                $this->check("Controller: {$controller}", false, "File or class not found");
            }
        }
    }

    private function checkRESTAPI(): void
    {
        $this->printSection('REST API Endpoints');
        
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes('asmaa-salon/v1');
        
        $this->check('API Namespace Registered', !empty($routes), 'No routes found for asmaa-salon/v1 namespace');
        
        // Check main endpoints
        $endpoints_to_check = [
            'ping' => 'GET',
            'customers' => 'GET',
            'services' => 'GET',
            'staff' => 'GET',
            'bookings' => 'GET',
            'orders' => 'GET',
            'queue' => 'GET',
            'invoices' => 'GET',
            'payments' => 'GET',
            'products' => 'GET',
            'notifications' => 'GET',
            'reports' => 'GET',
            'loyalty' => 'GET',
            'memberships' => 'GET',
            'commissions' => 'GET',
            'pos' => 'GET',
            'users' => 'GET',
        ];
        
        foreach ($endpoints_to_check as $endpoint => $method) {
            $route_found = false;
            foreach ($routes as $route => $handlers) {
                if (strpos($route, $endpoint) !== false) {
                    foreach ($handlers as $handler) {
                        if (isset($handler['methods'][$method])) {
                            $route_found = true;
                            break 2;
                        }
                    }
                }
            }
            $this->check("Endpoint: /{$endpoint}", $route_found, "Route not registered or method not available");
        }
    }

    private function checkVueRoutes(): void
    {
        $this->printSection('Vue Router Routes');
        
        $router_file = __DIR__ . '/assets/src/router/index.js';
        $this->check('Router File Exists', file_exists($router_file), 'Router file not found');
        
        if (file_exists($router_file)) {
            $content = file_get_contents($router_file);
            
            foreach ($this->components as $name => $config) {
                $routes = $config['routes'] ?? [$config['route'] ?? null];
                $routes = array_filter($routes);
                
                foreach ($routes as $route) {
                    if ($route) {
                        $route_clean = str_replace(['/', ':'], ['\/', '\\d+'], $route);
                        $found = preg_match("/path:\s*['\"]{$route_clean}['\"]/", $content) || 
                                preg_match("/path:\s*['\"]{$route}['\"]/", $content);
                        $this->check("Route: {$route}", $found, "Route not found in router");
                    }
                }
            }
        }
    }

    private function checkViews(): void
    {
        $this->printSection('Vue Views');
        
        foreach ($this->components as $name => $config) {
            if (isset($config['view'])) {
                $view_file = __DIR__ . '/assets/src/views/' . $config['view'];
                $this->check("View: {$config['view']}", file_exists($view_file), "View file not found");
            }
        }
        
        // Check additional views
        $additional_views = [
            'Users/Index.vue',
            'Roles/Index.vue',
            'Display/Queue.vue',
            'Display/StaffRoom.vue',
            'Rating.vue',
        ];
        
        foreach ($additional_views as $view) {
            $view_file = __DIR__ . '/assets/src/views/' . $view;
            $this->check("View: {$view}", file_exists($view_file), "View file not found");
        }
    }

    private function checkStores(): void
    {
        $this->printSection('Pinia Stores');
        
        $stores_dir = __DIR__ . '/assets/src/stores';
        $this->check('Stores Directory', is_dir($stores_dir), 'Stores directory not found');
        
        $expected_stores = ['bookings.js', 'notifications.js', 'ui.js'];
        
        foreach ($expected_stores as $store) {
            $store_file = $stores_dir . '/' . $store;
            $this->check("Store: {$store}", file_exists($store_file), "Store file not found");
        }
    }

    private function checkCapabilities(): void
    {
        $this->printSection('Capabilities & Permissions');
        
        // Suppress WordPress errors for this section
        $error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
            return true; // Suppress errors
        });
        
        try {
            $this->check('Capabilities Class', class_exists('\AsmaaSalon\Security\Capabilities'), 'Capabilities class not found');
            
            if (class_exists('\AsmaaSalon\Security\Capabilities')) {
                $capabilities = \AsmaaSalon\Security\Capabilities::get_all_capabilities();
                $this->check('Capabilities Registered', !empty($capabilities), 'No capabilities found', count($capabilities) . ' capabilities');
            }
            
            // Check if roles exist
            $roles = ['asmaa_super_admin', 'asmaa_admin', 'asmaa_manager', 'asmaa_accountant', 
                      'asmaa_receptionist', 'asmaa_cashier', 'asmaa_staff'];
            
            foreach ($roles as $role) {
                $role_exists = get_role($role) !== null;
                $this->check("Role: {$role}", $role_exists, "Role not registered");
            }
        } catch (\Throwable $e) {
            $this->check('Capabilities Check', false, 'Error: ' . $e->getMessage());
        } finally {
            if ($error_handler) {
                restore_error_handler();
            }
        }
    }

    private function checkAssets(): void
    {
        $this->printSection('Build Assets');
        
        $build_dir = __DIR__ . '/assets/build';
        $this->check('Build Directory', is_dir($build_dir), 'Build directory not found');
        
        $main_js = $build_dir . '/main.js';
        $this->check('Main JS Built', file_exists($main_js), 'main.js not built', filesize($main_js) . ' bytes');
        
        $assets_dir = $build_dir . '/assets';
        if (is_dir($assets_dir)) {
            $css_files = glob($assets_dir . '/*.css');
            $this->check('CSS Files', !empty($css_files), 'No CSS files found', count($css_files) . ' files');
        }
        
        // Check JS chunks in build directory
        $js_chunks = glob($build_dir . '/chunk-*.js');
        $this->check('JS Chunks', !empty($js_chunks), 'No JS chunk files found', count($js_chunks) . ' files');
    }

    private function check(string $name, bool $condition, string $error = '', string $info = ''): void
    {
        if ($condition) {
            $this->passed++;
            $status = "✓ PASS";
            $color = "\033[32m"; // Green
            $message = $info ?: 'OK';
        } else {
            $this->failed++;
            $status = "✗ FAIL";
            $color = "\033[31m"; // Red
            $message = $error ?: 'Failed';
        }
        
        $reset = "\033[0m";
        $name_padded = str_pad($name, 50);
        
        echo "{$color}{$status}{$reset} {$name_padded} {$message}\n";
        
        $this->results[] = [
            'name' => $name,
            'status' => $condition ? 'pass' : 'fail',
            'message' => $message,
        ];
    }

    private function printSection(string $title): void
    {
        echo "\n\033[1;36m" . str_repeat('=', 80) . "\033[0m\n";
        echo "\033[1;36m" . str_pad($title, 80, ' ', STR_PAD_BOTH) . "\033[0m\n";
        echo "\033[1;36m" . str_repeat('=', 80) . "\033[0m\n\n";
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "\033[1;33m" . str_repeat('=', 80) . "\033[0m\n";
        echo "\033[1;33m" . str_pad('ASMAA SALON SYSTEM HEALTH CHECK', 80, ' ', STR_PAD_BOTH) . "\033[0m\n";
        echo "\033[1;33m" . str_pad('Version: ' . (defined('ASMAA_SALON_VERSION') ? ASMAA_SALON_VERSION : 'Unknown'), 80, ' ', STR_PAD_BOTH) . "\033[0m\n";
        echo "\033[1;33m" . str_pad('Date: ' . date('Y-m-d H:i:s'), 80, ' ', STR_PAD_BOTH) . "\033[0m\n";
        echo "\033[1;33m" . str_repeat('=', 80) . "\033[0m\n";
    }

    private function printSummary(): void
    {
        $total = $this->passed + $this->failed + $this->warnings;
        $pass_percentage = $total > 0 ? round(($this->passed / $total) * 100, 2) : 0;
        
        echo "\n";
        echo "\033[1;36m" . str_repeat('=', 80) . "\033[0m\n";
        echo "\033[1;36m" . str_pad('SUMMARY', 80, ' ', STR_PAD_BOTH) . "\033[0m\n";
        echo "\033[1;36m" . str_repeat('=', 80) . "\033[0m\n\n";
        
        echo "\033[32m✓ Passed:\033[0m {$this->passed}\n";
        echo "\033[31m✗ Failed:\033[0m {$this->failed}\n";
        echo "\033[33m⚠ Warnings:\033[0m {$this->warnings}\n";
        echo "\033[1mTotal Checks:\033[0m {$total}\n";
        echo "\033[1mSuccess Rate:\033[0m {$pass_percentage}%\n\n";
        
        if ($this->failed > 0) {
            echo "\033[31m" . str_repeat('=', 80) . "\033[0m\n";
            echo "\033[1;31mFAILED CHECKS:\033[0m\n";
            echo "\033[31m" . str_repeat('=', 80) . "\033[0m\n";
            
            foreach ($this->results as $result) {
                if ($result['status'] === 'fail') {
                    echo "\033[31m✗ {$result['name']}\033[0m - {$result['message']}\n";
                }
            }
            echo "\n";
        }
        
        if ($this->failed === 0) {
            echo "\033[1;32m" . str_repeat('=', 80) . "\033[0m\n";
            echo "\033[1;32m" . str_pad('ALL CHECKS PASSED! ✓', 80, ' ', STR_PAD_BOTH) . "\033[0m\n";
            echo "\033[1;32m" . str_repeat('=', 80) . "\033[0m\n";
        }
        
        echo "\n";
    }
}

// Run the checker
$checker = new AsmaaSalon_SystemChecker();
$checker->run();


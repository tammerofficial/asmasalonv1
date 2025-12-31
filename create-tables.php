<?php
/**
 * Create Missing Database Tables
 * 
 * Run this script to create all missing database tables
 * Usage: php create-tables.php
 */

// Load WordPress
$plugin_dir = dirname(__FILE__);
$wp_load = null;

// Find wp-load.php
for ($i = 0; $i < 5; $i++) {
    $test_path = $plugin_dir . str_repeat('/..', $i) . '/wp-load.php';
    $real_path = realpath($test_path);
    if ($real_path && file_exists($real_path)) {
        $wp_load = $real_path;
        break;
    }
}

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
    die("WordPress not found. Please run this script from WordPress root directory.\n");
}

// Load plugin
require_once __DIR__ . '/asmaa-salon.php';

echo "\n";
echo "========================================\n";
echo "Creating Missing Database Tables\n";
echo "========================================\n\n";

// Create tables
try {
    \AsmaaSalon\Database\Schema::create_core_tables();
    echo "✓ All tables created successfully!\n\n";
    
    // Check which tables exist
    global $wpdb;
    $tables_to_check = [
        'asmaa_booking_settings',
        'asmaa_queue',
        'asmaa_memberships',
        'asmaa_commissions',
    ];
    
    echo "Checking created tables:\n";
    foreach ($tables_to_check as $table) {
        $full_table = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table;
        if ($exists) {
            echo "  ✓ {$full_table}\n";
        } else {
            echo "  ✗ {$full_table} (not found)\n";
        }
    }
    
    echo "\nDone!\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}



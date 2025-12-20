<?php
/**
 * Manual Seeder Script
 * Run this file directly to seed all tables with sample data
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Check if user has permission
if (!current_user_can('manage_options')) {
    die('Access denied. You need administrator privileges.');
}

// Load Seeder class
require_once(__DIR__ . '/includes/Install/Seeder.php');

echo "<h1>🌱 Asmaa Salon - Database Seeder</h1>";
echo "<p>Starting seeding process...</p>";

try {
    // Clear existing data (optional - comment out if you want to keep existing data)
    global $wpdb;
    
    echo "<h2>Step 1: Clearing existing data...</h2>";
    $tables = [
        'asmaa_pos_sessions',
        'asmaa_commission_settings',
        'asmaa_staff_commissions',
        'asmaa_staff_ratings',
        'asmaa_inventory_movements',
        'asmaa_membership_service_usage',
        'asmaa_membership_extensions',
        'asmaa_customer_memberships',
        'asmaa_loyalty_transactions',
        'asmaa_payments',
        'asmaa_invoice_items',
        'asmaa_invoices',
        'asmaa_order_items',
        'asmaa_orders',
        'asmaa_worker_calls',
        'asmaa_queue_tickets',
        'asmaa_bookings',
        'asmaa_membership_plans',
        'asmaa_products',
        'asmaa_services',
        'asmaa_staff',
        'asmaa_customers',
    ];
    
    foreach ($tables as $table) {
        $full_table = $wpdb->prefix . $table;
        $wpdb->query("TRUNCATE TABLE {$full_table}");
        echo "✓ Cleared: {$table}<br>";
    }
    
    echo "<h2>Step 2: Seeding data...</h2>";
    
    // Run seeder
    \AsmaaSalon\Install\Seeder::seed();
    
    echo "<h2>✅ Success!</h2>";
    echo "<p>All tables have been seeded with sample data.</p>";
    
    // Show summary
    echo "<h2>📊 Summary:</h2>";
    echo "<ul>";
    echo "<li>✅ Staff: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_staff") . " records</li>";
    echo "<li>✅ Services: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_services") . " records</li>";
    echo "<li>✅ Customers: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_customers") . " records</li>";
    echo "<li>✅ Products: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_products") . " records</li>";
    echo "<li>✅ Membership Plans: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_membership_plans") . " records</li>";
    echo "<li>✅ Bookings: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_bookings") . " records</li>";
    echo "<li>✅ Queue Tickets: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_queue_tickets") . " records</li>";
    echo "<li>✅ Orders: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_orders") . " records</li>";
    echo "<li>✅ Order Items: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_order_items") . " records</li>";
    echo "<li>✅ Invoices: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_invoices") . " records</li>";
    echo "<li>✅ Invoice Items: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_invoice_items") . " records</li>";
    echo "<li>✅ Payments: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_payments") . " records</li>";
    echo "<li>✅ Loyalty Transactions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_loyalty_transactions") . " records</li>";
    echo "<li>✅ Customer Memberships: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_customer_memberships") . " records</li>";
    echo "<li>✅ Membership Service Usage: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_membership_service_usage") . " records</li>";
    echo "<li>✅ Inventory Movements: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_inventory_movements") . " records</li>";
    echo "<li>✅ Staff Ratings: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_staff_ratings") . " records</li>";
    echo "<li>✅ Staff Commissions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_staff_commissions") . " records</li>";
    echo "<li>✅ Commission Settings: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_commission_settings") . " records</li>";
    echo "<li>✅ POS Sessions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_pos_sessions") . " records</li>";
    echo "<li>✅ Worker Calls: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_worker_calls") . " records</li>";
    echo "<li>✅ Membership Extensions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_membership_extensions") . " records</li>";
    echo "</ul>";
    
    echo "<p><a href='" . admin_url('admin.php?page=asmaa-salon') . "'>← Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error!</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}

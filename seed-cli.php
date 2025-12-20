#!/usr/bin/env php
<?php
/**
 * CLI Seeder Script - Run from terminal
 * Usage: php seed-cli.php
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Load Seeder class
require_once(__DIR__ . '/includes/Install/Seeder.php');

echo "🌱 Asmaa Salon - Database Seeder (CLI)\n";
echo "=====================================\n\n";

try {
    // Clear existing data
    global $wpdb;
    
    echo "Step 1: Clearing existing data...\n";
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
        echo "✓ Cleared: {$table}\n";
    }
    
    echo "\nStep 2: Seeding data...\n";
    
    // Run seeder
    \AsmaaSalon\Install\Seeder::seed();
    
    echo "\n✅ Success!\n";
    echo "All tables have been seeded with sample data.\n\n";
    
    // Show summary
    echo "📊 Summary:\n";
    echo "===========\n";
    echo "✅ Staff: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_staff") . " records\n";
    echo "✅ Services: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_services") . " records\n";
    echo "✅ Customers: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_customers") . " records\n";
    echo "✅ Products: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_products") . " records\n";
    echo "✅ Membership Plans: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_membership_plans") . " records\n";
    echo "✅ Bookings: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_bookings") . " records\n";
    echo "✅ Queue Tickets: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_queue_tickets") . " records\n";
    echo "✅ Orders: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_orders") . " records\n";
    echo "✅ Order Items: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_order_items") . " records\n";
    echo "✅ Invoices: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_invoices") . " records\n";
    echo "✅ Invoice Items: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_invoice_items") . " records\n";
    echo "✅ Payments: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_payments") . " records\n";
    echo "✅ Loyalty Transactions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_loyalty_transactions") . " records\n";
    echo "✅ Customer Memberships: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_customer_memberships") . " records\n";
    echo "✅ Membership Service Usage: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_membership_service_usage") . " records\n";
    echo "✅ Inventory Movements: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_inventory_movements") . " records\n";
    echo "✅ Staff Ratings: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_staff_ratings") . " records\n";
    echo "✅ Staff Commissions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_staff_commissions") . " records\n";
    echo "✅ Commission Settings: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_commission_settings") . " records\n";
    echo "✅ POS Sessions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_pos_sessions") . " records\n";
    echo "✅ Worker Calls: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_worker_calls") . " records\n";
    echo "✅ Membership Extensions: " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_membership_extensions") . " records\n";
    
    echo "\n🎉 Done! You can now view the dashboard.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error!\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

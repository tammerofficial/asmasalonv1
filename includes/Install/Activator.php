<?php

namespace AsmaaSalon\Install;

use AsmaaSalon\Database\Schema;
use AsmaaSalon\Security\Capabilities;
use AsmaaSalon\Services\MembershipExpiryChecker;

if (!defined('ABSPATH')) {
    exit;
}

class Activator
{
    public static function activate(): void
    {
        // Create database tables
        Schema::create_core_tables();

        // Register capabilities and roles
        Capabilities::register_capabilities();

        // Set DB version to current plugin version
        update_option('asmaa_salon_db_version', defined('ASMAA_SALON_VERSION') ? ASMAA_SALON_VERSION : '0.0.0', false);

        // Seed sample data if tables are empty
        Seeder::seed();

        // Flush rewrite rules for standalone dashboard page
        add_rewrite_rule('^asmaa-salon-dashboard/?$', 'index.php?asmaa_salon_dashboard=1', 'top');
        flush_rewrite_rules();

        // ✅ Schedule daily membership expiry check
        MembershipExpiryChecker::schedule_daily_check();
    }
}


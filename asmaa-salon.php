<?php
/**
 * Plugin Name: Asmaa Salon
 * Plugin URI:  https://example.com/asmaa-salon
 * Description: Salon management system for appointments, queue, POS, loyalty, memberships and commissions, built as a Vue/CoreUI dashboard inside WordPress.
 * Version:     0.2.0
 * Author:      Asmaa Salon Team
 * Text Domain: asmaa-salon
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ASMAA_SALON_VERSION', '0.2.0');
define('ASMAA_SALON_PLUGIN_FILE', __FILE__);
define('ASMAA_SALON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASMAA_SALON_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASMAA_SALON_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Simple PSR-4 autoloader for Asmaa Salon
spl_autoload_register(function (string $class): void {
    $prefix   = 'AsmaaSalon\\';
    $base_dir = ASMAA_SALON_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Main plugin bootstrap
require_once ASMAA_SALON_PLUGIN_DIR . 'includes/Plugin.php';

// Activation / deactivation hooks
register_activation_hook(
    __FILE__,
    static function (): void {
        if (class_exists(\AsmaaSalon\Install\Activator::class)) {
            \AsmaaSalon\Install\Activator::activate();
        }
    }
);

register_deactivation_hook(
    __FILE__,
    static function (): void {
        if (class_exists(\AsmaaSalon\Install\Deactivator::class)) {
            \AsmaaSalon\Install\Deactivator::deactivate();
        }
    }
);

// Global helper
function asmaa_salon(): \AsmaaSalon\Plugin
{
    return \AsmaaSalon\Plugin::instance();
}

// Kick it off
asmaa_salon();


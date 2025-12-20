<?php

namespace AsmaaSalon\Security;

if (!defined('ABSPATH')) {
    exit;
}

class Capabilities
{
    /**
     * Register all Asmaa Salon capabilities and roles
     */
    public static function register_capabilities(): void
    {
        // Define all capabilities
        $all_capabilities = self::get_all_capabilities();

        // Get or create administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($all_capabilities as $cap) {
                if (!$admin_role->has_cap($cap)) {
                    $admin_role->add_cap($cap);
                }
            }
        }

        // Create custom roles if they don't exist
        self::create_custom_roles();
    }

    /**
     * Get all Asmaa Salon capabilities
     */
    public static function get_all_capabilities(): array
    {
        return [
            // Services
            'asmaa_services_view',
            'asmaa_services_create',
            'asmaa_services_update',
            'asmaa_services_delete',
            'asmaa_services_restore',
            'asmaa_services_force_delete',

            // Customers
            'asmaa_customers_view',
            'asmaa_customers_create',
            'asmaa_customers_update',
            'asmaa_customers_delete',
            'asmaa_customers_restore',
            'asmaa_customers_force_delete',
            'asmaa_customers_export',

            // Staff
            'asmaa_staff_view',
            'asmaa_staff_create',
            'asmaa_staff_update',
            'asmaa_staff_delete',
            'asmaa_staff_restore',
            'asmaa_staff_force_delete',
            'asmaa_staff_view_commissions',
            'asmaa_staff_view_ratings',

            // Bookings
            'asmaa_bookings_view',
            'asmaa_bookings_create',
            'asmaa_bookings_update',
            'asmaa_bookings_delete',
            'asmaa_bookings_restore',
            'asmaa_bookings_force_delete',
            'asmaa_bookings_confirm',
            'asmaa_bookings_cancel',
            'asmaa_bookings_complete',

            // Queue
            'asmaa_queue_view',
            'asmaa_queue_create',
            'asmaa_queue_update',
            'asmaa_queue_delete',
            'asmaa_queue_call',
            'asmaa_queue_start',
            'asmaa_queue_complete',
            'asmaa_queue_cancel',

            // Orders
            'asmaa_orders_view',
            'asmaa_orders_create',
            'asmaa_orders_update',
            'asmaa_orders_delete',
            'asmaa_orders_restore',
            'asmaa_orders_force_delete',
            'asmaa_orders_export',

            // Invoices
            'asmaa_invoices_view',
            'asmaa_invoices_create',
            'asmaa_invoices_update',
            'asmaa_invoices_delete',
            'asmaa_invoices_restore',
            'asmaa_invoices_force_delete',
            'asmaa_invoices_print',
            'asmaa_invoices_send',
            'asmaa_invoices_cancel',

            // Payments
            'asmaa_payments_view',
            'asmaa_payments_create',
            'asmaa_payments_update',
            'asmaa_payments_delete',
            'asmaa_payments_restore',
            'asmaa_payments_force_delete',
            'asmaa_payments_refund',

            // Products
            'asmaa_products_view',
            'asmaa_products_create',
            'asmaa_products_update',
            'asmaa_products_delete',
            'asmaa_products_restore',
            'asmaa_products_force_delete',

            // Inventory
            'asmaa_inventory_view',
            'asmaa_inventory_manage',
            'asmaa_inventory_adjust',
            'asmaa_inventory_movements',
            'asmaa_inventory_alerts',

            // Reports
            'asmaa_reports_view',
            'asmaa_reports_sales',
            'asmaa_reports_commissions',
            'asmaa_reports_bookings',
            'asmaa_reports_customers',
            'asmaa_reports_staff',
            'asmaa_reports_inventory',
            'asmaa_reports_export',

            // Commissions
            'asmaa_commissions_view',
            'asmaa_commissions_calculate',
            'asmaa_commissions_approve',
            'asmaa_commissions_pay',
            'asmaa_commissions_export',

            // Loyalty
            'asmaa_loyalty_view',
            'asmaa_loyalty_manage',
            'asmaa_loyalty_adjust',
            'asmaa_loyalty_transactions',

            // Memberships
            'asmaa_memberships_view',
            'asmaa_memberships_create',
            'asmaa_memberships_update',
            'asmaa_memberships_delete',
            'asmaa_memberships_cancel',
            'asmaa_memberships_renew',

            // Access Control
            'asmaa_access_view_users',
            'asmaa_access_create_users',
            'asmaa_access_update_users',
            'asmaa_access_delete_users',
            'asmaa_access_reset_passwords',
            'asmaa_access_view_roles',
            'asmaa_access_create_roles',
            'asmaa_access_update_roles',
            'asmaa_access_delete_roles',
            'asmaa_access_assign_roles',
            'asmaa_access_view_permissions',
            'asmaa_access_assign_permissions',
        ];
    }

    /**
     * Create custom roles with appropriate capabilities
     */
    protected static function create_custom_roles(): void
    {
        // Super Admin - All capabilities
        $super_admin_caps = self::get_all_capabilities();
        add_role('asmaa_super_admin', __('Super Admin', 'asmaa-salon'), $super_admin_caps);

        // Admin - All except force_delete
        $admin_caps = array_filter(
            self::get_all_capabilities(),
            fn($cap) => strpos($cap, 'force_delete') === false
        );
        add_role('asmaa_admin', __('Admin', 'asmaa-salon'), $admin_caps);

        // Accountant - Financial modules only
        add_role('asmaa_accountant', __('Accountant', 'asmaa-salon'), [
            'asmaa_orders_view',
            'asmaa_orders_create',
            'asmaa_orders_export',
            'asmaa_invoices_view',
            'asmaa_invoices_create',
            'asmaa_invoices_print',
            'asmaa_invoices_send',
            'asmaa_payments_view',
            'asmaa_payments_create',
            'asmaa_payments_refund',
            'asmaa_commissions_view',
            'asmaa_commissions_export',
            'asmaa_reports_sales',
            'asmaa_reports_commissions',
            'asmaa_reports_export',
            'asmaa_customers_view',
        ]);

        // Manager - Operations management
        add_role('asmaa_manager', __('Manager', 'asmaa-salon'), [
            'asmaa_staff_view',
            'asmaa_staff_create',
            'asmaa_staff_update',
            'asmaa_staff_view_commissions',
            'asmaa_staff_view_ratings',
            'asmaa_bookings_view',
            'asmaa_bookings_update',
            'asmaa_queue_view',
            'asmaa_queue_create',
            'asmaa_queue_call',
            'asmaa_queue_complete',
            'asmaa_services_view',
            'asmaa_services_create',
            'asmaa_services_update',
            'asmaa_inventory_view',
            'asmaa_inventory_manage',
            'asmaa_commissions_view',
            'asmaa_commissions_approve',
            'asmaa_reports_bookings',
            'asmaa_reports_staff',
            'asmaa_reports_inventory',
        ]);

        // Receptionist - Basic operations
        add_role('asmaa_receptionist', __('Receptionist', 'asmaa-salon'), [
            'asmaa_bookings_view',
            'asmaa_bookings_create',
            'asmaa_bookings_update',
            'asmaa_queue_view',
            'asmaa_queue_create',
            'asmaa_queue_call',
            'asmaa_customers_view',
            'asmaa_customers_create',
            'asmaa_customers_update',
            'asmaa_services_view',
            'asmaa_staff_view',
            'asmaa_loyalty_view',
            'asmaa_memberships_view',
            'asmaa_memberships_create',
        ]);

        // Cashier - POS and payments
        add_role('asmaa_cashier', __('Cashier', 'asmaa-salon'), [
            'asmaa_orders_view',
            'asmaa_orders_create',
            'asmaa_orders_update',
            'asmaa_payments_view',
            'asmaa_payments_create',
            'asmaa_invoices_view',
            'asmaa_invoices_create',
            'asmaa_invoices_print',
            'asmaa_customers_view',
            'asmaa_customers_create',
            'asmaa_customers_update',
            'asmaa_inventory_view',
            'asmaa_loyalty_view',
            'asmaa_memberships_view',
            'asmaa_memberships_create',
        ]);

        // Staff - Limited access
        add_role('asmaa_staff', __('Staff', 'asmaa-salon'), [
            'asmaa_bookings_view',
            'asmaa_queue_view',
            'asmaa_staff_view_commissions',
            'asmaa_staff_view_ratings',
        ]);
    }

    /**
     * Check if user has capability
     */
    public static function can(string $capability, ?int $user_id = null): bool
    {
        if ($user_id === null) {
            return current_user_can($capability);
        }

        $user = get_user_by('id', $user_id);
        return $user && $user->has_cap($capability);
    }
}

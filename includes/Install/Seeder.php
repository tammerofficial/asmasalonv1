<?php

namespace AsmaaSalon\Install;

if (!defined('ABSPATH')) {
    exit;
}

class Seeder
{
    public static function seed(): void
    {
        global $wpdb;

        // Check if data already exists
        $staff_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_staff WHERE deleted_at IS NULL");
        $services_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_services WHERE deleted_at IS NULL");
        $customers_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_customers WHERE deleted_at IS NULL");

        // Only seed if tables are empty
        if ($staff_count > 0 || $services_count > 0 || $customers_count > 0) {
            return; // Data already exists
        }

        // 1. Seed Staff
        $staff_table = $wpdb->prefix . 'asmaa_staff';
        $staff_data = [
            ['name' => 'أسماء الجراح', 'phone' => '+965 12345678', 'email' => 'asmaa@salon.com', 'position' => 'Senior Stylist', 'rating' => 4.8, 'total_ratings' => 45, 'total_services' => 120, 'total_revenue' => 12500.500, 'is_active' => 1],
            ['name' => 'فاطمة أحمد', 'phone' => '+965 23456789', 'email' => 'fatima@salon.com', 'position' => 'Color Specialist', 'rating' => 4.6, 'total_ratings' => 32, 'total_services' => 95, 'total_revenue' => 9800.250, 'is_active' => 1],
            ['name' => 'مريم علي', 'phone' => '+965 34567890', 'email' => 'mariam@salon.com', 'position' => 'Hair Stylist', 'rating' => 4.5, 'total_ratings' => 28, 'total_services' => 78, 'total_revenue' => 7500.000, 'is_active' => 1],
            ['name' => 'نورا محمد', 'phone' => '+965 45678901', 'email' => 'nora@salon.com', 'position' => 'Skincare Specialist', 'rating' => 4.7, 'total_ratings' => 38, 'total_services' => 105, 'total_revenue' => 11200.750, 'is_active' => 1],
            ['name' => 'سارة خالد', 'phone' => '+965 56789012', 'email' => 'sara@salon.com', 'position' => 'Junior Stylist', 'rating' => 4.2, 'total_ratings' => 15, 'total_services' => 45, 'total_revenue' => 4200.500, 'is_active' => 1],
        ];

        foreach ($staff_data as $staff) {
            $wpdb->insert($staff_table, $staff);
        }

        // 2. Seed Services
        $services_table = $wpdb->prefix . 'asmaa_services';
        $services_data = [
            ['name' => 'Haircut', 'name_ar' => 'قص شعر', 'description' => 'Professional haircut service', 'price' => 15.000, 'duration' => 30, 'category' => 'Haircut', 'is_active' => 1],
            ['name' => 'Hair Coloring', 'name_ar' => 'صبغة شعر', 'description' => 'Full hair coloring service', 'price' => 85.000, 'duration' => 120, 'category' => 'Coloring', 'is_active' => 1],
            ['name' => 'Facial Treatment', 'name_ar' => 'علاج الوجه', 'description' => 'Deep cleansing facial treatment', 'price' => 45.000, 'duration' => 60, 'category' => 'Skincare', 'is_active' => 1],
            ['name' => 'Manicure', 'name_ar' => 'مانيكير', 'description' => 'Nail care and polish', 'price' => 25.000, 'duration' => 45, 'category' => 'Nails', 'is_active' => 1],
            ['name' => 'Massage', 'name_ar' => 'مساج', 'description' => 'Relaxing body massage', 'price' => 60.000, 'duration' => 90, 'category' => 'Massage', 'is_active' => 1],
            ['name' => 'Hair Styling', 'name_ar' => 'تصفيف شعر', 'description' => 'Professional hair styling', 'price' => 35.000, 'duration' => 45, 'category' => 'Haircut', 'is_active' => 1],
            ['name' => 'Hair Treatment', 'name_ar' => 'علاج شعر', 'description' => 'Deep conditioning treatment', 'price' => 50.000, 'duration' => 60, 'category' => 'Haircut', 'is_active' => 1],
            ['name' => 'Eyebrow Threading', 'name_ar' => 'خيط الحواجب', 'description' => 'Eyebrow shaping', 'price' => 10.000, 'duration' => 20, 'category' => 'Skincare', 'is_active' => 1],
        ];

        foreach ($services_data as $service) {
            $wpdb->insert($services_table, $service);
        }

        // 3. Seed Customers
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $customers_data = [
            ['name' => 'سارة أحمد', 'phone' => '+965 12345678', 'email' => 'sara@example.com', 'city' => 'الكويت', 'gender' => 'female', 'is_active' => 1, 'total_visits' => 15, 'total_spent' => 1250.500, 'loyalty_points' => 125],
            ['name' => 'فاطمة محمد', 'phone' => '+965 23456789', 'email' => 'fatima@example.com', 'city' => 'السالمية', 'gender' => 'female', 'is_active' => 1, 'total_visits' => 12, 'total_spent' => 980.250, 'loyalty_points' => 98],
            ['name' => 'مريم علي', 'phone' => '+965 34567890', 'email' => 'mariam@example.com', 'city' => 'حولي', 'gender' => 'female', 'is_active' => 1, 'total_visits' => 10, 'total_spent' => 750.000, 'loyalty_points' => 75],
            ['name' => 'نورا خالد', 'phone' => '+965 45678901', 'email' => 'nora@example.com', 'city' => 'الفنطاس', 'gender' => 'female', 'is_active' => 1, 'total_visits' => 8, 'total_spent' => 650.000, 'loyalty_points' => 65],
            ['name' => 'ليلى سالم', 'phone' => '+965 56789012', 'email' => 'laila@example.com', 'city' => 'الجهراء', 'gender' => 'female', 'is_active' => 1, 'total_visits' => 5, 'total_spent' => 450.000, 'loyalty_points' => 45],
        ];

        foreach ($customers_data as $customer) {
            $wpdb->insert($customers_table, $customer);
        }

        // 4. Seed Products
        $products_table = $wpdb->prefix . 'asmaa_products';
        $products_data = [
            ['name' => 'Shampoo', 'name_ar' => 'شامبو', 'sku' => 'SH-001', 'purchase_price' => 5.000, 'selling_price' => 12.000, 'stock_quantity' => 45, 'min_stock_level' => 10, 'category' => 'Hair Care', 'is_active' => 1],
            ['name' => 'Conditioner', 'name_ar' => 'بلسم', 'sku' => 'CO-001', 'purchase_price' => 5.500, 'selling_price' => 13.000, 'stock_quantity' => 38, 'min_stock_level' => 10, 'category' => 'Hair Care', 'is_active' => 1],
            ['name' => 'Face Cream', 'name_ar' => 'كريم الوجه', 'sku' => 'FC-001', 'purchase_price' => 8.000, 'selling_price' => 25.000, 'stock_quantity' => 22, 'min_stock_level' => 5, 'category' => 'Skincare', 'is_active' => 1],
            ['name' => 'Nail Polish', 'name_ar' => 'طلاء أظافر', 'sku' => 'NP-001', 'purchase_price' => 2.000, 'selling_price' => 8.000, 'stock_quantity' => 3, 'min_stock_level' => 5, 'category' => 'Nails', 'is_active' => 1],
            ['name' => 'Hair Mask', 'name_ar' => 'قناع شعر', 'sku' => 'HM-001', 'purchase_price' => 6.000, 'selling_price' => 18.000, 'stock_quantity' => 15, 'min_stock_level' => 5, 'category' => 'Hair Care', 'is_active' => 1],
        ];

        foreach ($products_data as $product) {
            $wpdb->insert($products_table, $product);
        }

        // 5. Seed Membership Plans
        $plans_table = $wpdb->prefix . 'asmaa_membership_plans';
        $plans_data = [
            ['name' => 'Basic Plan', 'name_ar' => 'الخطة الأساسية', 'price' => 50.000, 'duration_months' => 1, 'discount_percentage' => 10.00, 'free_services_count' => 5, 'priority_booking' => 1, 'is_active' => 1],
            ['name' => 'Premium Plan', 'name_ar' => 'الخطة المميزة', 'price' => 100.000, 'duration_months' => 1, 'discount_percentage' => 20.00, 'free_services_count' => 0, 'priority_booking' => 1, 'is_active' => 1],
            ['name' => 'Annual Plan', 'name_ar' => 'الخطة السنوية', 'price' => 500.000, 'duration_months' => 12, 'discount_percentage' => 25.00, 'free_services_count' => 0, 'priority_booking' => 1, 'is_active' => 1],
        ];

        foreach ($plans_data as $plan) {
            $wpdb->insert($plans_table, $plan);
        }

        // Get IDs for relationships
        $staff_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_staff ORDER BY id LIMIT 5");
        $service_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_services ORDER BY id LIMIT 8");
        $customer_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_customers ORDER BY id LIMIT 5");
        $product_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_products ORDER BY id LIMIT 5");
        $plan_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_membership_plans ORDER BY id LIMIT 3");

        // 6. Seed Bookings
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $bookings_data = [
            ['customer_id' => $customer_ids[0], 'service_id' => $service_ids[0], 'staff_id' => $staff_ids[0], 'booking_date' => date('Y-m-d', strtotime('+1 day')), 'booking_time' => '10:00:00', 'end_time' => '10:30:00', 'status' => 'confirmed', 'price' => 15.000, 'discount' => 0, 'final_price' => 15.000, 'notes' => 'First appointment'],
            ['customer_id' => $customer_ids[1], 'service_id' => $service_ids[1], 'staff_id' => $staff_ids[1], 'booking_date' => date('Y-m-d', strtotime('+2 days')), 'booking_time' => '14:00:00', 'end_time' => '16:00:00', 'status' => 'pending', 'price' => 85.000, 'discount' => 0, 'final_price' => 85.000, 'notes' => null],
            ['customer_id' => $customer_ids[2], 'service_id' => $service_ids[2], 'staff_id' => $staff_ids[2], 'booking_date' => date('Y-m-d', strtotime('-1 day')), 'booking_time' => '11:00:00', 'end_time' => '12:00:00', 'status' => 'completed', 'price' => 45.000, 'discount' => 0, 'final_price' => 45.000, 'notes' => null],
            ['customer_id' => $customer_ids[3], 'service_id' => $service_ids[3], 'staff_id' => $staff_ids[3], 'booking_date' => date('Y-m-d'), 'booking_time' => '15:00:00', 'end_time' => '15:45:00', 'status' => 'confirmed', 'price' => 25.000, 'discount' => 0, 'final_price' => 25.000, 'notes' => 'VIP customer'],
            ['customer_id' => $customer_ids[4], 'service_id' => $service_ids[4], 'staff_id' => $staff_ids[4], 'booking_date' => date('Y-m-d', strtotime('-3 days')), 'booking_time' => '16:00:00', 'end_time' => '17:30:00', 'status' => 'completed', 'price' => 60.000, 'discount' => 0, 'final_price' => 60.000, 'notes' => null],
        ];
        foreach ($bookings_data as $booking) {
            $wpdb->insert($bookings_table, $booking);
        }
        $booking_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_bookings ORDER BY id LIMIT 5");

        // 7. Seed Queue Tickets
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';
        $queue_data = [
            ['ticket_number' => 'T-001', 'customer_id' => $customer_ids[0], 'service_id' => $service_ids[0], 'staff_id' => $staff_ids[0], 'status' => 'waiting', 'check_in_at' => date('Y-m-d H:i:s'), 'called_at' => null, 'serving_started_at' => null, 'completed_at' => null],
            ['ticket_number' => 'T-002', 'customer_id' => $customer_ids[1], 'service_id' => $service_ids[1], 'staff_id' => $staff_ids[1], 'status' => 'called', 'check_in_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')), 'called_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')), 'serving_started_at' => null, 'completed_at' => null],
            ['ticket_number' => 'T-003', 'customer_id' => $customer_ids[2], 'service_id' => $service_ids[2], 'staff_id' => $staff_ids[2], 'status' => 'serving', 'check_in_at' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'called_at' => date('Y-m-d H:i:s', strtotime('-45 minutes')), 'serving_started_at' => date('Y-m-d H:i:s', strtotime('-40 minutes')), 'completed_at' => null],
        ];
        foreach ($queue_data as $ticket) {
            $wpdb->insert($queue_table, $ticket);
        }

        // 8. Seed Orders
        $orders_table = $wpdb->prefix . 'asmaa_orders';
        $orders_data = [
            ['order_number' => 'ORD-001', 'customer_id' => $customer_ids[0], 'staff_id' => $staff_ids[0], 'booking_id' => $booking_ids[2], 'order_date' => date('Y-m-d', strtotime('-1 day')), 'subtotal' => 45.000, 'discount' => 0, 'tax' => 0, 'total' => 45.000, 'status' => 'completed', 'payment_status' => 'paid', 'notes' => null],
            ['order_number' => 'ORD-002', 'customer_id' => $customer_ids[1], 'staff_id' => $staff_ids[1], 'booking_id' => null, 'order_date' => date('Y-m-d', strtotime('-2 days')), 'subtotal' => 150.000, 'discount' => 15.000, 'tax' => 0, 'total' => 135.000, 'status' => 'completed', 'payment_status' => 'paid', 'notes' => '10% discount applied'],
            ['order_number' => 'ORD-003', 'customer_id' => $customer_ids[2], 'staff_id' => $staff_ids[2], 'booking_id' => null, 'order_date' => date('Y-m-d'), 'subtotal' => 85.000, 'discount' => 0, 'tax' => 0, 'total' => 85.000, 'status' => 'pending', 'payment_status' => 'unpaid', 'notes' => null],
        ];
        foreach ($orders_data as $order) {
            $wpdb->insert($orders_table, $order);
        }
        $order_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_orders ORDER BY id LIMIT 3");

        // 9. Seed Order Items
        $order_items_table = $wpdb->prefix . 'asmaa_order_items';
        $order_items_data = [
            ['order_id' => $order_ids[0], 'item_type' => 'service', 'item_id' => $service_ids[2], 'item_name' => 'Facial Treatment', 'quantity' => 1, 'price' => 45.000, 'discount' => 0, 'total' => 45.000],
            ['order_id' => $order_ids[1], 'item_type' => 'service', 'item_id' => $service_ids[0], 'item_name' => 'Haircut', 'quantity' => 2, 'price' => 15.000, 'discount' => 0, 'total' => 30.000],
            ['order_id' => $order_ids[1], 'item_type' => 'service', 'item_id' => $service_ids[1], 'item_name' => 'Hair Coloring', 'quantity' => 1, 'price' => 85.000, 'discount' => 0, 'total' => 85.000],
            ['order_id' => $order_ids[1], 'item_type' => 'product', 'item_id' => $product_ids[0], 'item_name' => 'Shampoo', 'quantity' => 3, 'price' => 12.000, 'discount' => 0, 'total' => 36.000],
            ['order_id' => $order_ids[2], 'item_type' => 'service', 'item_id' => $service_ids[1], 'item_name' => 'Hair Coloring', 'quantity' => 1, 'price' => 85.000, 'discount' => 0, 'total' => 85.000],
        ];
        foreach ($order_items_data as $item) {
            $wpdb->insert($order_items_table, $item);
        }

        // 10. Seed Invoices
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $invoices_data = [
            ['invoice_number' => 'INV-001', 'order_id' => $order_ids[0], 'customer_id' => $customer_ids[0], 'issue_date' => date('Y-m-d', strtotime('-1 day')), 'due_date' => date('Y-m-d', strtotime('-1 day')), 'subtotal' => 45.000, 'discount' => 0, 'tax' => 0, 'total' => 45.000, 'paid_amount' => 45.000, 'due_amount' => 0, 'status' => 'paid', 'notes' => null],
            ['invoice_number' => 'INV-002', 'order_id' => $order_ids[1], 'customer_id' => $customer_ids[1], 'issue_date' => date('Y-m-d', strtotime('-2 days')), 'due_date' => date('Y-m-d', strtotime('-2 days')), 'subtotal' => 150.000, 'discount' => 15.000, 'tax' => 0, 'total' => 135.000, 'paid_amount' => 135.000, 'due_amount' => 0, 'status' => 'paid', 'notes' => '10% discount'],
            ['invoice_number' => 'INV-003', 'order_id' => $order_ids[2], 'customer_id' => $customer_ids[2], 'issue_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+7 days')), 'subtotal' => 85.000, 'discount' => 0, 'tax' => 0, 'total' => 85.000, 'paid_amount' => 0, 'due_amount' => 85.000, 'status' => 'sent', 'notes' => null],
        ];
        foreach ($invoices_data as $invoice) {
            $wpdb->insert($invoices_table, $invoice);
        }
        $invoice_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_invoices ORDER BY id LIMIT 3");

        // 11. Seed Invoice Items
        $invoice_items_table = $wpdb->prefix . 'asmaa_invoice_items';
        $invoice_items_data = [
            ['invoice_id' => $invoice_ids[0], 'item_type' => 'service', 'item_id' => $service_ids[2], 'description' => 'Facial Treatment', 'quantity' => 1, 'price' => 45.000, 'discount' => 0, 'total' => 45.000],
            ['invoice_id' => $invoice_ids[1], 'item_type' => 'service', 'item_id' => $service_ids[0], 'description' => 'Haircut', 'quantity' => 2, 'price' => 15.000, 'discount' => 0, 'total' => 30.000],
            ['invoice_id' => $invoice_ids[1], 'item_type' => 'service', 'item_id' => $service_ids[1], 'description' => 'Hair Coloring', 'quantity' => 1, 'price' => 85.000, 'discount' => 0, 'total' => 85.000],
            ['invoice_id' => $invoice_ids[1], 'item_type' => 'product', 'item_id' => $product_ids[0], 'description' => 'Shampoo', 'quantity' => 3, 'price' => 12.000, 'discount' => 0, 'total' => 36.000],
            ['invoice_id' => $invoice_ids[2], 'item_type' => 'service', 'item_id' => $service_ids[1], 'description' => 'Hair Coloring', 'quantity' => 1, 'price' => 85.000, 'discount' => 0, 'total' => 85.000],
        ];
        foreach ($invoice_items_data as $item) {
            $wpdb->insert($invoice_items_table, $item);
        }

        // 12. Seed Payments
        $payments_table = $wpdb->prefix . 'asmaa_payments';
        $payments_data = [
            ['payment_number' => 'PAY-001', 'invoice_id' => $invoice_ids[0], 'customer_id' => $customer_ids[0], 'amount' => 45.000, 'payment_method' => 'cash', 'payment_date' => date('Y-m-d', strtotime('-1 day')), 'status' => 'completed', 'reference_number' => null, 'notes' => null],
            ['payment_number' => 'PAY-002', 'invoice_id' => $invoice_ids[1], 'customer_id' => $customer_ids[1], 'amount' => 135.000, 'payment_method' => 'card', 'payment_date' => date('Y-m-d', strtotime('-2 days')), 'status' => 'completed', 'reference_number' => 'CARD-12345', 'notes' => 'Visa payment'],
        ];
        foreach ($payments_data as $payment) {
            $wpdb->insert($payments_table, $payment);
        }

        // 13. Seed Loyalty Transactions
        $loyalty_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        $loyalty_data = [
            ['customer_id' => $customer_ids[0], 'transaction_type' => 'earned', 'points' => 150, 'points_value' => 15.000, 'order_id' => $order_ids[0], 'description' => 'Purchase reward', 'transaction_date' => date('Y-m-d', strtotime('-1 day')), 'expires_at' => date('Y-m-d', strtotime('+1 year')), 'status' => 'active'],
            ['customer_id' => $customer_ids[1], 'transaction_type' => 'earned', 'points' => 200, 'points_value' => 20.000, 'order_id' => $order_ids[1], 'description' => 'Purchase reward', 'transaction_date' => date('Y-m-d', strtotime('-2 days')), 'expires_at' => date('Y-m-d', strtotime('+1 year')), 'status' => 'active'],
            ['customer_id' => $customer_ids[0], 'transaction_type' => 'redeemed', 'points' => -50, 'points_value' => 5.000, 'order_id' => null, 'description' => 'Discount redemption', 'transaction_date' => date('Y-m-d', strtotime('-5 days')), 'expires_at' => null, 'status' => 'used'],
        ];
        foreach ($loyalty_data as $transaction) {
            $wpdb->insert($loyalty_table, $transaction);
        }

        // 14. Seed Customer Memberships
        $memberships_table = $wpdb->prefix . 'asmaa_customer_memberships';
        $memberships_data = [
            ['customer_id' => $customer_ids[0], 'membership_plan_id' => $plan_ids[0], 'start_date' => date('Y-m-d', strtotime('-10 days')), 'end_date' => date('Y-m-d', strtotime('+20 days')), 'status' => 'active', 'auto_renew' => 1, 'services_used' => 3],
            ['customer_id' => $customer_ids[1], 'membership_plan_id' => $plan_ids[1], 'start_date' => date('Y-m-d', strtotime('-5 days')), 'end_date' => date('Y-m-d', strtotime('+25 days')), 'status' => 'active', 'auto_renew' => 1, 'services_used' => 8],
            ['customer_id' => $customer_ids[2], 'membership_plan_id' => $plan_ids[0], 'start_date' => date('Y-m-d', strtotime('-60 days')), 'end_date' => date('Y-m-d', strtotime('-30 days')), 'status' => 'expired', 'auto_renew' => 0, 'services_used' => 5],
        ];
        foreach ($memberships_data as $membership) {
            $wpdb->insert($memberships_table, $membership);
        }
        $membership_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_customer_memberships ORDER BY id LIMIT 3");

        // 15. Seed Membership Service Usage
        $usage_table = $wpdb->prefix . 'asmaa_membership_service_usage';
        $usage_data = [
            ['customer_membership_id' => $membership_ids[0], 'service_id' => $service_ids[0], 'booking_id' => $booking_ids[2], 'used_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
            ['customer_membership_id' => $membership_ids[0], 'service_id' => $service_ids[2], 'booking_id' => $booking_ids[2], 'used_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
            ['customer_membership_id' => $membership_ids[1], 'service_id' => $service_ids[1], 'booking_id' => null, 'used_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
        ];
        foreach ($usage_data as $usage) {
            $wpdb->insert($usage_table, $usage);
        }

        // 16. Seed Inventory Movements
        $movements_table = $wpdb->prefix . 'asmaa_inventory_movements';
        $movements_data = [
            ['product_id' => $product_ids[0], 'movement_type' => 'purchase', 'quantity' => 50, 'before_quantity' => 45, 'after_quantity' => 95, 'unit_cost' => 5.000, 'total_cost' => 250.000, 'reference_type' => null, 'reference_id' => null, 'notes' => 'Initial stock', 'movement_date' => date('Y-m-d', strtotime('-30 days'))],
            ['product_id' => $product_ids[0], 'movement_type' => 'sale', 'quantity' => -3, 'before_quantity' => 95, 'after_quantity' => 92, 'unit_cost' => 5.000, 'total_cost' => 15.000, 'reference_type' => 'order', 'reference_id' => $order_ids[1], 'notes' => 'Sold with order', 'movement_date' => date('Y-m-d', strtotime('-2 days'))],
            ['product_id' => $product_ids[3], 'movement_type' => 'purchase', 'quantity' => 20, 'before_quantity' => 3, 'after_quantity' => 23, 'unit_cost' => 2.000, 'total_cost' => 40.000, 'reference_type' => null, 'reference_id' => null, 'notes' => 'Restock', 'movement_date' => date('Y-m-d', strtotime('-1 day'))],
        ];
        foreach ($movements_data as $movement) {
            $wpdb->insert($movements_table, $movement);
        }

        // 17. Seed Staff Ratings
        $ratings_table = $wpdb->prefix . 'asmaa_staff_ratings';
        $ratings_data = [
            ['staff_id' => $staff_ids[0], 'customer_id' => $customer_ids[0], 'booking_id' => $booking_ids[2], 'rating' => 5, 'comment' => 'Excellent service!'],
            ['staff_id' => $staff_ids[1], 'customer_id' => $customer_ids[1], 'booking_id' => null, 'rating' => 4, 'comment' => 'Very good'],
            ['staff_id' => $staff_ids[2], 'customer_id' => $customer_ids[2], 'booking_id' => $booking_ids[2], 'rating' => 5, 'comment' => 'Professional and friendly'],
        ];
        foreach ($ratings_data as $rating) {
            $wpdb->insert($ratings_table, $rating);
        }

        // 18. Seed Staff Commissions
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';
        $commissions_data = [
            ['staff_id' => $staff_ids[0], 'order_id' => $order_ids[0], 'base_amount' => 45.000, 'commission_rate' => 15.00, 'commission_amount' => 6.750, 'rating_bonus' => 2.000, 'final_amount' => 8.750, 'status' => 'approved', 'calculated_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'approved_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'paid_at' => null],
            ['staff_id' => $staff_ids[1], 'order_id' => $order_ids[1], 'base_amount' => 135.000, 'commission_rate' => 15.00, 'commission_amount' => 20.250, 'rating_bonus' => 1.500, 'final_amount' => 21.750, 'status' => 'paid', 'calculated_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'approved_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'paid_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
        ];
        foreach ($commissions_data as $commission) {
            $wpdb->insert($commissions_table, $commission);
        }

        // 19. Seed Commission Settings
        $settings_table = $wpdb->prefix . 'asmaa_commission_settings';
        $settings_data = [
            ['staff_id' => null, 'service_id' => null, 'commission_type' => 'percentage', 'commission_value' => 15.00, 'rating_bonus_per_star' => 0.500, 'is_active' => 1],
            ['staff_id' => $staff_ids[0], 'service_id' => null, 'commission_type' => 'percentage', 'commission_value' => 20.00, 'rating_bonus_per_star' => 1.000, 'is_active' => 1],
        ];
        foreach ($settings_data as $setting) {
            $wpdb->insert($settings_table, $setting);
        }

        // 20. Seed POS Sessions
        $pos_table = $wpdb->prefix . 'asmaa_pos_sessions';
        $pos_data = [
            ['staff_id' => $staff_ids[0], 'opening_balance' => 100.000, 'closing_balance' => 280.000, 'total_sales' => 180.000, 'total_cash' => 180.000, 'total_card' => 0, 'total_other' => 0, 'opened_at' => date('Y-m-d H:i:s', strtotime('-2 days 08:00')), 'closed_at' => date('Y-m-d H:i:s', strtotime('-2 days 17:00')), 'notes' => 'Regular day'],
            ['staff_id' => $staff_ids[1], 'opening_balance' => 100.000, 'closing_balance' => null, 'total_sales' => 0, 'total_cash' => 0, 'total_card' => 0, 'total_other' => 0, 'opened_at' => date('Y-m-d H:i:s', strtotime('08:00')), 'closed_at' => null, 'notes' => 'Current session'],
        ];
        foreach ($pos_data as $pos) {
            $wpdb->insert($pos_table, $pos);
        }

        // 21. Seed Worker Calls
        $worker_calls_table = $wpdb->prefix . 'asmaa_worker_calls';
        $queue_ticket_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}asmaa_queue_tickets ORDER BY id LIMIT 3");
        $worker_calls_data = [
            ['staff_id' => $staff_ids[0], 'ticket_id' => $queue_ticket_ids[0], 'customer_name' => 'سارة أحمد', 'status' => 'pending', 'called_at' => null, 'accepted_at' => null, 'completed_at' => null, 'postponed_at' => null, 'sms_sent' => 0, 'notes' => null],
            ['staff_id' => $staff_ids[1], 'ticket_id' => $queue_ticket_ids[1], 'customer_name' => 'فاطمة محمد', 'status' => 'accepted', 'called_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')), 'accepted_at' => date('Y-m-d H:i:s', strtotime('-4 minutes')), 'completed_at' => null, 'postponed_at' => null, 'sms_sent' => 1, 'notes' => null],
            ['staff_id' => $staff_ids[2], 'ticket_id' => $queue_ticket_ids[2], 'customer_name' => 'مريم علي', 'status' => 'completed', 'called_at' => date('Y-m-d H:i:s', strtotime('-45 minutes')), 'accepted_at' => date('Y-m-d H:i:s', strtotime('-40 minutes')), 'completed_at' => date('Y-m-d H:i:s', strtotime('-10 minutes')), 'postponed_at' => null, 'sms_sent' => 1, 'notes' => 'Service completed successfully'],
        ];
        foreach ($worker_calls_data as $call) {
            $wpdb->insert($worker_calls_table, $call);
        }

        // 22. Seed Membership Extensions
        $extensions_table = $wpdb->prefix . 'asmaa_membership_extensions';
        $extensions_data = [
            ['customer_membership_id' => $membership_ids[0], 'extended_by_months' => 1, 'amount_paid' => 50.000, 'extended_at' => date('Y-m-d H:i:s', strtotime('-5 days')), 'notes' => 'Manual extension'],
            ['customer_membership_id' => $membership_ids[1], 'extended_by_months' => 3, 'amount_paid' => 270.000, 'extended_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'notes' => 'Quarterly extension with 10% discount'],
        ];
        foreach ($extensions_data as $extension) {
            $wpdb->insert($extensions_table, $extension);
        }
    }
}

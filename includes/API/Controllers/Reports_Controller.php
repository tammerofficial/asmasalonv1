<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

class Reports_Controller extends Base_Controller
{
    protected string $rest_base = 'reports';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/overview', [
            ['methods' => 'GET', 'callback' => [$this, 'get_overview'], 'permission_callback' => $this->permission_callback('asmaa_reports_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/sales', [
            ['methods' => 'GET', 'callback' => [$this, 'get_sales_report'], 'permission_callback' => $this->permission_callback('asmaa_reports_sales')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/bookings', [
            ['methods' => 'GET', 'callback' => [$this, 'get_bookings_report'], 'permission_callback' => $this->permission_callback('asmaa_reports_bookings')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/customers', [
            ['methods' => 'GET', 'callback' => [$this, 'get_customers_report'], 'permission_callback' => $this->permission_callback('asmaa_reports_customers')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/staff', [
            ['methods' => 'GET', 'callback' => [$this, 'get_staff_report'], 'permission_callback' => $this->permission_callback('asmaa_reports_staff')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/dashboard', [
            ['methods' => 'GET', 'callback' => [$this, 'get_dashboard_stats'], 'permission_callback' => $this->permission_callback('asmaa_reports_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/daily-sales', [
            ['methods' => 'GET', 'callback' => [$this, 'get_daily_sales'], 'permission_callback' => $this->permission_callback('asmaa_reports_sales')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/staff-performance', [
            ['methods' => 'GET', 'callback' => [$this, 'get_staff_performance'], 'permission_callback' => $this->permission_callback('asmaa_reports_staff')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/queue-stats', [
            ['methods' => 'GET', 'callback' => [$this, 'get_queue_stats'], 'permission_callback' => $this->permission_callback('asmaa_reports_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/commissions', [
            ['methods' => 'GET', 'callback' => [$this, 'get_commissions_report'], 'permission_callback' => $this->permission_callback('asmaa_reports_view')],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/booking-efficiency', [
            ['methods' => 'GET', 'callback' => [$this, 'get_booking_efficiency'], 'permission_callback' => $this->permission_callback('asmaa_reports_view')],
        ]);
    }

    private function get_grouping(string $start_date, string $end_date): array
    {
        $start_ts = strtotime($start_date . ' 00:00:00');
        $end_ts = strtotime($end_date . ' 23:59:59');
        $days = 0;
        if ($start_ts && $end_ts && $end_ts >= $start_ts) {
            $days = (int) floor(($end_ts - $start_ts) / 86400) + 1;
        }

        // If range is large, group by month for performance and readability
        if ($days > 120) {
            return [
                'group' => 'month',
                'format' => '%Y-%m',
                'days' => $days,
            ];
        }

        return [
            'group' => 'day',
            'format' => '%Y-%m-%d',
            'days' => $days,
        ];
    }

    public function get_overview(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $start_date = sanitize_text_field((string) ($request->get_param('start_date') ?: date('Y-m-01')));
        $end_date = sanitize_text_field((string) ($request->get_param('end_date') ?: date('Y-m-d')));

        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }
        
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $payments_table = $wpdb->prefix . 'asmaa_payments';
        $extended_customers_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $loyalty_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';

        $grouping = $this->get_grouping($start_date, $end_date);
        $fmt = $grouping['format'];

        // 1) Sales trend (orders + revenue + paid) - from WooCommerce
        $wc_orders = wc_get_orders([
            'limit' => 200, // Reasonable limit for trend analysis
            'status' => 'any',
            'date_created' => strtotime($start_date) . '...' . strtotime($end_date . ' 23:59:59'),
            'return' => 'objects',
        ]);
        
        // Group by period
        $sales_by_period = [];
        foreach ($wc_orders as $order) {
            $date = $order->get_date_created()->date('Y-m-d');
            $period = $grouping['group'] === 'month' 
                ? date('Y-m', strtotime($date))
                : $date;
            
            if (!isset($sales_by_period[$period])) {
                $sales_by_period[$period] = [
                    'orders' => 0,
                    'revenue' => 0.0,
                    'paid' => 0.0,
                ];
            }
            
            $total = (float) $order->get_total();
            $sales_by_period[$period]['orders']++;
            $sales_by_period[$period]['revenue'] += $total;
            if ($order->is_paid()) {
                $sales_by_period[$period]['paid'] += $total;
            }
        }
        
        // Convert to arrays
        $sales_labels = [];
        $sales_orders = [];
        $sales_revenue = [];
        $sales_paid = [];
        ksort($sales_by_period);
        foreach ($sales_by_period as $period => $data) {
            $sales_labels[] = $period;
            $sales_orders[] = $data['orders'];
            $sales_revenue[] = $data['revenue'];
            $sales_paid[] = $data['paid'];
        }
        $sales_labels = [];
        $sales_orders = [];
        $sales_revenue = [];
        $sales_paid = [];
        foreach ($sales_rows as $r) {
            $sales_labels[] = (string) ($r->p ?? '');
            $sales_orders[] = (int) ($r->orders ?? 0);
            $sales_revenue[] = (float) ($r->revenue ?? 0);
            $sales_paid[] = (float) ($r->paid ?? 0);
        }

        // 2) Bookings status distribution (range)
        $booking_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS c
             FROM {$bookings_table}
             WHERE booking_date BETWEEN %s AND %s
               AND deleted_at IS NULL
             GROUP BY status",
            $start_date,
            $end_date
        ));
        $bookings_status = [];
        foreach ($booking_rows as $r) {
            $bookings_status[(string) ($r->status ?? '')] = (int) ($r->c ?? 0);
        }

        // 3) Invoices status distribution (range) + totals
        $invoice_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT status,
                    COUNT(*) AS c,
                    COALESCE(SUM(total), 0) AS total,
                    COALESCE(SUM(paid_amount), 0) AS paid
             FROM {$invoices_table}
             WHERE deleted_at IS NULL
               AND issue_date BETWEEN %s AND %s
             GROUP BY status",
            $start_date,
            $end_date
        ));
        $invoices_status = [];
        $invoices_total_amount = 0.0;
        $invoices_paid_amount = 0.0;
        foreach ($invoice_rows as $r) {
            $status = (string) ($r->status ?? '');
            $invoices_status[$status] = (int) ($r->c ?? 0);
            $invoices_total_amount += (float) ($r->total ?? 0);
            $invoices_paid_amount += (float) ($r->paid ?? 0);
        }

        // 4) Payments methods (range)
        $payment_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT payment_method,
                    COUNT(*) AS c,
                    COALESCE(SUM(amount), 0) AS total
             FROM {$payments_table}
             WHERE payment_date BETWEEN %s AND %s
               AND status = 'completed'
               AND deleted_at IS NULL
             GROUP BY payment_method
             ORDER BY total DESC",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        $payment_labels = [];
        $payment_totals = [];
        $payment_counts = [];
        $payments_total_amount = 0.0;
        $payments_total_count = 0;
        foreach ($payment_rows as $r) {
            $payment_labels[] = (string) ($r->payment_method ?? 'unknown');
            $payment_totals[] = (float) ($r->total ?? 0);
            $payment_counts[] = (int) ($r->c ?? 0);
            $payments_total_amount += (float) ($r->total ?? 0);
            $payments_total_count += (int) ($r->c ?? 0);
        }

        // 5) Top services/products from WooCommerce orders (range)
        $top_services_data = [];
        $top_products_data = [];
        
        foreach ($wc_orders as $order) {
            // Get fees (services)
            foreach ($order->get_fees() as $fee) {
                $name = $fee->get_name();
                $total = (float) $fee->get_total();
                
                if (!isset($top_services_data[$name])) {
                    $top_services_data[$name] = ['qty' => 0, 'revenue' => 0.0];
                }
                $top_services_data[$name]['qty']++;
                $top_services_data[$name]['revenue'] += $total;
            }
            
            // Get products
            foreach ($order->get_items() as $item) {
                $name = $item->get_name();
                $qty = (int) $item->get_quantity();
                $total = (float) $item->get_total();
                
                if (!isset($top_products_data[$name])) {
                    $top_products_data[$name] = ['qty' => 0, 'revenue' => 0.0];
                }
                $top_products_data[$name]['qty'] += $qty;
                $top_products_data[$name]['revenue'] += $total;
            }
        }
        
        // Sort and limit
        uasort($top_services_data, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        uasort($top_products_data, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        
        $top_services_labels = [];
        $top_services_revenue = [];
        foreach (array_slice($top_services_data, 0, 10, true) as $name => $data) {
            $top_services_labels[] = $name;
            $top_services_revenue[] = $data['revenue'];
        }
        
        $top_products_labels = [];
        $top_products_revenue = [];
        foreach (array_slice($top_products_data, 0, 10, true) as $name => $data) {
            $top_products_labels[] = $name;
            $top_products_revenue[] = $data['revenue'];
        }

        // 6) Loyalty trend (earned vs redeemed)
        $loyalty_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(created_at, %s) AS p,
                COALESCE(SUM(CASE WHEN type = 'earned' THEN points ELSE 0 END), 0) AS earned_points,
                ABS(COALESCE(SUM(CASE WHEN type = 'redeemed' THEN points ELSE 0 END), 0)) AS redeemed_points
             FROM {$loyalty_table}
             WHERE created_at BETWEEN %s AND %s
             GROUP BY p
             ORDER BY p ASC",
            $fmt,
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        $loyalty_labels = [];
        $loyalty_earned = [];
        $loyalty_redeemed = [];
        $loyalty_total_earned = 0;
        $loyalty_total_redeemed = 0;
        foreach ($loyalty_rows as $r) {
            $loyalty_labels[] = (string) ($r->p ?? '');
            $earned = (int) ($r->earned_points ?? 0);
            $redeemed = (int) ($r->redeemed_points ?? 0);
            $loyalty_earned[] = $earned;
            $loyalty_redeemed[] = $redeemed;
            $loyalty_total_earned += $earned;
            $loyalty_total_redeemed += $redeemed;
        }

        // 7) New customers trend (from WordPress user registration)
        $customers_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(u.user_registered, %s) AS p,
                COUNT(DISTINCT u.ID) AS c
             FROM {$wpdb->users} u
             INNER JOIN {$extended_customers_table} ext ON ext.wc_customer_id = u.ID
             WHERE u.user_registered BETWEEN %s AND %s
             GROUP BY p
             ORDER BY p ASC",
            $fmt,
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        $customers_labels = [];
        $customers_counts = [];
        $customers_new_total = 0;
        foreach ($customers_rows as $r) {
            $customers_labels[] = (string) ($r->p ?? '');
            $c = (int) ($r->c ?? 0);
            $customers_counts[] = $c;
            $customers_new_total += $c;
        }

        // 8) Commissions by staff (range)
        $comm_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID as id,
                    u.display_name as name,
                    COALESCE(SUM(sc.final_amount), 0) AS total_commission
             FROM {$commissions_table} sc
             JOIN {$wpdb->users} u ON u.ID = sc.wp_user_id
             WHERE sc.created_at BETWEEN %s AND %s
             GROUP BY u.ID, u.display_name
             ORDER BY total_commission DESC
             LIMIT 12",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        $comm_labels = [];
        $comm_totals = [];
        $comm_total_amount = 0.0;
        foreach ($comm_rows as $r) {
            $comm_labels[] = (string) ($r->name ?? '');
            $amt = (float) ($r->total_commission ?? 0);
            $comm_totals[] = $amt;
            $comm_total_amount += $amt;
        }

        // Queue status (range)
        $queue_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS c
             FROM {$queue_table}
             WHERE DATE(created_at) BETWEEN %s AND %s
               AND deleted_at IS NULL
             GROUP BY status",
            $start_date,
            $end_date
        ));
        $queue_status = [];
        foreach ($queue_rows as $r) {
            $queue_status[(string) ($r->status ?? '')] = (int) ($r->c ?? 0);
        }

        $summary_orders = array_sum($sales_orders);
        $summary_revenue = array_sum($sales_revenue);
        $summary_paid_revenue = array_sum($sales_paid);
        $summary_avg_order = $summary_orders > 0 ? ($summary_revenue / $summary_orders) : 0;

        return $this->success_response([
            'period' => [
                'start' => $start_date,
                'end' => $end_date,
                'group' => $grouping['group'],
                'days' => $grouping['days'],
            ],
            'summary' => [
                'orders' => (int) $summary_orders,
                'revenue' => (float) $summary_revenue,
                'paid_revenue' => (float) $summary_paid_revenue,
                'avg_order' => (float) $summary_avg_order,
                'bookings' => (int) array_sum(array_values($bookings_status)),
                'invoices_total' => (float) $invoices_total_amount,
                'invoices_paid' => (float) $invoices_paid_amount,
                'payments_total' => (float) $payments_total_amount,
                'payments_count' => (int) $payments_total_count,
                'new_customers' => (int) $customers_new_total,
                'loyalty_earned' => (int) $loyalty_total_earned,
                'loyalty_redeemed' => (int) $loyalty_total_redeemed,
                'commissions_total' => (float) $comm_total_amount,
            ],
            'charts' => [
                'sales' => [
                    'labels' => $sales_labels,
                    'orders' => $sales_orders,
                    'revenue' => $sales_revenue,
                    'paid' => $sales_paid,
                ],
                'bookings_status' => $bookings_status,
                'invoices_status' => $invoices_status,
                'payments_methods' => [
                    'labels' => $payment_labels,
                    'totals' => $payment_totals,
                    'counts' => $payment_counts,
                ],
                'top_services' => [
                    'labels' => $top_services_labels,
                    'revenue' => $top_services_revenue,
                ],
                'top_products' => [
                    'labels' => $top_products_labels,
                    'revenue' => $top_products_revenue,
                ],
                'loyalty' => [
                    'labels' => $loyalty_labels,
                    'earned' => $loyalty_earned,
                    'redeemed' => $loyalty_redeemed,
                ],
                'new_customers' => [
                    'labels' => $customers_labels,
                    'counts' => $customers_counts,
                ],
                'commissions_by_staff' => [
                    'labels' => $comm_labels,
                    'totals' => $comm_totals,
                ],
                'queue_status' => $queue_status,
            ],
        ]);
    }

    public function get_sales_report(WP_REST_Request $request): WP_REST_Response
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }
        
        $start_date = $request->get_param('start_date') ?: date('Y-m-01');
        $end_date = $request->get_param('end_date') ?: date('Y-m-d');
        
        // Get WooCommerce orders
        $wc_orders = wc_get_orders([
            'limit' => 500, // Reasonable limit
            'status' => 'any',
            'date_created' => strtotime($start_date) . '...' . strtotime($end_date . ' 23:59:59'),
            'return' => 'objects',
        ]);
        
        // Group by date
        $results_by_date = [];
        foreach ($wc_orders as $order) {
            $date = $order->get_date_created()->date('Y-m-d');
            $total = (float) $order->get_total();
            $is_paid = $order->is_paid();
            
            if (!isset($results_by_date[$date])) {
                $results_by_date[$date] = [
                    'date' => $date,
                    'total_orders' => 0,
                    'total_revenue' => 0.0,
                    'paid_amount' => 0.0,
                ];
            }
            
            $results_by_date[$date]['total_orders']++;
            $results_by_date[$date]['total_revenue'] += $total;
            if ($is_paid) {
                $results_by_date[$date]['paid_amount'] += $total;
            }
        }
        
        // Convert to array and sort
        $results = array_values($results_by_date);
        usort($results, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return $this->success_response([
            'period' => ['start' => $start_date, 'end' => $end_date],
            'data' => $results,
        ]);
    }

    public function get_bookings_report(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        
        $start_date = $request->get_param('start_date') ?: date('Y-m-01');
        $end_date = $request->get_param('end_date') ?: date('Y-m-d');
        
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                status,
                COUNT(*) as count
            FROM {$bookings_table}
            WHERE booking_date BETWEEN %s AND %s AND deleted_at IS NULL
            GROUP BY status",
            $start_date,
            $end_date
        ));

        return $this->success_response([
            'period' => ['start' => $start_date, 'end' => $end_date],
            'data' => $results,
        ]);
    }

    public function get_customers_report(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        
        $extended_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        
        // Get total customers (WordPress users with 'customer' role)
        $user_counts = count_users();
        $total = $user_counts['avail_roles']['customer'] ?? 0;
        
        // Active customers
        $active = $total;
        
        // New this month (from WordPress user registration)
        $new_this_month = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT u.ID) 
             FROM {$wpdb->users} u
             INNER JOIN {$extended_table} ext ON ext.wc_customer_id = u.ID
             WHERE MONTH(u.user_registered) = %d AND YEAR(u.user_registered) = %d",
            date('n'),
            date('Y')
        ));
        
        // Top customers by total_spent
        $top_customers = $wpdb->get_results(
            "SELECT 
                u.ID as id,
                u.display_name as name,
                ext.total_spent,
                ext.total_visits
             FROM {$wpdb->users} u
             INNER JOIN {$extended_table} ext ON ext.wc_customer_id = u.ID
             ORDER BY ext.total_spent DESC
             LIMIT 10"
        );
        
        $stats = [
            'total' => $total,
            'active' => $active,
            'new_this_month' => $new_this_month,
            'top_customers' => $top_customers,
        ];

        return $this->success_response($stats);
    }

    public function get_staff_report(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        
        $extended_table = $wpdb->prefix . 'asmaa_staff_extended_data';
        
        $results = $wpdb->get_results(
            "SELECT u.ID as id, u.display_name as name, ext.total_services, ext.total_revenue, ext.rating, ext.total_ratings 
             FROM {$wpdb->users} u
             INNER JOIN {$extended_table} ext ON ext.wp_user_id = u.ID
             WHERE ext.is_active = 1 
             ORDER BY ext.total_revenue DESC"
        );

        return $this->success_response($results);
    }

    public function get_dashboard_stats(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }
        
        $extended_customers_table = $wpdb->prefix . 'asmaa_customer_extended_data';
        $extended_staff_table = $wpdb->prefix . 'asmaa_staff_extended_data';
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $services_table = $wpdb->prefix . 'asmaa_services';
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';
        
        $today = date('Y-m-d');
        $this_month_start = date('Y-m-01');
        $last_7_start = date('Y-m-d', strtotime($today . ' -6 days'));
        $last_30_start = date('Y-m-d', strtotime($today . ' -29 days'));

        // Sales stats (for Sales page) - from WooCommerce
        $today_res = wc_get_orders([
            'limit' => 100, // Reasonable for today's orders
            'status' => 'any',
            'date_created' => strtotime($today) . '...' . strtotime($today . ' 23:59:59'),
            'paginate' => true,
        ]);
        $today_orders = (int) ($today_res->total ?? 0);
        $today_sales = 0.0;
        if (isset($today_res->orders)) {
            foreach ($today_res->orders as $order) {
                $today_sales += (float) $order->get_total();
            }
        }

        $month_res = wc_get_orders([
            'limit' => 100, // Limit objects for performance
            'status' => 'any',
            'date_created' => strtotime($this_month_start) . '...' . time(),
            'paginate' => true,
        ]);
        $month_orders = (int) ($month_res->total ?? 0);
        $month_sales = 0.0;
        // If more than 100 orders, we'd ideally use SQL for revenue, but for now we'll just sum what we got
        if (isset($month_res->orders)) {
            foreach ($month_res->orders as $order) {
                $month_sales += (float) $order->get_total();
            }
        }
        $avg_order = $month_orders > 0 ? ($month_sales / $month_orders) : 0.0;

        $top_customer = $wpdb->get_row(
            "SELECT u.display_name as name, ext.total_spent
             FROM {$wpdb->users} u
             INNER JOIN {$extended_customers_table} ext ON ext.wc_customer_id = u.ID
             ORDER BY ext.total_spent DESC
             LIMIT 1"
        );
        
        // Faster counts
        $pending_res = wc_get_orders(['limit' => 1, 'status' => ['pending', 'processing'], 'paginate' => true]);
        $all_orders_res = wc_get_orders(['limit' => 1, 'status' => 'any', 'paginate' => true]);
        $user_counts = count_users();
        
        // Count staff from roles
        $staff_roles = [
            'asmaa_staff', 'asmaa_manager', 'asmaa_admin', 'asmaa_super_admin',
            'asmaa_accountant', 'asmaa_receptionist', 'asmaa_cashier',
            'administrator', 'editor', 'author',
            'huda_admin', 'huda_production', 'huda_tailor', 'huda_accountant',
            'workshop_supervisor', 'customer_service_employee'
        ];
        $total_staff = 0;
        foreach ($staff_roles as $role) {
            $total_staff += $user_counts['avail_roles'][$role] ?? 0;
        }
        
        // For active staff, we'll subtract those explicitly marked as inactive
        $inactive_staff_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$extended_staff_table} WHERE is_active = 0");
        $active_staff = max(0, $total_staff - $inactive_staff_count);

        $stats = [
            'customers' => (int) ($user_counts['avail_roles']['customer'] ?? 0),
            'bookingsToday' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$bookings_table} WHERE booking_date = %s AND deleted_at IS NULL",
                $today
            )),
            'monthlyRevenue' => (float) $month_sales, // Use the one calculated from WC
            'activeStaff' => $active_staff,
            'totalServices' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$services_table} WHERE is_active = 1 AND deleted_at IS NULL"),
            'pendingOrders' => (int) ($pending_res->total ?? 0),
            'unpaidInvoices' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$invoices_table} WHERE status IN ('sent', 'overdue', 'partial') AND deleted_at IS NULL"),
            'queueWaiting' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table} WHERE status IN ('waiting', 'called') AND deleted_at IS NULL"),

            // WooCommerce Integration Info
            'woocommerce' => [
                'active' => \AsmaaSalon\Services\WooCommerce_Integration_Service::is_woocommerce_active(),
                'products_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asmaa_product_extended_data"),
                'orders_count' => (int) ($all_orders_res->total ?? 0),
                'customers_count' => (int) ($user_counts['avail_roles']['customer'] ?? 0),
            ],

            // Sales page fields
            'today_sales' => $today_sales,
            'today_orders' => $today_orders,
            'month_sales' => $month_sales,
            'month_orders' => $month_orders,
            'avg_order' => $avg_order,
            'top_customer' => $top_customer ? (string) ($top_customer->name ?? '') : '',
            'top_customer_spent' => $top_customer ? (float) ($top_customer->total_spent ?? 0) : 0,
        ];

        // Charts data (safe additive fields)
        // 1) Sales last 7 days (revenue + orders) - from WooCommerce
        $last_7_res = wc_get_orders([
            'limit' => 200, // Reasonable for 7 days
            'status' => 'any',
            'date_created' => strtotime($last_7_start) . '...' . strtotime($today . ' 23:59:59'),
            'return' => 'objects',
        ]);
        
        $sales_map = [];
        if (is_array($last_7_res)) {
            foreach ($last_7_res as $order) {
                $d = $order->get_date_created()->date('Y-m-d');
                if (!isset($sales_map[$d])) {
                    $sales_map[$d] = ['orders' => 0, 'revenue' => 0.0];
                }
                $sales_map[$d]['orders']++;
                $sales_map[$d]['revenue'] += (float) $order->get_total();
            }
        }
        $sales_labels = [];
        $sales_orders = [];
        $sales_revenue = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime($last_7_start . " +{$i} days"));
            $sales_labels[] = $d;
            $sales_orders[] = (int) ($sales_map[$d]['orders'] ?? 0);
            $sales_revenue[] = (float) ($sales_map[$d]['revenue'] ?? 0);
        }

        // 2) Bookings status (last 30 days)
        $booking_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS c
             FROM {$bookings_table}
             WHERE booking_date BETWEEN %s AND %s
               AND deleted_at IS NULL
             GROUP BY status",
            $last_30_start,
            $today
        ));
        $booking_status = [
            'pending' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        foreach ($booking_rows as $r) {
            $k = (string) ($r->status ?? '');
            if ($k && array_key_exists($k, $booking_status)) {
                $booking_status[$k] = (int) ($r->c ?? 0);
            }
        }

        // 3) Invoice status (current snapshot)
        $invoice_rows = $wpdb->get_results(
            "SELECT status, COUNT(*) AS c
             FROM {$invoices_table}
             WHERE deleted_at IS NULL
             GROUP BY status"
        );
        $invoice_status = [];
        foreach ($invoice_rows as $r) {
            $invoice_status[(string) ($r->status ?? '')] = (int) ($r->c ?? 0);
        }

        $stats['charts'] = [
            'period' => [
                'sales_7d_start' => $last_7_start,
                'bookings_30d_start' => $last_30_start,
                'end' => $today,
            ],
            'sales_last_7_days' => [
                'labels' => $sales_labels,
                'orders' => $sales_orders,
                'revenue' => $sales_revenue,
            ],
            'bookings_status_last_30_days' => $booking_status,
            'invoices_status' => $invoice_status,
        ];

        return $this->success_response($stats);
    }

    public function get_daily_sales(WP_REST_Request $request): WP_REST_Response
    {
        if (!class_exists('WooCommerce')) {
            return $this->error_response(__('WooCommerce is required', 'asmaa-salon'), 500);
        }
        
        global $wpdb;
        $payments_table = $wpdb->prefix . 'asmaa_payments';

        $date = $request->get_param('date') ?: date('Y-m-d');

        // Get today's stats from WooCommerce
        $wc_res = wc_get_orders([
            'limit' => 200, // Reasonable for one day
            'status' => 'any',
            'date_created' => strtotime($date) . '...' . strtotime($date . ' 23:59:59'),
            'paginate' => true,
        ]);
        
        $total_orders = (int) ($wc_res->total ?? 0);
        $total_revenue = 0.0;
        if (isset($wc_res->orders)) {
            foreach ($wc_res->orders as $order) {
                $total_revenue += (float) $order->get_total();
            }
        }
        $avg_order_value = $total_orders > 0 ? ($total_revenue / $total_orders) : 0.0;
        
        $today_stats = (object) [
            'total_orders' => $total_orders,
            'total_revenue' => $total_revenue,
            'avg_order_value' => $avg_order_value,
        ];

        // Payment method breakdown
        $payment_methods = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                payment_method,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total
            FROM {$payments_table}
            WHERE DATE(payment_date) = %s AND status = 'completed' AND deleted_at IS NULL
            GROUP BY payment_method",
            $date
        ));

        // Yesterday comparison
        $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
        $yesterday_wc_orders = wc_get_orders([
            'limit' => -1,
            'status' => 'any',
            'date_created' => strtotime($yesterday) . '...' . strtotime($yesterday . ' 23:59:59'),
            'return' => 'objects',
        ]);
        $yesterday_revenue = 0.0;
        foreach ($yesterday_wc_orders as $order) {
            $yesterday_revenue += (float) $order->get_total();
        }

        // Last week same day
        $last_week = date('Y-m-d', strtotime($date . ' -7 days'));
        $last_week_wc_orders = wc_get_orders([
            'limit' => -1,
            'status' => 'any',
            'date_created' => strtotime($last_week) . '...' . strtotime($last_week . ' 23:59:59'),
            'return' => 'objects',
        ]);
        $last_week_revenue = 0.0;
        foreach ($last_week_wc_orders as $order) {
            $last_week_revenue += (float) $order->get_total();
        }

        $today_revenue = (float) ($today_stats->total_revenue ?? 0);

        return $this->success_response([
            'date' => $date,
            'total_orders' => (int) ($today_stats->total_orders ?? 0),
            'total_revenue' => $today_revenue,
            'avg_order_value' => (float) ($today_stats->avg_order_value ?? 0),
            'payment_methods' => $payment_methods,
            'comparison' => [
                'yesterday' => [
                    'revenue' => $yesterday_revenue,
                    'change_percent' => $yesterday_revenue > 0 ? round((($today_revenue - $yesterday_revenue) / $yesterday_revenue) * 100, 1) : 0,
                ],
                'last_week' => [
                    'revenue' => $last_week_revenue,
                    'change_percent' => $last_week_revenue > 0 ? round((($today_revenue - $last_week_revenue) / $last_week_revenue) * 100, 1) : 0,
                ],
            ],
        ]);
    }

    public function get_staff_performance(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $extended_staff_table = $wpdb->prefix . 'asmaa_staff_extended_data';
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';

        $date = $request->get_param('date') ?: date('Y-m-d');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');

        $date_filter = '';
        if ($start_date && $end_date) {
            $date_filter = $wpdb->prepare(" AND DATE(b.booking_date) BETWEEN %s AND %s", $start_date, $end_date);
        } elseif ($date) {
            $date_filter = $wpdb->prepare(" AND DATE(b.booking_date) = %s", $date);
        }

        $results = $wpdb->get_results(
            "SELECT 
                u.ID as id,
                u.display_name as name,
                COUNT(DISTINCT b.id) as services_count,
                COALESCE(SUM(b.final_price), 0) as revenue,
                ext.rating,
                COALESCE(SUM(sc.final_amount), 0) as total_commissions
            FROM {$wpdb->users} u
            INNER JOIN {$extended_staff_table} ext ON ext.wp_user_id = u.ID
            LEFT JOIN {$bookings_table} b ON ext.wp_user_id = b.wp_user_id AND b.status = 'completed' AND b.deleted_at IS NULL {$date_filter}
            LEFT JOIN {$commissions_table} sc ON ext.wp_user_id = sc.wp_user_id AND sc.status = 'pending'
            WHERE ext.is_active = 1
            GROUP BY u.ID, u.display_name, ext.rating
            ORDER BY revenue DESC"
        );

        return $this->success_response([
            'date' => $date,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data' => $results,
        ]);
    }

    public function get_queue_stats(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';

        $date = $request->get_param('date') ?: date('Y-m-d');

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                AVG(TIMESTAMPDIFF(MINUTE, check_in_at, called_at)) as avg_waiting_time,
                AVG(TIMESTAMPDIFF(MINUTE, serving_started_at, completed_at)) as avg_service_time
            FROM {$queue_table}
            WHERE DATE(created_at) = %s AND deleted_at IS NULL",
            $date
        ));

        return $this->success_response([
            'date' => $date,
            'total_tickets' => (int) ($stats->total_tickets ?? 0),
            'completed' => (int) ($stats->completed ?? 0),
            'cancelled' => (int) ($stats->cancelled ?? 0),
            'avg_waiting_time' => round((float) ($stats->avg_waiting_time ?? 0), 1),
            'avg_service_time' => round((float) ($stats->avg_service_time ?? 0), 1),
        ]);
    }

    public function get_commissions_report(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';

        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');
        $staff_id = $request->get_param('staff_id');

        $where = ["sc.status = 'pending'"];
        if ($start_date && $end_date) {
            $where[] = $wpdb->prepare("DATE_FORMAT(sc.created_at, '%%Y-%%m') BETWEEN %s AND %s", $start_date, $end_date);
        }
        if ($staff_id) {
            $where[] = $wpdb->prepare("sc.wp_user_id = %d", (int) $staff_id);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);

        $results = $wpdb->get_results(
            "SELECT 
                u.ID as staff_id,
                u.display_name as staff_name,
                COUNT(sc.id) as total_services,
                COALESCE(SUM(sc.base_amount), 0) as total_revenue,
                COALESCE(SUM(sc.commission_amount), 0) as base_commission,
                COALESCE(SUM(sc.rating_bonus), 0) as total_bonuses,
                COALESCE(SUM(sc.final_amount), 0) as total_commission
            FROM {$wpdb->users} u
            JOIN {$commissions_table} sc ON u.ID = sc.wp_user_id
            {$where_clause}
            GROUP BY u.ID, u.display_name
            ORDER BY total_commission DESC"
        );

        return $this->success_response([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data' => $results,
        ]);
    }

    /**
     * Get booking efficiency report
     * Compares booking scheduled time with actual arrival time
     */
    public function get_booking_efficiency(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;
        
        try {
            $bookings_table = $wpdb->prefix . 'asmaa_bookings';
            $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';
            
            $date_from = $request->get_param('date_from') ?: date('Y-m-d', strtotime('-30 days'));
            $date_to = $request->get_param('date_to') ?: date('Y-m-d');
            $staff_id = $request->get_param('staff_id') ? (int) $request->get_param('staff_id') : null;
            
            // Get bookings that were converted to queue tickets
            $query = "
                SELECT 
                    b.id as booking_id,
                    b.wc_customer_id,
                    b.wp_user_id as staff_id,
                    b.booking_date,
                    b.booking_time,
                    b.status as booking_status,
                    q.id as queue_ticket_id,
                    q.check_in_at,
                    q.serving_started_at,
                    q.completed_at,
                    TIMESTAMPDIFF(MINUTE, 
                        CONCAT(b.booking_date, ' ', b.booking_time),
                        q.check_in_at
                    ) as delay_minutes
                FROM {$bookings_table} b
                INNER JOIN {$queue_table} q ON q.booking_id = b.id
                WHERE b.deleted_at IS NULL
                AND q.deleted_at IS NULL
                AND b.booking_date BETWEEN %s AND %s
            ";
            
            $params = [$date_from, $date_to];
            
            if ($staff_id) {
                $query .= " AND b.wp_user_id = %d";
                $params[] = $staff_id;
            }
            
            $query .= " ORDER BY b.booking_date DESC, b.booking_time DESC";
            
            $bookings = $wpdb->get_results($wpdb->prepare($query, $params));
            
            // Calculate statistics
            $total_bookings = count($bookings);
            $on_time = 0;
            $late = 0;
            $early = 0;
            $total_delay = 0;
            $total_early = 0;
            $staff_stats = [];
            
            foreach ($bookings as $booking) {
                $delay = (int) $booking->delay_minutes;
                
                if ($delay > 0) {
                    $late++;
                    $total_delay += $delay;
                } elseif ($delay < 0) {
                    $early++;
                    $total_early += abs($delay);
                } else {
                    $on_time++;
                }
                
                // Staff statistics
                $staff_id_key = (int) $booking->staff_id;
                if (!isset($staff_stats[$staff_id_key])) {
                    $staff_stats[$staff_id_key] = [
                        'staff_id' => $staff_id_key,
                        'total' => 0,
                        'on_time' => 0,
                        'late' => 0,
                        'early' => 0,
                        'total_delay' => 0,
                        'total_early' => 0,
                    ];
                }
                
                $staff_stats[$staff_id_key]['total']++;
                if ($delay > 0) {
                    $staff_stats[$staff_id_key]['late']++;
                    $staff_stats[$staff_id_key]['total_delay'] += $delay;
                } elseif ($delay < 0) {
                    $staff_stats[$staff_id_key]['early']++;
                    $staff_stats[$staff_id_key]['total_early'] += abs($delay);
                } else {
                    $staff_stats[$staff_id_key]['on_time']++;
                }
            }
            
            // Get staff names
            foreach ($staff_stats as $staff_id_key => &$stats) {
                $user = get_user_by('ID', $staff_id_key);
                $stats['staff_name'] = $user ? ($user->display_name ?: $user->user_login) : "Staff #{$staff_id_key}";
            }
            
            $data = [
                'period' => [
                    'from' => $date_from,
                    'to' => $date_to,
                ],
                'summary' => [
                    'total_bookings' => $total_bookings,
                    'on_time' => $on_time,
                    'late' => $late,
                    'early' => $early,
                    'on_time_percentage' => $total_bookings > 0 ? round(($on_time / $total_bookings) * 100, 2) : 0,
                    'average_delay' => $late > 0 ? round($total_delay / $late, 2) : 0,
                    'average_early' => $early > 0 ? round($total_early / $early, 2) : 0,
                ],
                'staff_performance' => array_values($staff_stats),
                'bookings' => array_map(function($b) {
                    return [
                        'booking_id' => (int) $b->booking_id,
                        'queue_ticket_id' => (int) $b->queue_ticket_id,
                        'customer_id' => (int) $b->wc_customer_id,
                        'staff_id' => (int) $b->staff_id,
                        'scheduled_time' => $b->booking_date . ' ' . $b->booking_time,
                        'arrival_time' => $b->check_in_at,
                        'delay_minutes' => (int) $b->delay_minutes,
                        'serving_started_at' => $b->serving_started_at,
                        'completed_at' => $b->completed_at,
                    ];
                }, $bookings),
            ];
            
            return $this->success_response($data);
        } catch (\Exception $e) {
            return $this->error_response($e->getMessage(), 500);
        }
    }
}

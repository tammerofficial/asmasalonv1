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

        $orders_table = $wpdb->prefix . 'asmaa_orders';
        $order_items_table = $wpdb->prefix . 'asmaa_order_items';
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $payments_table = $wpdb->prefix . 'asmaa_payments';
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $loyalty_table = $wpdb->prefix . 'asmaa_loyalty_transactions';
        $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';
        $staff_table = $wpdb->prefix . 'asmaa_staff';
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';

        $grouping = $this->get_grouping($start_date, $end_date);
        $fmt = $grouping['format'];

        // 1) Sales trend (orders + revenue + paid)
        $sales_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(created_at, %s) AS p,
                COUNT(*) AS orders,
                COALESCE(SUM(total), 0) AS revenue,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) AS paid
             FROM {$orders_table}
             WHERE created_at BETWEEN %s AND %s
               AND deleted_at IS NULL
             GROUP BY p
             ORDER BY p ASC",
            $fmt,
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
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

        // 5) Top services/products from POS order items (range)
        $top_services_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                oi.item_name,
                COALESCE(SUM(oi.quantity), 0) AS qty,
                COALESCE(SUM(oi.total), 0) AS revenue
             FROM {$order_items_table} oi
             JOIN {$orders_table} o ON o.id = oi.order_id
             WHERE o.created_at BETWEEN %s AND %s
               AND o.deleted_at IS NULL
               AND oi.item_type = 'service'
             GROUP BY oi.item_id, oi.item_name
             ORDER BY revenue DESC
             LIMIT 10",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        $top_services_labels = [];
        $top_services_revenue = [];
        foreach ($top_services_rows as $r) {
            $top_services_labels[] = (string) ($r->item_name ?? '');
            $top_services_revenue[] = (float) ($r->revenue ?? 0);
        }

        $top_products_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                oi.item_name,
                COALESCE(SUM(oi.quantity), 0) AS qty,
                COALESCE(SUM(oi.total), 0) AS revenue
             FROM {$order_items_table} oi
             JOIN {$orders_table} o ON o.id = oi.order_id
             WHERE o.created_at BETWEEN %s AND %s
               AND o.deleted_at IS NULL
               AND oi.item_type = 'product'
             GROUP BY oi.item_id, oi.item_name
             ORDER BY revenue DESC
             LIMIT 10",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        $top_products_labels = [];
        $top_products_revenue = [];
        foreach ($top_products_rows as $r) {
            $top_products_labels[] = (string) ($r->item_name ?? '');
            $top_products_revenue[] = (float) ($r->revenue ?? 0);
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

        // 7) New customers trend
        $customers_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(created_at, %s) AS p,
                COUNT(*) AS c
             FROM {$customers_table}
             WHERE created_at BETWEEN %s AND %s
               AND deleted_at IS NULL
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
            "SELECT s.id,
                    s.name,
                    COALESCE(SUM(sc.final_amount), 0) AS total_commission
             FROM {$commissions_table} sc
             JOIN {$staff_table} s ON s.id = sc.staff_id
             WHERE sc.created_at BETWEEN %s AND %s
             GROUP BY s.id, s.name
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
        global $wpdb;
        
        $start_date = $request->get_param('start_date') ?: date('Y-m-01');
        $end_date = $request->get_param('end_date') ?: date('Y-m-d');
        
        $orders_table = $wpdb->prefix . 'asmaa_orders';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_orders,
                SUM(total) as total_revenue,
                SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as paid_amount
            FROM {$orders_table}
            WHERE created_at BETWEEN %s AND %s AND deleted_at IS NULL
            GROUP BY DATE(created_at)
            ORDER BY date DESC",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));

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
        
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        
        $stats = [
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$customers_table} WHERE deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$customers_table} WHERE is_active = 1 AND deleted_at IS NULL"),
            'new_this_month' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$customers_table} WHERE MONTH(created_at) = %d AND YEAR(created_at) = %d AND deleted_at IS NULL",
                date('n'),
                date('Y')
            )),
            'top_customers' => $wpdb->get_results(
                "SELECT id, name, total_spent, total_visits FROM {$customers_table} WHERE deleted_at IS NULL ORDER BY total_spent DESC LIMIT 10"
            ),
        ];

        return $this->success_response($stats);
    }

    public function get_staff_report(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        
        $staff_table = $wpdb->prefix . 'asmaa_staff';
        
        $results = $wpdb->get_results(
            "SELECT id, name, total_services, total_revenue, rating, total_ratings 
             FROM {$staff_table} 
             WHERE deleted_at IS NULL AND is_active = 1 
             ORDER BY total_revenue DESC"
        );

        return $this->success_response($results);
    }

    public function get_dashboard_stats(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        
        $customers_table = $wpdb->prefix . 'asmaa_customers';
        $bookings_table = $wpdb->prefix . 'asmaa_bookings';
        $orders_table = $wpdb->prefix . 'asmaa_orders';
        $staff_table = $wpdb->prefix . 'asmaa_staff';
        $services_table = $wpdb->prefix . 'asmaa_services';
        $invoices_table = $wpdb->prefix . 'asmaa_invoices';
        $queue_table = $wpdb->prefix . 'asmaa_queue_tickets';
        
        $today = date('Y-m-d');
        $this_month_start = date('Y-m-01');
        $last_7_start = date('Y-m-d', strtotime($today . ' -6 days'));
        $last_30_start = date('Y-m-d', strtotime($today . ' -29 days'));

        // Sales stats (for Sales page)
        $today_orders = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$orders_table} WHERE DATE(created_at) = %s AND deleted_at IS NULL",
            $today
        ));
        $today_sales = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM {$orders_table} WHERE DATE(created_at) = %s AND deleted_at IS NULL",
            $today
        ));

        $month_orders = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$orders_table} WHERE DATE(created_at) >= %s AND deleted_at IS NULL",
            $this_month_start
        ));
        $month_sales = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM {$orders_table} WHERE DATE(created_at) >= %s AND deleted_at IS NULL",
            $this_month_start
        ));
        $avg_order = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(AVG(total), 0) FROM {$orders_table} WHERE DATE(created_at) >= %s AND deleted_at IS NULL",
            $this_month_start
        ));

        $top_customer = $wpdb->get_row(
            "SELECT name, total_spent
             FROM {$customers_table}
             WHERE deleted_at IS NULL
             ORDER BY total_spent DESC
             LIMIT 1"
        );
        
        $stats = [
            'customers' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$customers_table} WHERE deleted_at IS NULL"),
            'bookingsToday' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$bookings_table} WHERE booking_date = %s AND deleted_at IS NULL",
                $today
            )),
            'monthlyRevenue' => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total), 0) FROM {$orders_table} WHERE DATE(created_at) >= %s AND deleted_at IS NULL",
                $this_month_start
            )),
            'activeStaff' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$staff_table} WHERE is_active = 1 AND deleted_at IS NULL"),
            'totalServices' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$services_table} WHERE is_active = 1 AND deleted_at IS NULL"),
            'pendingOrders' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$orders_table} WHERE status = 'pending' AND deleted_at IS NULL"),
            'unpaidInvoices' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$invoices_table} WHERE status IN ('sent', 'overdue', 'partial') AND deleted_at IS NULL"),
            'queueWaiting' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table} WHERE status IN ('waiting', 'called') AND deleted_at IS NULL"),

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
        // 1) Sales last 7 days (revenue + orders)
        $sales_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE(created_at) AS d,
                COUNT(*) AS orders,
                COALESCE(SUM(total), 0) AS revenue
             FROM {$orders_table}
             WHERE DATE(created_at) BETWEEN %s AND %s
               AND deleted_at IS NULL
             GROUP BY DATE(created_at)
             ORDER BY d ASC",
            $last_7_start,
            $today
        ));
        $sales_map = [];
        foreach ($sales_rows as $r) {
            $sales_map[(string) ($r->d ?? '')] = [
                'orders' => (int) ($r->orders ?? 0),
                'revenue' => (float) ($r->revenue ?? 0),
            ];
        }
        $sales_labels = [];
        $sales_orders = [];
        $sales_revenue = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime($last_7_start . " +{$i} days"));
            $sales_labels[] = $d;
            $sales_orders[] = (int) (($sales_map[$d]['orders'] ?? 0));
            $sales_revenue[] = (float) (($sales_map[$d]['revenue'] ?? 0));
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
        global $wpdb;
        $orders_table = $wpdb->prefix . 'asmaa_orders';
        $payments_table = $wpdb->prefix . 'asmaa_payments';

        $date = $request->get_param('date') ?: date('Y-m-d');

        // Get today's stats
        $today_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total), 0) as total_revenue,
                COALESCE(AVG(total), 0) as avg_order_value
            FROM {$orders_table}
            WHERE DATE(created_at) = %s AND deleted_at IS NULL",
            $date
        ));

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
        $yesterday_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM {$orders_table} WHERE DATE(created_at) = %s AND deleted_at IS NULL",
            $yesterday
        ));

        // Last week same day
        $last_week = date('Y-m-d', strtotime($date . ' -7 days'));
        $last_week_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM {$orders_table} WHERE DATE(created_at) = %s AND deleted_at IS NULL",
            $last_week
        ));

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
        $staff_table = $wpdb->prefix . 'asmaa_staff';
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
                s.id,
                s.name,
                COUNT(DISTINCT b.id) as services_count,
                COALESCE(SUM(b.final_price), 0) as revenue,
                s.rating,
                COALESCE(SUM(sc.final_amount), 0) as total_commissions
            FROM {$staff_table} s
            LEFT JOIN {$bookings_table} b ON s.id = b.staff_id AND b.status = 'completed' AND b.deleted_at IS NULL {$date_filter}
            LEFT JOIN {$commissions_table} sc ON s.id = sc.staff_id AND sc.status = 'pending'
            WHERE s.deleted_at IS NULL AND s.is_active = 1
            GROUP BY s.id, s.name, s.rating
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
        $staff_table = $wpdb->prefix . 'asmaa_staff';

        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');
        $staff_id = $request->get_param('staff_id');

        $where = ["sc.status = 'pending'"];
        if ($start_date && $end_date) {
            $where[] = $wpdb->prepare("DATE_FORMAT(sc.created_at, '%%Y-%%m') BETWEEN %s AND %s", $start_date, $end_date);
        }
        if ($staff_id) {
            $where[] = $wpdb->prepare("sc.staff_id = %d", (int) $staff_id);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);

        $results = $wpdb->get_results(
            "SELECT 
                s.id as staff_id,
                s.name as staff_name,
                COUNT(sc.id) as total_services,
                COALESCE(SUM(sc.base_amount), 0) as total_revenue,
                COALESCE(SUM(sc.commission_amount), 0) as base_commission,
                COALESCE(SUM(sc.rating_bonus), 0) as total_bonuses,
                COALESCE(SUM(sc.final_amount), 0) as total_commission
            FROM {$staff_table} s
            JOIN {$commissions_table} sc ON s.id = sc.staff_id
            {$where_clause}
            GROUP BY s.id, s.name
            ORDER BY total_commission DESC"
        );

        return $this->success_response([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data' => $results,
        ]);
    }
}

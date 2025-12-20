<?php
/**
 * Asmaa Salon - End-to-End Scenario (REST API via rest_do_request)
 *
 * Run from project root:
 * php wp-content/plugins/asmaa-salon/tests/e2e_scenario.php
 *
 * Notes:
 * - This boots WordPress, creates an admin user session, and runs a full scenario through REST endpoints.
 * - It asserts key outputs and verifies Activity Logger writes.
 */

declare(strict_types=1);

// Bootstrap WP
$root = realpath(__DIR__ . '/../../../..'); // wp-content/plugins/asmaa-salon/tests -> WP root
require_once $root . '/wp-load.php';

if (!function_exists('rest_do_request')) {
    fwrite(STDERR, "REST API not available.\n");
    exit(1);
}

function assert_true(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "❌ ASSERT FAILED: {$msg}\n");
        exit(1);
    }
}

function req(string $method, string $path, array $params = []): array {
    $request = new WP_REST_Request($method, $path);
    if ($params) {
        $request->set_body_params($params);
        $request->set_header('content-type', 'application/x-www-form-urlencoded');
    }

    $response = rest_do_request($request);
    $data = $response->get_data();

    return [
        'status' => $response->get_status(),
        'data' => $data,
    ];
}

// Ensure admin user
$admin_user = get_user_by('login', 'asmaa_e2e_admin');
if (!$admin_user) {
    $user_id = wp_create_user('asmaa_e2e_admin', wp_generate_password(24, true), 'asmaa_e2e_admin@example.com');
    wp_update_user(['ID' => $user_id, 'role' => 'administrator']);
    $admin_user = get_user_by('ID', $user_id);
}
wp_set_current_user($admin_user->ID);

// Ping
$ping = req('GET', '/asmaa-salon/v1/ping');
assert_true($ping['status'] === 200, 'ping status 200');
assert_true(($ping['data']['success'] ?? false) === true, 'ping success true');

// Unique suffix for re-runs (avoid phone/email uniqueness collisions)
$run = (string) time();
$started_at = current_time('mysql');

// Create Customer
$customer = req('POST', '/asmaa-salon/v1/customers', [
    'name' => 'E2E Customer',
    'phone' => '+965' . substr($run, -8),
    'email' => "e2e.customer.{$run}@example.com",
    'city' => 'Kuwait',
    'is_active' => 1,
]);
assert_true($customer['status'] === 201, 'customer created 201');
$customer_id = (int)($customer['data']['data']->id ?? $customer['data']['data']['id'] ?? 0);
assert_true($customer_id > 0, 'customer id exists');

// Create Service
$service = req('POST', '/asmaa-salon/v1/services', [
    'name' => 'E2E Service',
    'name_ar' => 'خدمة E2E',
    'price' => 10,
    'duration' => 30,
    'is_active' => 1,
]);
assert_true($service['status'] === 201, 'service created 201');
$service_id = (int)($service['data']['data']->id ?? $service['data']['data']['id'] ?? 0);
assert_true($service_id > 0, 'service id exists');

// Create Staff
$staff = req('POST', '/asmaa-salon/v1/staff', [
    'name' => 'E2E Staff',
    'phone' => '+965' . substr((string)($run + 1), -8),
    'email' => "e2e.staff.{$run}@example.com",
    'commission_rate' => 10,
    'is_active' => 1,
]);
assert_true($staff['status'] === 201, 'staff created 201');
$staff_id = (int)($staff['data']['data']->id ?? $staff['data']['data']['id'] ?? 0);
assert_true($staff_id > 0, 'staff id exists');

// Create Booking
$booking = req('POST', '/asmaa-salon/v1/bookings', [
    'customer_id' => $customer_id,
    'service_id' => $service_id,
    'staff_id' => $staff_id,
    'status' => 'pending',
    'booking_date' => date('Y-m-d'),
    'booking_time' => '10:00',
    'total_amount' => 10,
]);
assert_true($booking['status'] === 201, 'booking created 201');
$booking_id = (int)($booking['data']['data']->id ?? $booking['data']['data']['id'] ?? 0);
assert_true($booking_id > 0, 'booking id exists');

// Create Queue Ticket linked to booking
$ticket = req('POST', '/asmaa-salon/v1/queue', [
    'customer_id' => $customer_id,
    'service_id' => $service_id,
    'staff_id' => $staff_id,
    'booking_id' => $booking_id,
    'notes' => 'E2E queue ticket',
]);
assert_true($ticket['status'] === 201, 'queue ticket created 201');
$ticket_id = (int)($ticket['data']['data']->id ?? $ticket['data']['data']['id'] ?? 0);
assert_true($ticket_id > 0, 'ticket id exists');

// Call next (should call this ticket if it is next)
$called = req('POST', '/asmaa-salon/v1/queue/call-next');
assert_true(in_array($called['status'], [200, 201], true), 'call-next ok');

// Start serving the ticket
$start = req('POST', "/asmaa-salon/v1/queue/{$ticket_id}/start");
assert_true($start['status'] === 200, 'start serving ok');

// Complete ticket
$complete = req('POST', "/asmaa-salon/v1/queue/{$ticket_id}/complete");
assert_true($complete['status'] === 200, 'complete ticket ok');

// Create Order linked to booking
$order = req('POST', '/asmaa-salon/v1/orders', [
    'customer_id' => $customer_id,
    'staff_id' => $staff_id,
    'booking_id' => $booking_id,
    'subtotal' => 10,
    'discount' => 0,
    'tax' => 0,
    'total' => 10,
    'status' => 'pending',
    'payment_status' => 'unpaid',
    'payment_method' => 'cash',
    'items' => [
        [
            'item_type' => 'service',
            'item_id' => $service_id,
            'item_name' => 'E2E Service',
            'quantity' => 1,
            'unit_price' => 10,
            'total' => 10,
            'staff_id' => $staff_id,
        ],
    ],
]);
assert_true($order['status'] === 201, 'order created 201');
$order_id = (int)($order['data']['data']->id ?? $order['data']['data']['id'] ?? 0);
assert_true($order_id > 0, 'order id exists');

// Create Payment for order (should mark order paid)
$payment = req('POST', '/asmaa-salon/v1/payments', [
    'customer_id' => $customer_id,
    'order_id' => $order_id,
    'amount' => 10,
    'payment_method' => 'cash',
    'status' => 'completed',
]);
assert_true($payment['status'] === 201, 'payment created 201');
$payment_id = (int)($payment['data']['data']->id ?? $payment['data']['data']['id'] ?? 0);
assert_true($payment_id > 0, 'payment id exists');

// Verify Activity Log has entries
global $wpdb;
$log_table = $wpdb->prefix . 'asmaa_activity_log';
$count = (int)$wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$log_table} WHERE created_at >= %s AND subject_id IN (%d,%d,%d,%d)",
        $started_at,
        $booking_id,
        $ticket_id,
        $order_id,
        $payment_id
    )
);
assert_true($count >= 3, 'activity log has entries for this run');

fwrite(STDOUT, "✅ E2E scenario finished successfully.\n");
fwrite(STDOUT, "- customer_id={$customer_id}\n");
fwrite(STDOUT, "- service_id={$service_id}\n");
fwrite(STDOUT, "- staff_id={$staff_id}\n");
fwrite(STDOUT, "- booking_id={$booking_id}\n");
fwrite(STDOUT, "- ticket_id={$ticket_id}\n");
fwrite(STDOUT, "- order_id={$order_id}\n");
fwrite(STDOUT, "- payment_id={$payment_id}\n");
fwrite(STDOUT, "\n");


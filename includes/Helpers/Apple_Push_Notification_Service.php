<?php

namespace AsmaaSalon\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

class Apple_Push_Notification_Service
{
    /**
     * Send push notification to update pass on device
     * 
     * @param string $serial_number Pass serial number
     * @return bool Success status
     */
    public static function send_push_notification(string $serial_number): bool
    {
        global $wpdb;
        
        // Get device registrations for this pass
        $registrations_table = $wpdb->prefix . 'asmaa_apple_wallet_device_registrations';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$registrations_table}'") === $registrations_table;
        
        if (!$table_exists) {
            return false;
        }
        
        // Get all devices registered for this pass
        $devices = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT device_id, push_token FROM {$registrations_table} WHERE serial_number = %s AND push_token IS NOT NULL AND push_token != ''",
                $serial_number
            )
        );
        
        if (empty($devices)) {
            return false;
        }
        
        // Get pass type identifier
        $passes_table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        $pass = $wpdb->get_row(
            $wpdb->prepare("SELECT pass_type_identifier FROM {$passes_table} WHERE serial_number = %s", $serial_number)
        );
        
        if (!$pass) {
            return false;
        }
        
        $pass_type_id = $pass->pass_type_identifier;
        
        // Send push to each device
        $success_count = 0;
        foreach ($devices as $device) {
            if (self::send_apns_notification($device->push_token, $pass_type_id, $serial_number)) {
                $success_count++;
            }
        }
        
        return $success_count > 0;
    }
    
    /**
     * Send APNs notification to a single device
     * 
     * @param string $push_token Device push token
     * @param string $pass_type_id Pass type identifier
     * @param string $serial_number Pass serial number
     * @return bool Success status
     */
    private static function send_apns_notification(string $push_token, string $pass_type_id, string $serial_number): bool
    {
        // Get certificate path
        $cert_path = \AsmaaSalon\Config\Apple_Wallet_Config::get_certificate_path();
        $cert_password = \AsmaaSalon\Config\Apple_Wallet_Config::CERTIFICATE_PASSWORD;
        
        // Resolve certificate path
        if ($cert_path && !file_exists($cert_path)) {
            $upload_dir = wp_upload_dir();
            $certs_dir = $upload_dir['basedir'] . '/asmaa-salon/certs';
            if (file_exists($certs_dir . '/' . basename($cert_path))) {
                $cert_path = $certs_dir . '/' . basename($cert_path);
            }
        }
        
        if (!$cert_path || !file_exists($cert_path)) {
            error_log('Apple Wallet APNs: Certificate not found');
            return false;
        }
        
        // Determine APNs endpoint (production or sandbox)
        $use_sandbox = \AsmaaSalon\Config\Apple_Wallet_Config::SANDBOX_MODE;
        $apns_url = $use_sandbox 
            ? 'ssl://gateway.sandbox.push.apple.com:2195'
            : 'ssl://gateway.push.apple.com:2195';
        
        try {
            // Read certificate
            $cert_data = file_get_contents($cert_path);
            $pkcs12 = [];
            
            if (!openssl_pkcs12_read($cert_data, $pkcs12, $cert_password)) {
                error_log('Apple Wallet APNs: Failed to read certificate');
                return false;
            }
            
            // Create stream context
            $ctx = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', $cert_path);
            if ($cert_password) {
                stream_context_set_option($ctx, 'ssl', 'passphrase', $cert_password);
            }
            
            // Open connection to APNs
            $fp = stream_socket_client($apns_url, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
            
            if (!$fp) {
                error_log("Apple Wallet APNs: Failed to connect: $err $errstr");
                return false;
            }
            
            // Build payload
            $payload = json_encode([
                'aps' => [
                    'alert' => [
                        'loc-key' => 'PASS_UPDATE_AVAILABLE',
                    ],
                ],
                'passTypeIdentifier' => $pass_type_id,
                'serialNumber' => $serial_number,
            ]);
            
            // Build notification message
            $msg = chr(0) . pack('n', 32) . pack('H*', $push_token) . pack('n', strlen($payload)) . $payload;
            
            // Send notification
            $result = fwrite($fp, $msg, strlen($msg));
            fclose($fp);
            
            if ($result === false) {
                error_log('Apple Wallet APNs: Failed to send notification');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Apple Wallet APNs Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send push notification to all devices for a customer's passes
     * 
     * @param int $wc_customer_id Customer ID
     * @param string|null $pass_type Optional pass type filter
     * @return int Number of notifications sent
     */
    public static function notify_customer_passes(int $wc_customer_id, ?string $pass_type = null): int
    {
        global $wpdb;
        
        $passes_table = $wpdb->prefix . 'asmaa_apple_wallet_passes';
        
        if ($pass_type) {
            $passes = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT serial_number FROM {$passes_table} WHERE wc_customer_id = %d AND pass_type = %s",
                    $wc_customer_id,
                    $pass_type
                )
            );
        } else {
            $passes = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT serial_number FROM {$passes_table} WHERE wc_customer_id = %d",
                    $wc_customer_id
                )
            );
        }
        
        $sent = 0;
        foreach ($passes as $serial_number) {
            if (self::send_push_notification($serial_number)) {
                $sent++;
            }
        }
        
        return $sent;
    }
}


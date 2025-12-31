<?php

namespace AsmaaSalon\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

class QR_Code_Generator
{
    /**
     * Generate QR code data for customer loyalty scanning
     * 
     * @param int $customer_id WooCommerce customer ID (WordPress user ID)
     * @return array QR code data
     */
    public static function generate_for_customer(int $customer_id): array
    {
        $nonce = wp_create_nonce('loyalty_scan_' . $customer_id);
        $timestamp = time();
        
        $data = [
            'type' => 'loyalty_scan',
            'customer_id' => $customer_id,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'action' => 'earn_points',
        ];
        
        // Encode as JSON
        $json_data = json_encode($data);
        
        // Base64 encode for URL safety
        $encoded = base64_encode($json_data);
        
        return [
            'data' => $data,
            'json' => $json_data,
            'encoded' => $encoded,
            'url' => rest_url('asmaa-salon/v1/loyalty/scan/' . $encoded),
        ];
    }
    
    /**
     * Validate and decode QR code data
     * 
     * @param string $encoded_data Base64 encoded QR code data
     * @return array|false Decoded data or false if invalid
     */
    public static function validate_and_decode(string $encoded_data)
    {
        // Decode from base64
        $json_data = base64_decode($encoded_data, true);
        if ($json_data === false) {
            return false;
        }
        
        // Decode JSON
        $data = json_decode($json_data, true);
        if ($data === null || !is_array($data)) {
            return false;
        }
        
        // Validate required fields
        if (empty($data['type']) || 
            empty($data['customer_id']) || 
            empty($data['timestamp']) || 
            empty($data['nonce'])) {
            return false;
        }
        
        // Validate type
        if ($data['type'] !== 'loyalty_scan') {
            return false;
        }
        
        // Validate timestamp (QR code expires after 1 hour)
        $age = time() - (int) $data['timestamp'];
        if ($age > 3600) {
            return false;
        }
        
        // Validate nonce
        if (!wp_verify_nonce($data['nonce'], 'loyalty_scan_' . $data['customer_id'])) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Generate QR code image URL (using external service or library)
     * 
     * @param string $data Data to encode in QR code
     * @param int $size QR code size in pixels
     * @return string QR code image URL
     */
    public static function generate_image_url(string $data, int $size = 300): string
    {
        // Use Google Charts API for QR code generation
        $encoded_data = urlencode($data);
        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded_data}";
    }
}


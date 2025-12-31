<?php

namespace AsmaaSalon\Services;

if (!defined('ABSPATH')) {
    exit;
}

class Product_Service
{
    /**
     * Get or create Virtual Product for a service
     * 
     * @param int $service_id Service ID from asmaa_services table
     * @param string $service_name Service name
     * @param float $price Service price
     * @return int WooCommerce Product ID
     */
    public static function get_or_create_service_product(int $service_id, string $service_name, float $price): int
    {
        global $wpdb;
        $extended_table = $wpdb->prefix . 'asmaa_product_extended_data';
        
        // البحث عن منتج موجود مرتبط بهذه الخدمة
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT wc_product_id FROM {$extended_table} WHERE service_id = %d",
            $service_id
        ));
        
        if ($existing) {
            $product = wc_get_product((int) $existing);
            if ($product && $product->exists()) {
                // Update price if changed
                if ((float) $product->get_price() !== $price) {
                    $product->set_price($price);
                    $product->set_regular_price($price);
                    $product->save();
                }
                return (int) $existing;
            }
        }
        
        // إنشاء Virtual Product جديد
        $product = new \WC_Product_Simple();
        $product->set_name($service_name);
        $product->set_virtual(true);
        $product->set_downloadable(false);
        $product->set_price($price);
        $product->set_regular_price($price);
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        $product->set_sku('SERVICE-' . $service_id);
        
        // إخفاء المنتج من المتجر الإلكتروني (Catalog Visibility = Hidden)
        // هذا يمنع ظهور الخدمات كمنتجات قابلة للشراء في المتجر العام
        $product->set_catalog_visibility('hidden');
        
        $product->save();
        
        $wc_product_id = $product->get_id();
        
        // ربط المنتج بالخدمة في الجدول الممتد
        $wpdb->replace($extended_table, [
            'wc_product_id' => $wc_product_id,
            'service_id' => $service_id,
            'is_service' => 1,
        ]);
        
        return $wc_product_id;
    }
}


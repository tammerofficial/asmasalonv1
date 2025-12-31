# خطة الربط الاحترافية مع WooCommerce

## نظرة عامة

ربط نظام Asmaa Salon مع WooCommerce بشكل ثنائي الاتجاه (Bidirectional Sync) مع الحفاظ على المكونات الخاصة بالصالون مستقلة.

## المكونات المراد ربطها

### 1. المكونات المرتبطة مع WooCommerce

| المكون | حالة الربط | التفاصيل |

|--------|-----------|----------|

| **المنتجات (Products)** | ✅ ربط كامل | مزامنة ثنائية: `asmaa_products` ↔ `wc_products` |

| **الطلبات (Orders)** | ✅ ربط كامل | مزامنة ثنائية: `asmaa_orders` ↔ `wc_orders` |

| **العملاء (Customers)** | ✅ ربط كامل | مزامنة ثنائية: `asmaa_customers` ↔ `wc_customers` |

| **المدفوعات (Payments)** | ✅ ربط كامل | ربط مع WooCommerce Payments |

| **الفواتير (Invoices)** | ✅ ربط كامل | ربط مع WooCommerce Orders |

| **POS** | ✅ ربط كامل | استخدام WooCommerce Products & Orders |

### 2. المكونات المستقلة (تبقى في Asmaa Salon فقط)

| المكون | السبب |

|--------|-------|

| **الحجوزات (Bookings)** | غير موجود في WooCommerce |

| **الطابور (Queue)** | خاص بالصالون |

| **غرفة الموظفين (Staff Room)** | خاص بالصالون |

| **الخدمات (Services)** | غير موجودة في WooCommerce (يمكن تحويلها لـ Products) |

| **الموظفين (Staff)** | خاص بالصالون |

| **نقاط الولاء (Loyalty)** | خاص بالصالون |

| **العضويات (Memberships)** | خاص بالصالون |

| **العمولات (Commissions)** | خاص بالصالون |

| **التقارير (Reports)** | تجميع من كلا النظامين |

---

## البنية التقنية

### 1. إنشاء WooCommerce Integration Service

**الملف:** `includes/Services/WooCommerce_Integration_Service.php`

```php
namespace AsmaaSalon\Services;

class WooCommerce_Integration_Service {
    // Check if WooCommerce is active
    public static function is_woocommerce_active(): bool
    
    // Products Sync
    public static function sync_product_to_wc(int $asmaa_product_id): ?int
    public static function sync_product_from_wc(int $wc_product_id): ?int
    
    // Orders Sync
    public static function sync_order_to_wc(int $asmaa_order_id): ?int
    public static function sync_order_from_wc(int $wc_order_id): ?int
    
    // Customers Sync
    public static function sync_customer_to_wc(int $asmaa_customer_id): ?int
    public static function sync_customer_from_wc(int $wc_customer_id): ?int
    
    // Payments Sync
    public static function sync_payment_to_wc(int $asmaa_payment_id, int $wc_order_id): bool
}
```

### 2. إضافة حقول ربط في قاعدة البيانات

**الملف:** `includes/Database/Schema.php`

إضافة أعمدة ربط في الجداول:

```sql
-- asmaa_products
ALTER TABLE wp_asmaa_products 
ADD COLUMN wc_product_id BIGINT UNSIGNED NULL,
ADD COLUMN wc_synced_at DATETIME NULL,
ADD KEY idx_wc_product_id (wc_product_id);

-- asmaa_orders
ALTER TABLE wp_asmaa_orders 
ADD COLUMN wc_order_id BIGINT UNSIGNED NULL,
ADD COLUMN wc_synced_at DATETIME NULL,
ADD KEY idx_wc_order_id (wc_order_id);

-- asmaa_customers
ALTER TABLE wp_asmaa_customers 
ADD COLUMN wc_customer_id BIGINT UNSIGNED NULL,
ADD COLUMN wc_synced_at DATETIME NULL,
ADD KEY idx_wc_customer_id (wc_customer_id);

-- asmaa_payments
ALTER TABLE wp_asmaa_payments 
ADD COLUMN wc_payment_id BIGINT UNSIGNED NULL,
ADD KEY idx_wc_payment_id (wc_payment_id);
```

### 3. WooCommerce Hooks Integration

**الملف:** `includes/Plugin.php`

إضافة hooks في `init()`:

```php
// WooCommerce Integration Hooks
if (class_exists('WooCommerce')) {
    // Products
    add_action('woocommerce_new_product', [$this, 'sync_wc_product_to_asmaa'], 10, 1);
    add_action('woocommerce_update_product', [$this, 'sync_wc_product_to_asmaa'], 10, 1);
    
    // Orders
    add_action('woocommerce_new_order', [$this, 'sync_wc_order_to_asmaa'], 10, 1);
    add_action('woocommerce_update_order', [$this, 'sync_wc_order_to_asmaa'], 10, 1);
    
    // Customers
    add_action('woocommerce_created_customer', [$this, 'sync_wc_customer_to_asmaa'], 10, 1);
    add_action('woocommerce_update_customer', [$this, 'sync_wc_customer_to_asmaa'], 10, 1);
}
```

---

## تفاصيل التكامل حسب المكون

### 1. المنتجات (Products)

**الملف:** `includes/API/Controllers/Products_Controller.php`

**التعديلات:**

- عند إنشاء/تحديث منتج في Asmaa Salon → إنشاء/تحديث في WooCommerce
- عند إنشاء/تحديث منتج في WooCommerce → إنشاء/تحديث في Asmaa Salon

**Mapping:**

```
asmaa_products.name → wc_product.post_title
asmaa_products.sku → wc_product_meta._sku
asmaa_products.selling_price → wc_product_meta._regular_price
asmaa_products.stock_quantity → wc_product_meta._stock
asmaa_products.min_stock_level → wc_product_meta._low_stock_amount
asmaa_products.image → wc_product_meta._thumbnail_id
```

### 2. الطلبات (Orders)

**الملف:** `includes/API/Controllers/Orders_Controller.php`

**التعديلات:**

- عند إنشاء طلب في Asmaa Salon → إنشاء WooCommerce Order
- عند إنشاء طلب في WooCommerce → إنشاء Asmaa Order

**Mapping:**

```
asmaa_orders.order_number → wc_order.post_title
asmaa_orders.customer_id → wc_order_meta._customer_user (via wc_customer_id)
asmaa_orders.total → wc_order_meta._order_total
asmaa_orders.status → wc_order.post_status (mapping: pending→wc-pending, completed→wc-completed)
asmaa_order_items → wc_order_items (line items)
```

### 3. العملاء (Customers)

**الملف:** `includes/API/Controllers/Customers_Controller.php`

**التعديلات:**

- عند إنشاء/تحديث عميل في Asmaa Salon → إنشاء/تحديث WooCommerce Customer
- عند إنشاء/تحديث عميل في WooCommerce → إنشاء/تحديث Asmaa Customer

**Mapping:**

```
asmaa_customers.name → wc_customer.display_name
asmaa_customers.email → wc_customer.email
asmaa_customers.phone → wc_customer_meta.billing_phone
asmaa_customers.address → wc_customer_meta.billing_address_1
asmaa_customers.city → wc_customer_meta.billing_city
```

### 4. المدفوعات (Payments)

**الملف:** `includes/API/Controllers/Payments_Controller.php`

**التعديلات:**

- ربط المدفوعات مع WooCommerce Order Payments
- استخدام WooCommerce Payment Gateways عند الحاجة

### 5. POS Integration

**الملف:** `includes/API/Controllers/POS_Controller.php`

**التعديلات:**

- استخدام WooCommerce Products في POS
- إنشاء WooCommerce Orders عند البيع من POS
- مزامنة المخزون تلقائياً

---

## إعدادات التكامل

### 1. صفحة الإعدادات

**الملف:** `includes/API/Controllers/Settings_Controller.php` (جديد)

إضافة endpoints:

- `GET /settings/woocommerce` - جلب إعدادات التكامل
- `PUT /settings/woocommerce` - تحديث إعدادات التكامل

**الإعدادات:**

```json
{
  "woocommerce_enabled": true,
  "sync_products": true,
  "sync_orders": true,
  "sync_customers": true,
  "sync_direction": "bidirectional", // "bidirectional" | "asmaa_to_wc" | "wc_to_asmaa"
  "auto_sync": true,
  "sync_on_create": true,
  "sync_on_update": true
}
```

### 2. WooCommerce Settings Page

إضافة تبويب في WooCommerce Settings:

- Settings → Integration → Asmaa Salon

---

## معالجة التعارضات (Conflict Resolution)

### استراتيجية التعامل مع التعديلات المتزامنة:

1. **Timestamp-based**: آخر تعديل يفوز
2. **Source Priority**: Asmaa Salon له الأولوية للبيانات الخاصة بالصالون
3. **Manual Resolution**: إشعار للمستخدم عند التعارض

**الملف:** `includes/Services/Sync_Conflict_Resolver.php`

---

## Frontend Integration

### 1. تحديث Stores

**الملف:** `assets/src/stores/products.js`

إضافة:

- `wc_product_id` في state
- `syncWithWooCommerce()` method
- `isWooCommerceActive` computed

### 2. تحديث Views

**الملفات:**

- `assets/src/views/Products/Index.vue` - إضافة badge "WooCommerce Synced"
- `assets/src/views/Orders/Index.vue` - إضافة رابط لـ WooCommerce Order
- `assets/src/views/Customers/Index.vue` - إضافة رابط لـ WooCommerce Customer

### 3. إعدادات التكامل في UI

**الملف:** `assets/src/views/Settings/WooCommerce.vue` (جديد)

صفحة إعدادات التكامل مع WooCommerce

---

## Error Handling & Logging

### 1. Sync Log Table

**الملف:** `includes/Database/Schema.php`

```sql
CREATE TABLE wp_asmaa_wc_sync_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL, -- 'product', 'order', 'customer'
    entity_id BIGINT UNSIGNED NOT NULL,
    wc_entity_id BIGINT UNSIGNED NULL,
    sync_direction VARCHAR(20) NOT NULL, -- 'to_wc', 'from_wc'
    status VARCHAR(20) NOT NULL, -- 'success', 'failed', 'pending'
    error_message TEXT NULL,
    synced_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_entity (entity_type, entity_id),
    KEY idx_status (status),
    KEY idx_synced_at (synced_at)
);
```

### 2. Error Notification

عند فشل المزامنة:

- تسجيل في `sync_log`
- إشعار في Dashboard
- إعادة المحاولة التلقائية (Retry mechanism)

---

## Testing Strategy

### 1. Unit Tests

- Test WooCommerce Integration Service methods
- Test Sync Conflict Resolver
- Test Mapping functions

### 2. Integration Tests

- Test bidirectional sync
- Test conflict resolution
- Test error handling

### 3. Manual Testing Checklist

- [ ] إنشاء منتج في Asmaa Salon → يظهر في WooCommerce
- [ ] إنشاء منتج في WooCommerce → يظهر في Asmaa Salon
- [ ] تحديث منتج في Asmaa Salon → يتحدث في WooCommerce
- [ ] إنشاء طلب في POS → يظهر في WooCommerce
- [ ] إنشاء طلب في WooCommerce → يظهر في Asmaa Salon
- [ ] مزامنة العملاء ثنائية الاتجاه

---

## Migration Strategy (لا نقل بيانات)

- عدم نقل البيانات الموجودة
- البدء من الصفر: البيانات الجديدة فقط ستكون متزامنة
- إضافة `wc_synced_at = NULL` للبيانات القديمة (لتمييزها)

---

## Performance Optimization

### 1. Batch Sync

- مزامنة مجمعة للبيانات الكبيرة
- Queue system للمزامنة غير المتزامنة

### 2. Caching

- Cache WooCommerce connection status
- Cache sync status per entity

### 3. Background Processing

- استخدام WP-Cron للمزامنة المجمعة
- استخدام Action Scheduler (إذا متوفر)

---

## Security Considerations

1. **Permission Checks**: التأكد من صلاحيات المستخدم قبل المزامنة
2. **Data Validation**: التحقق من صحة البيانات قبل المزامنة
3. **Sanitization**: تنظيف جميع البيانات المدخلة
4. **Nonce Verification**: التحقق من nonce في جميع الطلبات

---

## Documentation

### 1. API Documentation

توثيق جميع endpoints الجديدة

### 2. User Guide

دليل المستخدم لاستخدام التكامل

### 3. Developer Guide

دليل المطورين لإضافة ميزات جديدة

---

## Rollout Plan

### Phase 1: Foundation (Week 1)

- إنشاء WooCommerce Integration Service
- إضافة حقول الربط في قاعدة البيانات
- إضافة WooCommerce Hooks

### Phase 2: Products Sync (Week 2)

- مزامنة المنتجات ثنائية الاتجاه
- تحديث Products Controller
- تحديث Frontend

### Phase 3: Orders & Customers (Week 3)

- مزامنة الطلبات
- مزامنة العملاء
- تحديث POS Integration

### Phase 4: Payments & Invoices (Week 4)

- ربط المدفوعات
- ربط الفواتير
- Testing & Bug Fixes

### Phase 5: Polish & Documentation (Week 5)

- إعدادات التكامل في UI
- Error Handling improvements
- Documentation
- Final Testing

---

## Files to Create/Modify

### New Files:

1. `includes/Services/WooCommerce_Integration_Service.php`
2. `includes/Services/Sync_Conflict_Resolver.php`
3. `includes/API/Controllers/Settings_Controller.php`
4. `assets/src/views/Settings/WooCommerce.vue`
5. `includes/Database/Migrations/Add_WooCommerce_Fields.php`

### Modified Files:

1. `includes/Plugin.php` - إضافة WooCommerce hooks
2. `includes/Database/Schema.php` - إضافة حقول الربط
3. `includes/API/Controllers/Products_Controller.php` - إضافة sync
4. `includes/API/Controllers/Orders_Controller.php` - إضافة sync
5. `includes/API/Controllers/Customers_Controller.php` - إضافة sync
6. `includes/API/Controllers/Payments_Controller.php` - إضافة sync
7. `includes/API/Controllers/POS_Controller.php` - استخدام WooCommerce
8. `assets/src/stores/products.js` - إضافة WooCommerce state
9. `assets/src/stores/orders.js` - إضافة WooCommerce state
10. `assets/src/stores/customers.js` - إضافة WooCommerce state

---

## Success Metrics

- ✅ جميع المنتجات الجديدة متزامنة تلقائياً
- ✅ جميع الطلبات متزامنة ثنائياً
- ✅ جميع العملاء متزامنون
- ✅ POS يعمل مع WooCommerce Products
- ✅ لا تعارضات في البيانات
- ✅ Performance: المزامنة < 1 ثانية لكل entity
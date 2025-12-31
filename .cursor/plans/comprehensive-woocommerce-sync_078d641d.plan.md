---
name: comprehensive-woocommerce-sync
overview: إعادة هيكلة النظام لاستخدام الجداول الأصلية لـ WooCommerce و WordPress مباشرة. العملاء من wc_customers، الموظفين من wp_users، المنتجات من wc_products. فقط الأشياء الإضافية الخاصة بالصالون تبقى في جداول منفصلة.
todos:
  - id: refactor-customers-controller
    content: إعادة كتابة Customers_Controller لاستخدام wc_customers مباشرة بدلاً من asmaa_customers
    status: completed
  - id: refactor-staff-controller
    content: إعادة كتابة Staff_Controller لاستخدام wp_users مباشرة بدلاً من asmaa_staff
    status: completed
  - id: refactor-products-controller
    content: إعادة كتابة Products_Controller لاستخدام wc_products مباشرة بدلاً من asmaa_products
    status: completed
  - id: optimize-database-indexes
    content: إضافة فهارس محسّنة لجميع الجداول لتحسين الأداء (composite indexes، covering indexes)
    status: completed
  - id: optimize-extended-data-tables
    content: تحسين فهارس جداول البيانات الإضافية للاستعلامات السريعة
    status: pending
  - id: update-all-references
    content: تحديث جميع المراجع في الكود من asmaa_customers/staff/products إلى wc_customers/wp_users/wc_products
    status: completed
  - id: create-extended-data-tables
    content: إنشاء جداول إضافية فقط للبيانات الخاصة بالصالون (loyalty_points، total_visits، إلخ)
    status: completed
  - id: update-frontend-stores
    content: تحديث جميع Frontend Stores لاستخدام APIs الجديدة
    status: completed
  - id: update-all-views
    content: تحديث جميع Views لتعرض البيانات مباشرة من WooCommerce و WordPress
    status: completed
  - id: apple-wallet-table
    content: إنشاء جدول asmaa_apple_wallet_passes لتخزين معلومات بطاقات Apple Wallet
    status: completed
  - id: apple-wallet-service
    content: إنشاء Apple_Wallet_Service لإنشاء وتحديث بطاقات Apple Wallet
    status: completed
  - id: qr-code-generation
    content: تنفيذ نظام إنشاء QR Codes فريدة لكل عميل لسكان النقاط
    status: completed
  - id: apple-wallet-web-service
    content: إنشاء Web Service endpoints لتحديث بطاقات Apple Wallet تلقائياً
    status: completed
  - id: loyalty-wallet-integration
    content: ربط منطق نقاط الولاء مع Apple Wallet (تحديث البطاقة عند تغيير النقاط)
    status: completed
  - id: membership-wallet-integration
    content: ربط منطق العضويات مع Apple Wallet (تحديث البطاقة عند تغيير العضوية)
    status: completed
  - id: apple-wallet-frontend
    content: إضافة أزرار "أضف إلى Apple Wallet" في صفحات نقاط الولاء والعضويات
    status: completed
---

# خطة إعادة الهيكلة: استخدام الجداول الأصلية مباشرة

## المبدأ الأساسي

**لا جداول منفصلة للأشياء الموجودة في WooCommerce و WordPress**

- **العملاء**: استخدام `wc_customers` (WooCommerce) مباشرة
- **الموظفين**: استخدام `wp_users` (WordPress) مباشرة  
- **المنتجات**: استخدام `wc_products` (WooCommerce) مباشرة
- **الطلبات**: استخدام `wc_orders` (WooCommerce) مباشرة

**فقط الأشياء الإضافية الخاصة بالصالون تبقى في جداول منفصلة:**

- الحجوزات (Bookings)
- الطابور (Queue)
- الخدمات (Services) - غير موجودة في WooCommerce
- نقاط الولاء (Loyalty)
- العضويات (Memberships)
- العمولات (Commissions)

## 1. إعادة هيكلة قاعدة البيانات

### جداول سيتم إزالتها/استبدالها:

1. **`asmaa_customers`** → استخدام `wc_customers` مباشرة
2. **`asmaa_staff`** → استخدام `wp_users` مباشرة
3. **`asmaa_products`** → استخدام `wc_products` مباشرة
4. **`asmaa_orders`** → استخدام `wc_orders` مباشرة

### جداول إضافية للبيانات الخاصة بالصالون:

إنشاء جداول إضافية صغيرة فقط للبيانات الإضافية التي لا توجد في WooCommerce/WordPress:

#### 1. `asmaa_customer_extended_data`

```sql
CREATE TABLE wp_asmaa_customer_extended_data (
    wc_customer_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    total_visits INT UNSIGNED NOT NULL DEFAULT 0,
    total_spent DECIMAL(10,3) NOT NULL DEFAULT 0,
    loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
    last_visit_at DATETIME NULL,
    preferred_staff_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (wc_customer_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (preferred_staff_id) REFERENCES wp_users(ID) ON DELETE SET NULL,
    -- Indexes للأداء العالي
    KEY idx_loyalty_points (loyalty_points),
    KEY idx_last_visit_at (last_visit_at),
    KEY idx_total_spent (total_spent),
    KEY idx_preferred_staff (preferred_staff_id),
    -- Composite index للاستعلامات الشائعة
    KEY idx_loyalty_active (loyalty_points, last_visit_at),
    KEY idx_customer_stats (total_visits, total_spent, loyalty_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. `asmaa_staff_extended_data`

```sql
CREATE TABLE wp_asmaa_staff_extended_data (
    wp_user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    position VARCHAR(100) NULL,
    hire_date DATE NULL,
    salary DECIMAL(10,3) NULL,
    commission_rate DECIMAL(5,2) NULL,
    photo VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    total_ratings INT UNSIGNED NOT NULL DEFAULT 0,
    total_services INT UNSIGNED NOT NULL DEFAULT 0,
    total_revenue DECIMAL(10,3) NOT NULL DEFAULT 0,
    service_ids JSON NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (wp_user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    -- Indexes للأداء العالي
    KEY idx_is_active (is_active),
    KEY idx_rating (rating),
    KEY idx_position (position),
    KEY idx_total_revenue (total_revenue),
    KEY idx_total_services (total_services),
    -- Composite indexes للاستعلامات الشائعة
    KEY idx_active_rating (is_active, rating),
    KEY idx_staff_performance (is_active, total_revenue, rating),
    KEY idx_commission_stats (is_active, commission_rate, total_revenue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. `asmaa_product_extended_data`

```sql
CREATE TABLE wp_asmaa_product_extended_data (
    wc_product_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    purchase_price DECIMAL(10,3) NOT NULL DEFAULT 0,
    min_stock_level INT NOT NULL DEFAULT 0,
    barcode VARCHAR(100) NULL,
    brand VARCHAR(100) NULL,
    unit VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (wc_product_id) REFERENCES wp_posts(ID) ON DELETE CASCADE,
    -- Indexes للأداء العالي
    KEY idx_barcode (barcode),
    KEY idx_brand (brand),
    KEY idx_min_stock_level (min_stock_level),
    -- Composite index للاستعلامات الشائعة
    KEY idx_brand_barcode (brand, barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### جداول تبقى كما هي (خاصة بالصالون):

- `asmaa_services` - الخدمات غير موجودة في WooCommerce
- `asmaa_bookings` - الحجوزات خاصة بالصالون
- `asmaa_queue_tickets` - الطابور خاص بالصالون
- `asmaa_loyalty_transactions` - نقاط الولاء خاصة بالصالون
- `asmaa_customer_memberships` - العضويات خاصة بالصالون
- `asmaa_membership_plans` - خطط العضويات خاصة بالصالون
- `asmaa_staff_commissions` - العمولات خاصة بالصالون
- `asmaa_staff_ratings` - تقييمات الموظفين خاصة بالصالون
- `asmaa_invoices` - الفواتير خاصة بالصالون (يمكن ربطها مع wc_orders)
- `asmaa_payments` - المدفوعات خاصة بالصالون (يمكن ربطها مع wc_orders)

## 2. إعادة كتابة Controllers

### Customers_Controller

**قبل:**

```php
// استخدام asmaa_customers
$table = $wpdb->prefix . 'asmaa_customers';
$customers = $wpdb->get_results("SELECT * FROM {$table}");
```

**بعد:**

```php
// استخدام WooCommerce Customers مباشرة
$customers = wc_get_customers([
    'number' => $per_page,
    'offset' => $offset,
]);

// الحصول على البيانات الإضافية من asmaa_customer_extended_data
foreach ($customers as $customer) {
    $extended = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}asmaa_customer_extended_data WHERE wc_customer_id = %d",
        $customer->get_id()
    ));
    $customer->extended_data = $extended;
}
```

### Staff_Controller

**قبل:**

```php
// استخدام asmaa_staff
$table = $wpdb->prefix . 'asmaa_staff';
$staff = $wpdb->get_results("SELECT * FROM {$table}");
```

**بعد:**

```php
// استخدام WordPress Users مباشرة
$staff_users = get_users([
    'role__in' => ['asmaa_staff', 'asmaa_manager', 'asmaa_admin'],
    'number' => $per_page,
    'offset' => $offset,
]);

// الحصول على البيانات الإضافية من asmaa_staff_extended_data
foreach ($staff_users as $user) {
    $extended = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}asmaa_staff_extended_data WHERE wp_user_id = %d",
        $user->ID
    ));
    $user->extended_data = $extended;
}
```

### Products_Controller

**قبل:**

```php
// استخدام asmaa_products
$table = $wpdb->prefix . 'asmaa_products';
$products = $wpdb->get_results("SELECT * FROM {$table}");
```

**بعد:**

```php
// استخدام WooCommerce Products مباشرة
$products = wc_get_products([
    'limit' => $per_page,
    'offset' => $offset,
    'status' => 'publish',
]);

// الحصول على البيانات الإضافية من asmaa_product_extended_data
foreach ($products as $product) {
    $extended = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}asmaa_product_extended_data WHERE wc_product_id = %d",
        $product->get_id()
    ));
    $product->extended_data = $extended;
}
```

### Orders_Controller

**قبل:**

```php
// استخدام asmaa_orders
$table = $wpdb->prefix . 'asmaa_orders';
$orders = $wpdb->get_results("SELECT * FROM {$table}");
```

**بعد:**

```php
// استخدام WooCommerce Orders مباشرة
$orders = wc_get_orders([
    'limit' => $per_page,
    'offset' => $offset,
    'status' => 'any',
]);
```

## 3. تحسين الأداء والفهارس

### 3.1 فهارس محسّنة للجداول الأساسية

#### جدول `asmaa_bookings`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_bookings
ADD KEY idx_customer_status (wc_customer_id, status),
ADD KEY idx_staff_status (wp_user_id, status),
ADD KEY idx_date_status (booking_date, status),
ADD KEY idx_service_date (service_id, booking_date),
ADD KEY idx_completed_at (completed_at),
ADD KEY idx_confirmed_at (confirmed_at);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_customer_date_status (wc_customer_id, booking_date, status),
ADD KEY idx_staff_date_status (wp_user_id, booking_date, status);
```

#### جدول `asmaa_queue_tickets`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_queue_tickets
ADD KEY idx_customer_status (wc_customer_id, status),
ADD KEY idx_staff_status (wp_user_id, status),
ADD KEY idx_service_status (service_id, status),
ADD KEY idx_created_status (created_at, status),
ADD KEY idx_called_at (called_at),
ADD KEY idx_completed_at (completed_at);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_today_status (DATE(created_at), status),
ADD KEY idx_customer_today (wc_customer_id, DATE(created_at));
```

#### جدول `asmaa_loyalty_transactions`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_loyalty_transactions
ADD KEY idx_customer_type (wc_customer_id, type),
ADD KEY idx_customer_created (wc_customer_id, created_at),
ADD KEY idx_type_created (type, created_at),
ADD KEY idx_reference (reference_type, reference_id);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_customer_type_date (wc_customer_id, type, created_at);
```

#### جدول `asmaa_customer_memberships`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_customer_memberships
ADD KEY idx_customer_status (wc_customer_id, status),
ADD KEY idx_plan_status (membership_plan_id, status),
ADD KEY idx_end_date (end_date),
ADD KEY idx_start_date (start_date),
ADD KEY idx_status_end_date (status, end_date);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_customer_active (wc_customer_id, status, end_date),
ADD KEY idx_expiring_soon (status, end_date);
```

#### جدول `asmaa_staff_commissions`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_staff_commissions
ADD KEY idx_staff_status (wp_user_id, status),
ADD KEY idx_order_status (order_id, status),
ADD KEY idx_booking_status (booking_id, status),
ADD KEY idx_status_created (status, created_at),
ADD KEY idx_approved_at (approved_at),
ADD KEY idx_paid_at (paid_at);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_staff_status_date (wp_user_id, status, created_at),
ADD KEY idx_pending_approval (status, created_at);
```

#### جدول `asmaa_invoices`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_invoices
ADD KEY idx_customer_status (wc_customer_id, status),
ADD KEY idx_order_status (wc_order_id, status),
ADD KEY idx_status_due_date (status, due_date),
ADD KEY idx_issue_date (issue_date),
ADD KEY idx_due_date (due_date),
ADD KEY idx_paid_amount (paid_amount);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_customer_status_date (wc_customer_id, status, issue_date),
ADD KEY idx_unpaid_due (status, due_date);
```

#### جدول `asmaa_payments`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_payments
ADD KEY idx_customer_status (wc_customer_id, status),
ADD KEY idx_invoice_status (invoice_id, status),
ADD KEY idx_order_status (wc_order_id, status),
ADD KEY idx_payment_method (payment_method),
ADD KEY idx_payment_date (payment_date),
ADD KEY idx_status_date (status, payment_date);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_customer_date (wc_customer_id, payment_date),
ADD KEY idx_method_date (payment_method, payment_date);
```

#### جدول `asmaa_services`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_services
ADD KEY idx_category_active (category, is_active),
ADD KEY idx_price (price),
ADD KEY idx_duration (duration),
ADD KEY idx_active_category (is_active, category);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_active_category_price (is_active, category, price);
```

#### جدول `asmaa_inventory_movements`:

```sql
-- فهارس إضافية محسّنة
ALTER TABLE wp_asmaa_inventory_movements
ADD KEY idx_product_type (wc_product_id, type),
ADD KEY idx_type_date (type, movement_date),
ADD KEY idx_reference (reference_type, reference_id),
ADD KEY idx_performed_by (performed_by),
ADD KEY idx_movement_date (movement_date);

-- Composite index للاستعلامات الشائعة
ADD KEY idx_product_type_date (wc_product_id, type, movement_date);
```

### 3.2 تحسين فهارس الجداول المرتبطة

#### تحديث فهارس الجداول المرتبطة:

```sql
-- asmaa_order_items
ALTER TABLE wp_asmaa_order_items
ADD KEY idx_order_type (order_id, item_type),
ADD KEY idx_product_order (wc_product_id, order_id),
ADD KEY idx_staff_order (staff_id, order_id);

-- asmaa_invoice_items
ALTER TABLE wp_asmaa_invoice_items
ADD KEY idx_invoice_total (invoice_id, total);

-- asmaa_membership_service_usage
ALTER TABLE wp_asmaa_membership_service_usage
ADD KEY idx_membership_service (customer_membership_id, service_id),
ADD KEY idx_membership_date (customer_membership_id, used_at),
ADD KEY idx_service_date (service_id, used_at);
```

### 3.3 تحليل الأداء

#### استعلامات للتحقق من الأداء:

```sql
-- تحليل الاستعلامات البطيئة
EXPLAIN SELECT * FROM wp_asmaa_bookings 
WHERE wc_customer_id = 123 AND status = 'pending' 
ORDER BY booking_date DESC;

-- تحليل استخدام الفهارس
SHOW INDEX FROM wp_asmaa_bookings;

-- تحليل حجم الجداول
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
AND table_name LIKE 'wp_asmaa_%'
ORDER BY size_mb DESC;
```

### 3.4 أفضل الممارسات للأداء

1. **استخدام Composite Indexes:**

   - للاستعلامات التي تستخدم عدة أعمدة في WHERE
   - ترتيب الأعمدة حسب الانتقائية (الأكثر انتقائية أولاً)

2. **Covering Indexes:**

   - إضافة جميع الأعمدة المطلوبة في SELECT إلى الفهرس
   - يقلل من الحاجة لقراءة الجدول

3. **تحليل الاستعلامات:**

   - استخدام EXPLAIN لتحليل خطط التنفيذ
   - التأكد من استخدام الفهارس بشكل صحيح

4. **صيانة قاعدة البيانات:**

   - تحليل الجداول بانتظام: `ANALYZE TABLE`
   - تحسين الجداول: `OPTIMIZE TABLE`
   - تنظيف البيانات المحذوفة (soft deletes)

## 4. تحديث جميع المراجع في الكود

### البحث والاستبدال:

1. **العملاء:**

   - `asmaa_customers` → استخدام `wc_get_customer()` و `wc_get_customers()`
   - `customer_id` → `wc_customer_id` (في الجداول المرتبطة)

2. **الموظفين:**

   - `asmaa_staff` → استخدام `get_users()` و `get_user_by()`
   - `staff_id` → `wp_user_id` (في الجداول المرتبطة)

3. **المنتجات:**

   - `asmaa_products` → استخدام `wc_get_product()` و `wc_get_products()`
   - `product_id` → `wc_product_id` (في الجداول المرتبطة)

4. **الطلبات:**

   - `asmaa_orders` → استخدام `wc_get_order()` و `wc_get_orders()`
   - `order_id` → `wc_order_id` (في الجداول المرتبطة)

### تحديث الجداول المرتبطة:

#### Bookings Table:

```sql
ALTER TABLE wp_asmaa_bookings
CHANGE COLUMN customer_id wc_customer_id BIGINT UNSIGNED NOT NULL,
CHANGE COLUMN staff_id wp_user_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (wc_customer_id) REFERENCES wp_users(ID),
ADD FOREIGN KEY (wp_user_id) REFERENCES wp_users(ID);
```

#### Queue Tickets Table:

```sql
ALTER TABLE wp_asmaa_queue_tickets
CHANGE COLUMN customer_id wc_customer_id BIGINT UNSIGNED NULL,
CHANGE COLUMN staff_id wp_user_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (wc_customer_id) REFERENCES wp_users(ID),
ADD FOREIGN KEY (wp_user_id) REFERENCES wp_users(ID);
```

#### Order Items Table:

```sql
ALTER TABLE wp_asmaa_order_items
CHANGE COLUMN item_id wc_product_id BIGINT UNSIGNED NULL,
-- item_type يمكن أن يكون 'product' أو 'service'
-- إذا كان 'service' فـ wc_product_id = NULL
```

## 4. تحديث الواجهة الأمامية (Frontend)

### Stores:

#### customers.js

```javascript
// قبل
const customers = ref([])
const response = await api.get('/customers')

// بعد
const customers = ref([])
const response = await api.get('/customers') // API يعيد WooCommerce Customers
// البيانات الإضافية في customer.extended_data
```

#### staff.js

```javascript
// قبل
const staff = ref([])
const response = await api.get('/staff')

// بعد
const staff = ref([])
const response = await api.get('/staff') // API يعيد WordPress Users
// البيانات الإضافية في user.extended_data
```

#### products.js

```javascript
// قبل
const products = ref([])
const response = await api.get('/products')

// بعد
const products = ref([])
const response = await api.get('/products') // API يعيد WooCommerce Products
// البيانات الإضافية في product.extended_data
```

### Views:

جميع Views يجب أن تعرض البيانات مباشرة من WooCommerce/WordPress:

- `Customers/Index.vue`: عرض `wc_customers` مباشرة
- `Staff/Index.vue`: عرض `wp_users` مباشرة
- `Products/Index.vue`: عرض `wc_products` مباشرة
- `Orders/Index.vue`: عرض `wc_orders` مباشرة

## 5. خطوات التنفيذ

### المرحلة 1: إنشاء الجداول الإضافية

1. إنشاء `asmaa_customer_extended_data` مع فهارس محسّنة
2. إنشاء `asmaa_staff_extended_data` مع فهارس محسّنة
3. إنشاء `asmaa_product_extended_data` مع فهارس محسّنة
4. إنشاء `asmaa_apple_wallet_passes` مع فهارس محسّنة

### المرحلة 2: تحسين الأداء والفهارس

1. إضافة فهارس محسّنة لجميع الجداول
2. إنشاء composite indexes للاستعلامات الشائعة
3. تحسين فهارس جداول البيانات الإضافية
4. تحليل الأداء والتحقق من تحسين الاستعلامات

### المرحلة 3: تحديث Controllers

1. إعادة كتابة `Customers_Controller` لاستخدام WooCommerce
2. إعادة كتابة `Staff_Controller` لاستخدام WordPress Users
3. إعادة كتابة `Products_Controller` لاستخدام WooCommerce
4. إعادة كتابة `Orders_Controller` لاستخدام WooCommerce
5. تحديث جميع Controllers الأخرى التي تستخدم هذه الجداول

### المرحلة 4: تحديث الجداول المرتبطة

1. تحديث `asmaa_bookings` لاستخدام `wc_customer_id` و `wp_user_id`
2. تحديث `asmaa_queue_tickets` لاستخدام `wc_customer_id` و `wp_user_id`
3. تحديث `asmaa_order_items` لاستخدام `wc_product_id`
4. تحديث جميع الجداول المرتبطة الأخرى

### المرحلة 5: تحديث الواجهة الأمامية

1. تحديث جميع Stores
2. تحديث جميع Views
3. تحديث جميع Components التي تستخدم هذه البيانات
4. اختبار جميع الصفحات

### المرحلة 6: الاختبار والتنظيف

1. اختبار جميع الوظائف
2. التحقق من عدم وجود أخطاء
3. تحليل الأداء والتحقق من سرعة الاستعلامات
4. تحديث التوثيق

## 6. الملفات المراد تعديلها

### ملفات جديدة:

1. `includes/Database/Migrations/Create_Extended_Data_Tables.php`
2. `includes/Database/Migrations/Optimize_Database_Indexes.php`
3. `includes/Services/WooCommerce_Customers_Service.php` - wrapper لـ WooCommerce Customers
4. `includes/Services/WordPress_Users_Service.php` - wrapper لـ WordPress Users
5. `includes/Services/Apple_Wallet_Service.php`
6. `includes/API/Controllers/Apple_Wallet_Controller.php`
7. `includes/Helpers/QR_Code_Generator.php`

### ملفات سيتم إعادة كتابتها بالكامل:

1. `includes/API/Controllers/Customers_Controller.php`
2. `includes/API/Controllers/Staff_Controller.php`
3. `includes/API/Controllers/Products_Controller.php`
4. `includes/API/Controllers/Orders_Controller.php`

### ملفات سيتم تحديثها:

1. `includes/API/Controllers/Bookings_Controller.php` - تحديث المراجع
2. `includes/API/Controllers/Queue_Controller.php` - تحديث المراجع
3. `includes/API/Controllers/POS_Controller.php` - تحديث المراجع
4. `includes/API/Controllers/Loyalty_Controller.php` - تحديث المراجع
5. `includes/API/Controllers/Memberships_Controller.php` - تحديث المراجع
6. `includes/API/Controllers/Commissions_Controller.php` - تحديث المراجع
7. جميع ملفات Views في `assets/src/views/`
8. جميع ملفات Stores في `assets/src/stores/`

## 7. معايير النجاح

- ✅ لا توجد جداول منفصلة للعملاء، الموظفين، المنتجات، الطلبات
- ✅ جميع البيانات تأتي مباشرة من WooCommerce و WordPress
- ✅ البيانات الإضافية الخاصة بالصالون في جداول منفصلة صغيرة
- ✅ جميع Views تعرض البيانات مباشرة من الجداول الأصلية
- ✅ لا توجد مزامنة - البيانات في مكانها الأصلي
- ✅ الأداء أفضل (لا حاجة للمزامنة)
- ✅ البيانات متسقة دائماً (مصدر واحد للحقيقة)

## 8. ملاحظات مهمة

### البيانات الإضافية:

البيانات الإضافية الخاصة بالصالون (مثل `loyalty_points`، `total_visits`) ستكون في جداول منفصلة صغيرة مرتبطة بـ WooCommerce/WordPress IDs:

- `asmaa_customer_extended_data.wc_customer_id` → `wp_users.ID` (للعملاء)
- `asmaa_staff_extended_data.wp_user_id` → `wp_users.ID` (للموظفين)
- `asmaa_product_extended_data.wc_product_id` → `wp_posts.ID` (للمنتجات)

### العلاقات:

- الحجوزات مرتبطة بـ `wc_customer_id` و `wp_user_id`
- الطابور مرتبط بـ `wc_customer_id` و `wp_user_id`
- نقاط الولاء مرتبطة بـ `wc_customer_id`
- العضويات مرتبطة بـ `wc_customer_id`
- العمولات مرتبطة بـ `wp_user_id`

### الأمان:

- استخدام WooCommerce و WordPress APIs الرسمية
- احترام الصلاحيات الموجودة في WooCommerce و WordPress
- التأكد من أن جميع العمليات آمنة ومصرح بها

### ملاحظات مهمة:

#### لا نقل بيانات:

- **لا يتم نقل البيانات من الجداول القديمة**
- البيانات الجديدة فقط ستستخدم الجداول الأصلية
- الجداول القديمة تبقى للبيانات الموجودة مسبقاً
- يمكن إزالة الجداول القديمة لاحقاً بعد التأكد من عدم الحاجة إليها

#### تحسين الأداء:

- جميع الجداول تستخدم `ENGINE=InnoDB` للأداء العالي
- جميع الفهارس محسّنة للاستعلامات الشائعة
- Composite indexes للاستعلامات المعقدة
- Regular maintenance للجداول (ANALYZE, OPTIMIZE)

## 9. تكامل Apple Wallet لنقاط الولاء والعضويات

### المبدأ الأساسي

ربط نقاط الولاء والعضويات مع Apple Wallet لإنشاء بطاقات رقمية للعملاء تظهر في iPhone. عند الضغط على البطاقة، تظهر معلومات النقاط والعضوية وQR Code لسكان النقاط الجديدة.

### 9.1 جدول Apple Wallet Passes

إنشاء جدول لتخزين معلومات بطاقات Apple Wallet:

```sql
CREATE TABLE wp_asmaa_apple_wallet_passes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    wc_customer_id BIGINT UNSIGNED NOT NULL,
    pass_type_identifier VARCHAR(255) NOT NULL,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    authentication_token VARCHAR(100) NOT NULL UNIQUE,
    pass_data JSON NOT NULL,
    qr_code_data TEXT NOT NULL,
    qr_code_url VARCHAR(500) NULL,
    last_updated DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY idx_serial_number (serial_number),
    UNIQUE KEY idx_auth_token (authentication_token),
    KEY idx_wc_customer_id (wc_customer_id),
    FOREIGN KEY (wc_customer_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);
```

### 9.2 Apple Wallet Service

إنشاء خدمة `Apple_Wallet_Service.php`:

#### الوظائف الأساسية:

1. **`create_pass(int $wc_customer_id): array`**

   - إنشاء بطاقة Apple Wallet جديدة للعميل
   - توليد QR Code فريد
   - إنشاء Pass Type ID و Serial Number
   - حفظ البيانات في الجدول

2. **`update_pass(int $wc_customer_id): bool`**

   - تحديث بطاقة Apple Wallet عند تغيير النقاط أو العضوية
   - تحديث QR Code إذا لزم الأمر
   - إرسال إشعار لتحديث البطاقة في iPhone

3. **`get_pass_data(int $wc_customer_id): array`**

   - الحصول على بيانات البطاقة (النقاط، العضوية، الخدمات المتاحة)
   - توليد QR Code جديد إذا لزم الأمر

4. **`generate_qr_code(int $wc_customer_id): string`**

   - توليد QR Code فريد للعميل
   - يحتوي على: customer_id، timestamp، nonce
   - يمكن استخدامه لسكان النقاط الجديدة

### 9.3 محتوى بطاقة Apple Wallet

#### البيانات التي تظهر في البطاقة:

```json
{
  "formatVersion": 1,
  "passTypeIdentifier": "pass.com.asmaasalon.loyalty",
  "serialNumber": "CUST-12345-UNIQUE",
  "teamIdentifier": "TEAM_ID",
  "organizationName": "Asmaa Al-Jarallah Salon",
  "description": "Loyalty Card",
  "logoText": "Asmaa Salon",
  "foregroundColor": "rgb(255, 255, 255)",
  "backgroundColor": "rgb(187, 160, 122)",
  "storeCard": {
    "primaryFields": [
      {
        "key": "points",
        "label": "نقاط الولاء",
        "value": "150"
      }
    ],
    "secondaryFields": [
      {
        "key": "membership",
        "label": "العضوية",
        "value": "الخطة الأساسية"
      },
      {
        "key": "expires",
        "label": "تنتهي في",
        "value": "2025-12-31"
      }
    ],
    "auxiliaryFields": [
      {
        "key": "services",
        "label": "الخدمات المتاحة",
        "value": "5 خدمات"
      }
    ],
    "barcode": {
      "message": "QR_CODE_DATA",
      "format": "PKBarcodeFormatQR",
      "messageEncoding": "iso-8859-1"
    }
  },
  "webServiceURL": "https://yoursite.com/wp-json/asmaa-salon/v1/apple-wallet",
  "authenticationToken": "AUTH_TOKEN"
}
```

### 9.4 QR Code للعميل

#### محتوى QR Code:

```json
{
  "type": "loyalty_scan",
  "customer_id": 123,
  "timestamp": 1703123456,
  "nonce": "random_string",
  "action": "earn_points"
}
```

#### استخدام QR Code:

- عند سكان QR Code من البطاقة في iPhone
- يفتح صفحة في المتصفح أو التطبيق
- يعرض معلومات العميل والنقاط
- يمكن للموظف إضافة نقاط جديدة

### 9.5 Web Service لتحديث البطاقات

Apple Wallet يحتاج Web Service endpoints لتحديث البطاقات تلقائياً:

#### Endpoints المطلوبة:

1. **`GET /apple-wallet/v1/devices/{device_id}/registrations/{pass_type_id}`**

   - الحصول على قائمة البطاقات المسجلة على الجهاز

2. **`POST /apple-wallet/v1/devices/{device_id}/registrations/{pass_type_id}/{serial_number}`**

   - تسجيل بطاقة جديدة على الجهاز

3. **`DELETE /apple-wallet/v1/devices/{device_id}/registrations/{pass_type_id}/{serial_number}`**

   - إلغاء تسجيل بطاقة

4. **`GET /apple-wallet/v1/passes/{pass_type_id}/{serial_number}`**

   - الحصول على بيانات البطاقة المحدثة

5. **`POST /apple-wallet/v1/log`**

   - تسجيل أخطاء من Apple Wallet

### 9.6 ربط منطق نقاط الولاء

#### تحديث `Loyalty_Controller`:

عند كسب أو استبدال النقاط:

```php
// بعد تحديث النقاط
$wpdb->update($customers_table, ['loyalty_points' => $balance_after], ['id' => $customer_id]);

// تحديث بطاقة Apple Wallet
\AsmaaSalon\Services\Apple_Wallet_Service::update_pass($customer_id);
```

#### تحديث `Memberships_Controller`:

عند إنشاء أو تحديث العضوية:

```php
// بعد إنشاء/تحديث العضوية
$wpdb->insert($memberships_table, $data);

// تحديث بطاقة Apple Wallet
\AsmaaSalon\Services\Apple_Wallet_Service::update_pass($customer_id);
```

### 9.7 Frontend Integration

#### إضافة أزرار "أضف إلى Apple Wallet":

في `Loyalty/Index.vue` و `Memberships/Index.vue`:

```vue
<template>
  <div v-if="customerHasPass">
    <button @click="addToAppleWallet" class="apple-wallet-button">
      <img src="/apple-wallet-icon.png" alt="Apple Wallet" />
      أضف إلى Apple Wallet
    </button>
  </div>
</template>

<script setup>
async function addToAppleWallet() {
  const response = await api.post(`/customers/${customerId}/apple-wallet/create`)
  const passUrl = response.data.pass_url
  
  // فتح رابط البطاقة لإضافتها إلى Apple Wallet
  window.location.href = passUrl
}
</script>
```

### 9.8 QR Code Scanner للموظفين

إنشاء صفحة لسكان QR Code من بطاقة العميل:

#### `QRScanner.vue`:

```vue
<template>
  <div class="qr-scanner">
    <h2>سكان بطاقة العميل</h2>
    <qrcode-reader @decode="onDecode" />
    <div v-if="customerData">
      <h3>{{ customerData.name }}</h3>
      <p>النقاط الحالية: {{ customerData.loyalty_points }}</p>
      <p>العضوية: {{ customerData.membership }}</p>
      <button @click="earnPoints">إضافة نقاط</button>
    </div>
  </div>
</template>
```

### 9.9 خطوات التنفيذ

#### المرحلة 7: Apple Wallet Integration

1. **إنشاء الجدول:**

   - إنشاء `asmaa_apple_wallet_passes` table
   - إضافة migration script

2. **إنشاء Apple Wallet Service:**

   - إنشاء `Apple_Wallet_Service.php`
   - تنفيذ `create_pass()`, `update_pass()`, `generate_qr_code()`

3. **إنشاء Web Service Endpoints:**

   - إنشاء `Apple_Wallet_Controller.php`
   - تنفيذ جميع endpoints المطلوبة من Apple

4. **ربط مع نقاط الولاء:**

   - تحديث `Loyalty_Controller` لاستدعاء `update_pass()`
   - تحديث `POS_Controller` لاستدعاء `update_pass()`

5. **ربط مع العضويات:**

   - تحديث `Memberships_Controller` لاستدعاء `update_pass()`
   - تحديث عند إنشاء/تحديث/تجديد العضوية

6. **Frontend Integration:**

   - إضافة أزرار "أضف إلى Apple Wallet"
   - إنشاء صفحة QR Scanner
   - إضافة مؤشرات حالة البطاقة

7. **اختبار التكامل:**

   - اختبار إنشاء البطاقة
   - اختبار تحديث البطاقة تلقائياً
   - اختبار QR Code scanning
   - اختبار على iPhone فعلي

### 9.10 الملفات المراد إنشاؤها/تعديلها

#### ملفات جديدة:

1. `includes/Database/Migrations/Create_Apple_Wallet_Table.php`
2. `includes/Services/Apple_Wallet_Service.php`
3. `includes/API/Controllers/Apple_Wallet_Controller.php`
4. `includes/Helpers/QR_Code_Generator.php`
5. `assets/src/views/QRScanner.vue`
6. `assets/src/components/AppleWalletButton.vue`

#### ملفات سيتم تحديثها:

1. `includes/API/Controllers/Loyalty_Controller.php` - إضافة استدعاءات `update_pass()`
2. `includes/API/Controllers/Memberships_Controller.php` - إضافة استدعاءات `update_pass()`
3. `includes/API/Controllers/POS_Controller.php` - إضافة استدعاءات `update_pass()`
4. `includes/API/Controllers/Customers_Controller.php` - إضافة endpoint لإنشاء البطاقة
5. `assets/src/views/Loyalty/Index.vue` - إضافة زر Apple Wallet
6. `assets/src/views/Memberships/Index.vue` - إضافة زر Apple Wallet
7. `assets/src/views/Customers/Profile.vue` - إضافة زر Apple Wallet

### 9.11 متطلبات Apple Wallet

#### شهادات SSL:

- يجب أن يكون الموقع على HTTPS
- شهادة SSL صالحة من Apple

#### Pass Type ID:

- التسجيل في Apple Developer Program
- إنشاء Pass Type ID
- إنشاء شهادات للـ Pass Type ID

#### Web Service:

- يجب أن يكون Web Service على HTTPS
- يجب أن يدعم Push Notifications لتحديث البطاقات

### 9.12 معايير النجاح

- ✅ يمكن للعملاء إضافة بطاقاتهم إلى Apple Wallet
- ✅ البطاقة تظهر النقاط الحالية والعضوية
- ✅ QR Code يعمل بشكل صحيح لسكان النقاط
- ✅ البطاقة تتحدث تلقائياً عند تغيير النقاط أو العضوية
- ✅ الموظفون يمكنهم سكان QR Code لإضافة نقاط
- ✅ الخدمات المتاحة للـ redeem تظهر في البطاقة
- ✅ يعمل على iPhone و iPad

### 9.13 ملاحظات مهمة

#### الأمان:

- QR Code يجب أن يحتوي على nonce لمنع إعادة الاستخدام
- Authentication Token يجب أن يكون فريد وآمن
- Web Service يجب أن يتحقق من صحة الطلبات

#### الأداء:

- تحديث البطاقات يجب أن يكون غير متزامن (async)
- استخدام Queue system لتحديث البطاقات
- Cache بيانات البطاقات لتقليل استعلامات قاعدة البيانات

#### تجربة المستخدم:

- إضافة loading states عند إنشاء البطاقة
- إظهار رسائل واضحة عند النجاح/الفشل
- دعم iOS و Android (Android يمكن استخدام Google Pay)
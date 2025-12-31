---
name: Enhanced Data Flow and POS Integration Plan
overview: تطبيق تحسينات شاملة على رحلة البيانات من الحجز إلى الدفع، مع دعم تعدد الموظفات في الطلب الواحد، التعامل مع المدفوعات المسبقة (العربون)، وإضافة دعم Offline-First للـ POS
todos:
  - id: booking-queue-link
    content: إضافة booking_id إلى asmaa_queue_tickets وتحسين الربط بين الحجوزات والطابور
    status: completed
  - id: multi-staff-order-items
    content: تطبيق حفظ وقراءة staff_id في WooCommerce Order Item Meta
    status: completed
  - id: partial-payments
    content: إضافة دعم المدفوعات المسبقة (العربون) في POS
    status: completed
  - id: offline-pos
    content: تطبيق Offline-First للـ POS باستخدام IndexedDB
    status: completed
  - id: booking-efficiency-reports
    content: إضافة تقارير كفاءة المواعيد
    status: completed
    dependencies:
      - booking-queue-link
  - id: commissions-from-meta
    content: تحديث حساب العمولات لاستخدام Order Item Meta مع Virtual Products
    status: completed
    dependencies:
      - multi-staff-order-items
  - id: virtual-products-service
    content: إنشاء خدمة إدارة Virtual Products للخدمات
    status: completed
  - id: offline-idempotency
    content: تطبيق Idempotency في Batch Sync باستخدام client_side_id (توليد مبكر في sessionStorage)
    status: completed
    dependencies:
      - offline-pos
  - id: dexie-integration
    content: استبدال idb بـ Dexie.js للتعامل مع IndexedDB
    status: completed
    dependencies:
      - offline-pos
  - id: catalog-visibility
    content: ضبط Catalog Visibility للـ Virtual Products إلى Hidden
    status: completed
    dependencies:
      - virtual-products-service
---

# خطة تحسين رحلة البيانات وتكامل POS المتقدم

## نظرة عامة

تطبيق تحسينات على رحلة البيانات الكاملة من الحجز إلى الدفع، مع التركيز على:

1. ربط الحجوزات بالطابور بشكل سلس
2. دعم تعدد الموظفات في طلب واحد عبر Order Item Meta
3. التعامل مع المدفوعات المسبقة (العربون)
4. دعم Offline-First للـ POS

## 1. ربط الحجوزات بالطابور (Seamless Booking-to-Queue Switch)

### الملفات المتأثرة:

- `includes/Database/Schema.php` - إضافة `booking_id` إلى `asmaa_queue_tickets`
- `includes/API/Controllers/Queue_Controller.php` - تحسين ربط الحجز بالطابور
- `includes/API/Controllers/Bookings_Controller.php` - إضافة endpoint لتحويل الحجز إلى طابور
- `includes/API/Controllers/POS_Controller.php` - ربط بيانات الحجز/الطابور بالطلب النهائي

### التغييرات:

#### 1.1 تحديث Schema للطابور

```php
// في Schema.php - جدول asmaa_queue_tickets
// إضافة عمود booking_id إذا لم يكن موجوداً
booking_id BIGINT UNSIGNED NULL,
KEY idx_booking_id (booking_id)
```

#### 1.2 تحسين Queue_Controller

- في `create_item()`: التأكد من حفظ `booking_id` عند إنشاء تذكرة من حجز
- إضافة endpoint جديد: `POST /queue/from-booking/:booking_id` لتحويل الحجز مباشرة إلى طابور
- عند تحويل الحجز، نسخ بيانات الخدمة والموظفة تلقائياً

#### 1.3 تحسين Bookings_Controller

- إضافة endpoint: `POST /bookings/:id/convert-to-queue` لتحويل الحجز إلى تذكرة طابور
- تحديث حالة الحجز إلى "arrived" عند التحويل
- ربط `queue_ticket_id` بالحجز

#### 1.4 ربط البيانات في POS

- عند إنشاء طلب من POS، التحقق من وجود `booking_id` أو `queue_ticket_id`
- حفظ هذه الروابط في Order Meta في WooCommerce
- استخدامها في التقارير لتحليل "كفاءة المواعيد"

## 2. تعدد الموظفات في الطلب الواحد (Multi-Staff Order Items)

### الملفات المتأثرة:

- `includes/API/Controllers/POS_Controller.php` - حفظ `staff_id` في Order Item Meta وإنشاء Virtual Products للخدمات
- `includes/API/Controllers/Orders_Controller.php` - قراءة `staff_id` من Order Items
- `includes/API/Controllers/Commissions_Controller.php` - استخدام Order Item Meta لحساب العمولات
- `includes/Services/Product_Service.php` - خدمة جديدة لإدارة Virtual Products للخدمات

### التغييرات:

#### 2.1 استخدام Virtual Products للخدمات (بدلاً من Fees)

**السبب المعماري**: WooCommerce يعامل Fees بشكل مختلف في التقارير. استخدام Virtual Products يضمن:

- توحيد معالجة جميع العناصر عبر `get_items()`
- دعم تقارير WooCommerce الافتراضية للخدمات
- سهولة التكامل مع أنظمة المحاسبة الخارجية

في `POS_Controller::process_order()`:

```php
// عند إضافة خدمة (Service)
if ($item_type === 'service') {
    // الحصول على أو إنشاء Virtual Product للخدمة
    $service_product_id = $this->get_or_create_service_product($item_id, $item_name, $unit_price);
    
    $wc_product = wc_get_product($service_product_id);
    if (!$wc_product) {
        throw new \Exception(__('Service product not found', 'asmaa-salon'));
    }
    
    // إضافة الخدمة كمنتج virtual
    $wc_order->add_product($wc_product, $quantity, [
        'subtotal' => $item_total,
        'total' => $item_total,
    ]);
    
    // الحصول على آخر item تم إضافته وحفظ staff_id في Meta
    $items = $wc_order->get_items();
    $last_item = end($items);
    if ($last_item && $staff_id) {
        $last_item->add_meta_data('_asmaa_staff_id', $staff_id);
        $last_item->save();
    }
} else {
    // للمنتجات العادية
    $wc_order->add_product($wc_product, $quantity, [
        'subtotal' => $item_total,
        'total' => $item_total,
    ]);
    $items = $wc_order->get_items();
    $last_item = end($items);
    if ($last_item && $staff_id) {
        $last_item->add_meta_data('_asmaa_staff_id', $staff_id);
        $last_item->save();
    }
}
```

**ملاحظة**: استخدام `_asmaa_staff_id` (مع underscore) يجعل الـ meta hidden في WooCommerce، مما يمنع ظهوره في الفواتير أو الإيميلات للعملاء.

#### 2.2 خدمة إدارة Virtual Products للخدمات

ملف جديد: `includes/Services/Product_Service.php`

```php
public function get_or_create_service_product(int $service_id, string $service_name, float $price): int
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
```

#### 2.3 قراءة Staff ID من Order Items

في `Orders_Controller::format_wc_order()`:

```php
foreach ($wc_order->get_items() as $item_id => $item) {
    // قراءة staff_id من Meta (hidden meta key)
    $staff_id = $item->get_meta('_asmaa_staff_id');
    
    // تحديد نوع العنصر
    $is_service = false;
    if ($item instanceof \WC_Order_Item_Product) {
        $product = $item->get_product();
        $is_service = $product && $product->is_virtual();
    }
    
    $items[] = (object) [
        'id' => $item_id,
        'item_type' => $is_service ? 'service' : 'product',
        'item_name' => $item->get_name(),
        'quantity' => (int) $item->get_quantity(),
        'unit_price' => (float) $item->get_subtotal() / max(1, $item->get_quantity()),
        'total' => (float) $item->get_total(),
        'staff_id' => $staff_id ? (int) $staff_id : null,
    ];
}
```

#### 2.4 تحسين حساب العمولات بناءً على Order Item Meta

في `POS_Controller::process_commissions()` و `Orders_Controller::create_staff_commissions_for_order()`:

```php
/**
 * حساب عمولات الطلب بناءً على Order Item Meta
 */
protected function calculate_order_commissions(int $wc_order_id): array
{
    $wc_order = wc_get_order($wc_order_id);
    if (!$wc_order) {
        return [];
    }
    
    global $wpdb;
    $commissions_table = $wpdb->prefix . 'asmaa_staff_commissions';
    $extended_table = $wpdb->prefix . 'asmaa_staff_extended_data';
    $commissions = [];
    
    // الحصول على إعدادات العمولات الافتراضية
    $settings_table = $wpdb->prefix . 'asmaa_commission_settings';
    $settings = $wpdb->get_row("SELECT * FROM {$settings_table} ORDER BY id DESC LIMIT 1");
    $default_service_rate = $settings ? (float) $settings->service_commission_rate : 0.0;
    $default_product_rate = $settings ? (float) ($settings->product_commission_rate ?? 0.0) : 0.0;
    
    foreach ($wc_order->get_items() as $item) {
        $staff_id = $item->get_meta('_asmaa_staff_id');
        
        if (!$staff_id) {
            continue; // تخطي العناصر بدون موظفة
        }
        
        $item_total = (float) $item->get_total();
        if ($item_total <= 0) {
            continue;
        }
        
        // تحديد نوع العنصر
        $is_service = false;
        if ($item instanceof \WC_Order_Item_Product) {
            $product = $item->get_product();
            $is_service = $product && $product->is_virtual();
        }
        
        // جلب نسبة عمولة الموظفة (من الجدول الممتد أو الافتراضي)
        $extended = $wpdb->get_row($wpdb->prepare(
            "SELECT commission_rate FROM {$extended_table} WHERE wp_user_id = %d",
            (int) $staff_id
        ));
        
        $rate = $extended && $extended->commission_rate !== null
            ? (float) $extended->commission_rate
            : ($is_service ? $default_service_rate : $default_product_rate);
        
        if ($rate <= 0) {
            continue;
        }
        
        $commission_amount = round($item_total * ($rate / 100), 3);
        
        if ($commission_amount <= 0) {
            continue;
        }
        
        // تسجيل العمولة في الجدول
        $wpdb->insert($commissions_table, [
            'wp_user_id' => (int) $staff_id,
            'order_id' => $wc_order_id,
            'order_item_id' => $item->get_id(),
            'booking_id' => null, // سيتم ربطه لاحقاً إذا كان مرتبطاً بحجز
            'base_amount' => $item_total,
            'commission_rate' => $rate,
            'commission_amount' => $commission_amount,
            'rating_bonus' => 0.0,
            'final_amount' => $commission_amount,
            'status' => 'pending',
            'notes' => sprintf(
                'Auto commission from WC order #%d for %s: %s',
                $wc_order_id,
                $is_service ? 'service' : 'product',
                $item->get_name()
            ),
        ]);
        
        $commissions[] = [
            'staff_id' => (int) $staff_id,
            'item_id' => $item->get_id(),
            'amount' => $commission_amount,
        ];
    }
    
    return $commissions;
}
```

## 3. التعامل مع المدفوعات المسبقة (Partial Payments / Deposits)

### الملفات المتأثرة:

- `includes/API/Controllers/POS_Controller.php` - التحقق من المدفوعات المسبقة
- `includes/API/Controllers/Payments_Controller.php` - ربط المدفوعات المسبقة بالطلبات
- `includes/Services/WooCommerce_Integration_Service.php` - معالجة المدفوعات المسبقة

### التغييرات:

#### 3.1 التحقق من المدفوعات المسبقة في POS

في `POS_Controller::process_order()`:

```php
// قبل إنشاء الطلب، التحقق من وجود مدفوعات مسبقة
$prepaid_amount = 0.0;
if ($customer_id) {
    $prepaid_orders = wc_get_orders([
        'customer_id' => $customer_id,
        'status' => ['processing', 'on-hold'],
        'limit' => 10,
    ]);
    
    foreach ($prepaid_orders as $prepaid_order) {
        $prepaid_amount += (float) $prepaid_order->get_total_paid();
    }
}

// عرض المبلغ المدفوع مسبقاً في الواجهة
// خصم المبلغ المدفوع مسبقاً من الإجمالي
$final_total = max(0, $total - $prepaid_amount);
```

#### 3.2 ربط المدفوعات المسبقة بالطلب النهائي

- عند إنشاء طلب جديد، ربطه بالطلبات المدفوعة مسبقاً عبر Order Meta
- تحديث حالة الطلبات المدفوعة مسبقاً إلى "completed" عند اكتمال الدفع

#### 3.3 API Endpoint للتحقق من المدفوعات المسبقة

إضافة endpoint جديد: `GET /pos/prepaid/:customer_id`

- يعيد قائمة بالطلبات المدفوعة مسبقاً والمبلغ المتبقي

## 4. دعم Offline-First للـ POS

### الملفات المتأثرة:

- `assets/src/views/POS/Index.vue` - إضافة IndexedDB للـ POS
- `assets/src/services/posOfflineService.js` - خدمة جديدة للتعامل مع البيانات Offline
- `includes/API/Controllers/POS_Controller.php` - endpoint لمزامنة الطلبات المحفوظة

### التغييرات:

#### 4.1 إنشاء خدمة Offline للـ POS مع Idempotency (باستخدام Dexie.js)

**ملاحظة معمارية**: استخدام Dexie.js يوفر واجهة تعامل تشبه SQL/Query، مما يسهل:

- Filtering للطلبات المعلقة
- البحث عن عميل في الـ Cache المحلي
- معالجة البيانات المعقدة أثناء Offline

ملف جديد: `assets/src/services/posOfflineService.js`

```javascript
import Dexie from 'dexie';

// إنشاء قاعدة بيانات Dexie
class POSOfflineDB extends Dexie {
    constructor() {
        super('asmaa_pos_offline');
        this.version(1).stores({
            pending_orders: 'client_side_id, created_at, sync_attempts',
            cart_sessions: 'session_id, customer_id, created_at',
        });
    }
}

const db = new POSOfflineDB();

// توليد UUID فريد - يتم توليده عند فتح السلة وليس عند الدفع فقط
// هذا يضمن ثبات المعرّف حتى لو تكررت محاولات الإرسال
export function generateClientId() {
    return 'pos_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// حفظ client_side_id في sessionStorage عند فتح السلة
export function initializeCartSession() {
    let sessionId = sessionStorage.getItem('pos_cart_session_id');
    if (!sessionId) {
        sessionId = generateClientId();
        sessionStorage.setItem('pos_cart_session_id', sessionId);
    }
    return sessionId;
}

// حفظ طلب في IndexedDB
export async function savePendingOrder(orderData, client_side_id = null) {
    // استخدام client_side_id من sessionStorage إذا لم يتم تمريره
    if (!client_side_id) {
        client_side_id = sessionStorage.getItem('pos_cart_session_id') || generateClientId();
    }
    
    const pendingOrder = {
        client_side_id,
        order_data: orderData,
        created_at: new Date().toISOString(),
        sync_attempts: 0,
        last_sync_attempt: null,
    };
    
    await db.pending_orders.put(pendingOrder);
    return client_side_id;
}

// قراءة جميع الطلبات المحفوظة (باستخدام Dexie Query)
export async function getPendingOrders() {
    return await db.pending_orders
        .orderBy('created_at')
        .toArray();
}

// البحث عن طلبات عميل معين (مثال على قوة Dexie)
export async function getPendingOrdersByCustomer(customerId) {
    return await db.pending_orders
        .where('order_data.customer_id')
        .equals(customerId)
        .toArray();
}

// حذف طلب بعد المزامنة الناجحة
export async function deletePendingOrder(client_side_id) {
    await db.pending_orders.delete(client_side_id);
}

// تحديث عدد محاولات المزامنة
export async function updateSyncAttempt(client_side_id, success) {
    if (success) {
        await db.pending_orders.delete(client_side_id);
    } else {
        await db.pending_orders.update(client_side_id, {
            sync_attempts: Dexie.defineClass.increment(1),
            last_sync_attempt: new Date().toISOString(),
        });
    }
}

// الحصول على عدد الطلبات المعلقة (للعرض في UI)
export async function getPendingOrdersCount() {
    return await db.pending_orders.count();
}
```

#### 4.2 تحديث POS Vue Component

- إضافة فحص للاتصال بالإنترنت باستخدام `navigator.onLine` و `online/offline` events
- حفظ الطلبات تلقائياً في IndexedDB مع `client_side_id` عند انقطاع الإنترنت
- عرض مؤشر حالة الاتصال في الواجهة
- زر يدوي للمزامنة عند الحاجة
- معالجة الأخطاء وعرض إشعارات واضحة
```javascript
// في POS/Index.vue
import { 
    savePendingOrder, 
    getPendingOrders, 
    deletePendingOrder,
    initializeCartSession,
    getPendingOrdersCount 
} from '@/services/posOfflineService';

// عند فتح السلة (mounted أو عند إضافة أول عنصر)
onMounted(() => {
    // توليد client_side_id مبكراً عند فتح السلة
    const sessionId = initializeCartSession();
    console.log('Cart session initialized:', sessionId);
    
    // عرض عدد الطلبات المعلقة إن وجدت
    updatePendingOrdersBadge();
});

// عند محاولة إنشاء طلب
async function processOrder(orderData) {
    // استخدام client_side_id من sessionStorage (تم توليده عند فتح السلة)
    const client_side_id = sessionStorage.getItem('pos_cart_session_id');
    
    // إضافة client_side_id للطلب
    orderData.client_side_id = client_side_id;
    
    if (!navigator.onLine) {
        // حفظ في IndexedDB
        await savePendingOrder(orderData, client_side_id);
        showNotification('تم حفظ الطلب محلياً. سيتم المزامنة تلقائياً عند عودة الإنترنت.');
        return { success: false, offline: true, client_side_id };
    }
    
    // محاولة الإرسال للخادم
    try {
        const response = await api.post('/pos/process', orderData);
        
        // حذف session بعد النجاح
        sessionStorage.removeItem('pos_cart_session_id');
        
        return { success: true, data: response.data };
    } catch (error) {
        // في حالة الفشل، حفظ محلياً
        await savePendingOrder(orderData, client_side_id);
        return { success: false, offline: true, client_side_id };
    }
}

// عند عودة الإنترنت
window.addEventListener('online', async () => {
    await syncPendingOrders();
});

async function syncPendingOrders() {
    const pending = await getPendingOrders();
    if (pending.length === 0) return;
    
    // إرسال جميع الطلبات المحفوظة
    const results = await api.post('/pos/sync-pending', {
        orders: pending.map(p => ({
            client_side_id: p.client_side_id,
            order_data: p.order_data,
        })),
    });
    
    // حذف الطلبات الناجحة
    for (const result of results.data) {
        if (result.success) {
            await deletePendingOrder(result.client_side_id);
        }
    }
}
```


#### 4.3 Batch Sync Endpoint مع Idempotency

إضافة endpoint جديد: `POST /pos/sync-pending`

في `POS_Controller.php`:

```php
public function sync_pending_orders(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $pending_orders_table = $wpdb->prefix . 'asmaa_pending_orders_sync'; // جدول لتتبع الطلبات المزامنة
    
    $orders = $request->get_param('orders');
    if (!is_array($orders) || empty($orders)) {
        return $this->error_response(__('No orders provided', 'asmaa-salon'), 400);
    }
    
    $results = [];
    
    foreach ($orders as $order_data) {
        $client_side_id = sanitize_text_field($order_data['client_side_id'] ?? '');
        
        if (empty($client_side_id)) {
            $results[] = [
                'client_side_id' => $client_side_id,
                'success' => false,
                'error' => 'Missing client_side_id',
            ];
            continue;
        }
        
        // Idempotency Check: التحقق من وجود الطلب مسبقاً
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT wc_order_id FROM {$pending_orders_table} WHERE client_side_id = %s",
            $client_side_id
        ));
        
        if ($existing) {
            // الطلب موجود مسبقاً - إرجاع النتيجة بدون معالجة
            $results[] = [
                'client_side_id' => $client_side_id,
                'success' => true,
                'wc_order_id' => (int) $existing,
                'message' => 'Order already synced',
            ];
            continue;
        }
        
        // معالجة الطلب
        try {
            $order_data_array = $order_data['order_data'] ?? [];
            $wc_order_id = $this->process_order_internal($order_data_array);
            
            // تسجيل الطلب في جدول المزامنة
            $wpdb->insert($pending_orders_table, [
                'client_side_id' => $client_side_id,
                'wc_order_id' => $wc_order_id,
                'synced_at' => current_time('mysql'),
            ]);
            
            $results[] = [
                'client_side_id' => $client_side_id,
                'success' => true,
                'wc_order_id' => $wc_order_id,
            ];
        } catch (\Exception $e) {
            $results[] = [
                'client_side_id' => $client_side_id,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    return $this->success_response([
        'synced' => count(array_filter($results, fn($r) => $r['success'])),
        'failed' => count(array_filter($results, fn($r) => !$r['success'])),
        'results' => $results,
    ]);
}
```

**Migration للجدول الجديد**:

### Migration للجداول الجديدة (Production Ready):

ملف جديد: `includes/Database/Migrations/Add_Sync_And_Extended_Tables.php`

```php
<?php

namespace AsmaaSalon\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Add_Sync_And_Extended_Tables
{
    /**
     * Run migration
     */
    public static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $charset_collate = $wpdb->get_charset_collate();

        // 1. جدول تتبع المزامنة (Idempotency)
        $table_sync = $wpdb->prefix . 'asmaa_pending_orders_sync';
        $sql_sync = "CREATE TABLE {$table_sync} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_side_id VARCHAR(100) NOT NULL,
            wc_order_id BIGINT UNSIGNED NOT NULL,
            synced_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_client_side_id (client_side_id),
            KEY idx_wc_order_id (wc_order_id),
            KEY idx_synced_at (synced_at)
        ) {$charset_collate};";
        
        dbDelta($sql_sync);

        // 2. تحديث جدول الطابور لإضافة رابط الحجز (إذا لم يكن موجوداً)
        $table_queue = $wpdb->prefix . 'asmaa_queue_tickets';
        
        // التحقق من وجود العمود قبل إضافته
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'booking_id'",
            DB_NAME,
            $table_queue
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_queue} ADD COLUMN booking_id BIGINT UNSIGNED NULL AFTER wc_customer_id");
            $wpdb->query("ALTER TABLE {$table_queue} ADD INDEX idx_booking_id (booking_id)");
        }
    }

    /**
     * Check if migration is needed
     */
    public static function needs_migration(): bool
    {
        global $wpdb;
        
        // التحقق من وجود جدول المزامنة
        $table_sync = $wpdb->prefix . 'asmaa_pending_orders_sync';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables 
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_sync
        ));
        
        if (!$exists) {
            return true;
        }
        
        // التحقق من وجود عمود booking_id في جدول الطابور
        $table_queue = $wpdb->prefix . 'asmaa_queue_tickets';
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'booking_id'",
            DB_NAME,
            $table_queue
        ));
        
        return empty($column_exists);
    }
}
```

**تسجيل Migration في Plugin.php**:

```php
protected function maybe_add_sync_and_extended_tables(): void
{
    $migrated = get_option('asmaa_salon_sync_tables_migrated', false);
    
    if (!$migrated && class_exists('\AsmaaSalon\Database\Migrations\Add_Sync_And_Extended_Tables')) {
        if (\AsmaaSalon\Database\Migrations\Add_Sync_And_Extended_Tables::needs_migration()) {
            \AsmaaSalon\Database\Migrations\Add_Sync_And_Extended_Tables::migrate();
            update_option('asmaa_salon_sync_tables_migrated', true, false);
        }
    }
}
```

## 5. تحسينات إضافية على التقارير

### الملفات المتأثرة:

- `includes/API/Controllers/Reports_Controller.php` - تقارير كفاءة المواعيد

### التغييرات:

#### 5.1 تقرير كفاءة المواعيد

إضافة endpoint جديد: `GET /reports/booking-efficiency`

- يقارن وقت الحجز المحدد مع وقت الوصول الفعلي (من `check_in_at` في queue_ticket)
- يحسب متوسط التأخير لكل موظفة
- يعرض إحصائيات عن المواعيد التي تم تحويلها من حجز إلى طابور

## 6. تحديثات قاعدة البيانات

### Migration جديدة:

ملف جديد: `includes/Database/Migrations/Add_Booking_Queue_Links.php`

- إضافة `booking_id` إلى `asmaa_queue_tickets` إذا لم يكن موجوداً
- إضافة فهارس للأداء

## 7. اختبارات

- اختبار تحويل الحجز إلى طابور
- اختبار تعدد الموظفات في طلب واحد
- اختبار المدفوعات المسبقة
- اختبار Offline-First (محاكاة انقطاع الإنترنت)
- اختبار المزامنة التلقائية

## ملاحظات تقنية ومعمارية

### 1. Metadata Key Convention

- استخدام `_asmaa_staff_id` (مع underscore في البداية) يجعل الـ meta **hidden** في WooCommerce
- هذا يمنع ظهور بيانات الموظفات في الفواتير أو الإيميلات المرسلة للعملاء
- البيانات الإدارية الداخلية تبقى مخفية عن العملاء

### 2. Virtual Products vs Fees

- **استخدام Virtual Products للخدمات** بدلاً من Fees يضمن:
        - توحيد معالجة جميع العناصر عبر `get_items()`
        - دعم تقارير WooCommerce الافتراضية للخدمات
        - سهولة التكامل مع أنظمة المحاسبة الخارجية (QuickBooks, Xero)
        - تجنب مشاكل معالجة Fees في التقارير
        - **Catalog Visibility = Hidden**: منع ظهور الخدمات كمنتجات قابلة للشراء في المتجر الإلكتروني العام
        - الحصول مجاناً على ميزات مثل: تقارير المبيعات حسب الصنف، دعم الضرائب المتقدمة

### 3. Idempotency في Batch Sync

- **إضافة `client_side_id` (UUID)** لكل طلب يتم إنشاؤه offline
- **توليد مبكر**: يتم توليد `client_side_id` عند فتح السلة وليس عند الدفع فقط
- حفظ `client_side_id` في `sessionStorage` لضمان ثبات المعرّف
- جدول `asmaa_pending_orders_sync` لتتبع الطلبات المزامنة
- التحقق من وجود `client_side_id` قبل المعالجة يمنع:
        - تكرار الخصم من المخزون
        - تكرار الفواتير
        - مشاكل في حالة "Lag" في الإنترنت وإرسال الطلب مرتين

### 4. IndexedDB Implementation

- استخدام مكتبة `idb` (IndexedDB wrapper) للتعامل مع IndexedDB
- تخزين `client_side_id`, `order_data`, `created_at`, `sync_attempts`
- معالجة تلقائية عند عودة الإنترنت عبر `online` event listener

### 5. Network Detection

- استخدام `navigator.onLine` للفحص الفوري
- الاستماع لـ `online/offline` events للمزامنة التلقائية
- عرض مؤشر حالة الاتصال في الواجهة

### 6. Commission Calculation Architecture

- حساب العمولات بناءً على **Order Item Meta** وليس رأس الطلب
- دعم تعدد الموظفات في طلب واحد بدقة 100%
- استخدام Virtual Products يسهل تحديد نوع العنصر (خدمة/منتج)
- جلب نسبة العمولة من الجدول الممتد للموظفة أو استخدام القيمة الافتراضية
# 🔄 دليل تفعيل مزامنة WooCommerce

## ✅ ما تم إضافته

### 1. المزامنة ثنائية الاتجاه
- ✅ **POS Orders** → WooCommerce Orders
- ✅ **Orders** → WooCommerce Orders  
- ✅ **Invoices** → WooCommerce Orders (مرتبطة)
- ✅ **Payments** → WooCommerce Order Payment Status

### 2. المؤشرات البصرية
- ✅ زر **WC** يظهر بجانب Order/Invoice/Payment المتزامن
- ✅ النقر على الزر يفتح WooCommerce Order في تبويب جديد

---

## 🚀 خطوات التفعيل

### الخطوة 1: تفعيل WooCommerce Integration

1. اذهب إلى **Settings → WooCommerce**
2. فعّل **"Enable WooCommerce Integration"**
3. تأكد من تفعيل:
   - ✅ **Sync Orders**
   - ✅ **Sync Direction**: **Bidirectional (Both Ways)**
   - ✅ **Auto Sync**: مفعل
   - ✅ **Sync on Create**: مفعل
   - ✅ **Sync on Update**: مفعل

### الخطوة 2: تشغيل Migration (إضافة الأعمدة)

Migration سيتم تشغيله تلقائياً عند تحميل Plugin. لكن إذا لم يحدث:

```php
// في WordPress Admin أو عبر WP-CLI
// سيتم تشغيله تلقائياً عند زيارة أي صفحة في Plugin
```

الأعمدة المضافة:
- `asmaa_invoices.wc_order_id`
- `asmaa_payments.wc_payment_id` (موجود مسبقاً)
- `asmaa_orders.wc_order_id` (موجود مسبقاً)

### الخطوة 3: اختبار المزامنة

#### اختبار من Asmaa Salon إلى WooCommerce:

1. **إنشاء Order جديد** من صفحة Orders
2. **إنشاء Invoice جديد** من صفحة Invoices
3. **إنشاء Payment جديد** من صفحة Payments
4. **بيع من POS** - سيتم إنشاء Order + Invoice + Payment تلقائياً

**النتيجة المتوقعة:**
- ✅ يظهر زر **WC** بجانب Order/Invoice/Payment
- ✅ النقر على الزر يفتح WooCommerce Order

#### اختبار من WooCommerce إلى Asmaa Salon:

1. اذهب إلى **WooCommerce → Orders**
2. أنشئ Order جديد أو عدّل Order موجود
3. **النتيجة المتوقعة:**
   - ✅ يظهر Order في صفحة Orders في Asmaa Salon
   - ✅ يظهر Payment إذا كان Order مدفوع

---

## 🔍 التحقق من المزامنة

### 1. في الواجهة

- **صفحة Orders**: زر **WC** بجانب Order Number
- **صفحة Invoices**: زر **WC** بجانب Invoice Number  
- **صفحة Payments**: زر **WC** بجانب Payment Number

### 2. في قاعدة البيانات

```sql
-- التحقق من Orders المتزامنة
SELECT id, order_number, wc_order_id, wc_synced_at 
FROM wp_asmaa_orders 
WHERE wc_order_id IS NOT NULL;

-- التحقق من Invoices المتزامنة
SELECT id, invoice_number, wc_order_id 
FROM wp_asmaa_invoices 
WHERE wc_order_id IS NOT NULL;

-- التحقق من Payments المتزامنة
SELECT id, payment_number, wc_payment_id 
FROM wp_asmaa_payments 
WHERE wc_payment_id IS NOT NULL;

-- عرض سجل المزامنة
SELECT * FROM wp_asmaa_wc_sync_log 
ORDER BY synced_at DESC 
LIMIT 20;
```

---

## ⚠️ استكشاف الأخطاء

### المشكلة: لا يظهر زر WC

**الأسباب المحتملة:**
1. ❌ WooCommerce Integration غير مفعل
2. ❌ Sync Direction = `wc_to_asmaa` فقط (يجب أن يكون `bidirectional`)
3. ❌ Migration لم يتم تشغيله (الأعمدة غير موجودة)
4. ❌ المزامنة فشلت (تحقق من `asmaa_wc_sync_log`)

**الحل:**
```sql
-- تحقق من وجود الأعمدة
SHOW COLUMNS FROM wp_asmaa_invoices LIKE 'wc_order_id';
SHOW COLUMNS FROM wp_asmaa_payments LIKE 'wc_payment_id';
SHOW COLUMNS FROM wp_asmaa_orders LIKE 'wc_order_id';

-- إذا لم تكن موجودة، شغّل Migration يدوياً:
-- اذهب إلى Settings → WooCommerce (سيتم تشغيل Migration تلقائياً)
```

### المشكلة: المزامنة لا تعمل

**التحقق:**
1. ✅ WooCommerce مفعل ومثبت
2. ✅ Settings → WooCommerce → Enable Integration = ✅
3. ✅ Settings → WooCommerce → Sync Orders = ✅
4. ✅ Settings → WooCommerce → Sync Direction = `bidirectional`

**فحص السجلات:**
```sql
SELECT * FROM wp_asmaa_wc_sync_log 
WHERE status = 'failed' 
ORDER BY synced_at DESC;
```

---

## 📊 سجل المزامنة

جميع عمليات المزامنة تُسجل في جدول `asmaa_wc_sync_log`:

```sql
SELECT 
    entity_type,
    entity_id,
    wc_entity_id,
    sync_direction,
    status,
    error_message,
    synced_at
FROM wp_asmaa_wc_sync_log
ORDER BY synced_at DESC
LIMIT 50;
```

**الحقول:**
- `entity_type`: `order`, `invoice`, `payment`, `product`, `customer`
- `sync_direction`: `to_wc` (من Asmaa إلى WooCommerce) أو `from_wc` (من WooCommerce إلى Asmaa)
- `status`: `success` أو `failed`
- `error_message`: رسالة الخطأ (إن وجدت)

---

## 🎯 ملخص

### ✅ ما يعمل الآن:

1. **POS → WooCommerce**: عند البيع من POS، يتم إنشاء Order في WooCommerce
2. **Orders → WooCommerce**: عند إنشاء/تحديث Order، يتم المزامنة
3. **Invoices → WooCommerce**: عند إنشاء/تحديث Invoice، يتم ربطه بـ WooCommerce Order
4. **Payments → WooCommerce**: عند إنشاء/تحديث Payment، يتم تحديث WooCommerce Order Payment Status
5. **WooCommerce → Asmaa Salon**: عند إنشاء/تحديث Order في WooCommerce، يتم المزامنة تلقائياً

### 🔄 المزامنة التلقائية:

- ✅ عند **إنشاء** Order/Invoice/Payment → مزامنة فورية
- ✅ عند **تحديث** Order/Invoice/Payment → مزامنة فورية
- ✅ عند **تغيير** Order Status في WooCommerce → مزامنة فورية
- ✅ عند **تغيير** Payment Status في WooCommerce → مزامنة فورية

---

## 💡 نصائح

1. **افحص Settings أولاً**: تأكد من تفعيل جميع الإعدادات المطلوبة
2. **راقب السجلات**: استخدم `asmaa_wc_sync_log` لتتبع المشاكل
3. **اختبر تدريجياً**: ابدأ بإنشاء Order واحد وافحص النتيجة
4. **استخدم Bidirectional**: للحصول على مزامنة كاملة في كلا الاتجاهين

---

**تم التطبيق بنجاح! 🎉**


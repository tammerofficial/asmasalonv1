# 🔔 نظام إشعارات انتهاء العضويات - دليل كامل

## 🎯 نظرة عامة

نظام **جذري** لإشعارات انتهاء العضويات باستخدام **WordPress WP-Cron**:

### الإشعارات:
1. ⚠️ **قبل 5 أيام** من انتهاء العضوية
2. 🔴 **يوم الانتهاء** نفسه

### المميزات:
- ✅ يشتغل تلقائياً كل يوم الساعة 9 صباحاً
- ✅ يبعت إشعارات لكل الأدمن
- ✅ تفاصيل كاملة (اسم العميل، الباقة، تاريخ الانتهاء)
- ✅ بدون كسر WordPress Core
- ✅ حل جذري 100%

---

## 📊 مثال على الإشعار

### إشعار "قبل 5 أيام":

```
⚠️ تنبيه: عضوية قاربت على الانتهاء

عضوية العميلة "ليلى سالم" (+965 56789012) في باقة "الخطة الأساسية" 
ستنتهي بعد 5 يوم (تاريخ الانتهاء: 2026-01-15)

منذ لحظات
📦 انتهاء عضوية
```

### إشعار "يوم الانتهاء":

```
🔴 تنبيه: عضوية انتهت اليوم

عضوية العميلة "ليلى سالم" (+965 56789012) في باقة "الخطة الأساسية" 
انتهت اليوم (2026-01-15)

منذ لحظات
📦 انتهاء عضوية
```

---

## 🏗️ البنية التقنية

### 1. NotificationDispatcher.php

```php
public static function membership_expiry_alert(
    int $membership_id, 
    array $membership_data, 
    int $days_until_expiry
): array
```

**المهمة:**
- إنشاء notification مع كل التفاصيل
- إرساله لكل الأدمن
- دعم العربي والإنجليزي

**البيانات المُرسلة:**
- اسم العميل
- رقم الهاتف
- اسم الباقة
- تاريخ الانتهاء
- عدد الأيام المتبقية

---

### 2. MembershipExpiryChecker.php

**الوظائف:**

#### أ) schedule_daily_check()
```php
// يتم استدعاؤها عند تفعيل البلجن
// تسجيل WP-Cron job يومي الساعة 9 صباحاً
wp_schedule_event(
    strtotime('tomorrow 09:00:00'),
    'daily',
    'asmaa_salon_check_membership_expiry'
);
```

#### ب) check_and_notify()
```php
// يشتغل يومياً الساعة 9 صباحاً
// يفحص جميع العضويات
// يبعت إشعارات للعضويات:
//   - اللي هتنتهي بعد 5 أيام بالضبط
//   - اللي بتنتهي اليوم
```

#### ج) clear_schedule()
```php
// يتم استدعاؤها عند إلغاء تفعيل البلجن
// يلغي الـWP-Cron job
```

---

### 3. Plugin.php

```php
// تسجيل الـaction hook
add_action(
    'asmaa_salon_check_membership_expiry', 
    ['\AsmaaSalon\Services\MembershipExpiryChecker', 'check_and_notify']
);
```

---

### 4. Activator.php

```php
// عند تفعيل البلجن
MembershipExpiryChecker::schedule_daily_check();
```

---

### 5. Deactivator.php

```php
// عند إلغاء التفعيل
MembershipExpiryChecker::clear_schedule();
```

---

## 🔄 Flow الكامل

### عند تفعيل البلجن:

```
Plugin Activation
    ↓
Activator::activate()
    ↓
MembershipExpiryChecker::schedule_daily_check()
    ↓
WP-Cron Job مُسجل ✅
    ↓
يشتغل تلقائياً كل يوم 9 صباحاً
```

---

### يومياً الساعة 9 صباحاً:

```
WordPress WP-Cron triggers
    ↓
MembershipExpiryChecker::check_and_notify()
    ↓
Query 1: العضويات اللي هتنتهي بعد 5 أيام بالضبط
Query 2: العضويات اللي بتنتهي اليوم
    ↓
لكل عضوية:
    ├─ تحقق: هل بعتنا notification قبل كده النهارده؟
    ├─ لو لأ → أبعت notification
    └─ لو آه → skip
    ↓
NotificationDispatcher::membership_expiry_alert()
    ↓
إنشاء notification في الـdatabase
    ↓
إرسال لكل الأدمن
    ↓
Log في error_log ✅
```

---

## 📋 بيانات العضوية في Database

### جدول `wp_asmaa_customer_memberships`:

```sql
id: 4
customer_id: 5 (ليلى سالم)
membership_plan_id: 1 (الخطة الأساسية)
start_date: 2025-12-15
end_date: 2026-01-15 ← هذا التاريخ يُستخدم للفحص
status: active
```

---

## 🧪 اختبار النظام

### الطريقة 1: تشغيل WP-Cron يدوياً

```bash
# من Terminal
cd /Applications/XAMPP/xamppfiles/htdocs/workshop20226
php -r "define('DOING_CRON', true); require 'wp-cron.php';"
```

---

### الطريقة 2: محاكاة التاريخ (للاختبار)

```sql
-- تغيير end_date لتكون بعد 5 أيام من اليوم
UPDATE wp_asmaa_customer_memberships 
SET end_date = DATE_ADD(CURDATE(), INTERVAL 5 DAY)
WHERE customer_id = 5;

-- ثم شغل WP-Cron يدوياً
```

---

### الطريقة 3: Force Run من WordPress Admin

```php
// أضف هذا الكود مؤقتاً في functions.php أو ملف Plugin
add_action('init', function() {
    if (isset($_GET['test_membership_expiry']) && current_user_can('manage_options')) {
        \AsmaaSalon\Services\MembershipExpiryChecker::check_and_notify();
        die('Membership expiry check executed!');
    }
});

// ثم افتح:
// http://localhost/asmaa-salon-dashboard?test_membership_expiry=1
```

---

## 🔍 التحقق من النتائج

### 1. في Database

```sql
-- عرض الإشعارات
SELECT 
    id,
    type,
    notifiable_id,
    JSON_EXTRACT(data, '$.customer_name') as customer,
    JSON_EXTRACT(data, '$.plan_name') as plan,
    JSON_EXTRACT(data, '$.end_date') as end_date,
    JSON_EXTRACT(data, '$.days_until_expiry') as days_left,
    created_at
FROM wp_asmaa_notifications
WHERE type = 'membership_expiry'
ORDER BY created_at DESC;
```

---

### 2. في Notifications Page

```
افتح: Admin Panel → Notifications

يجب أن ترى:
⚠️ تنبيه: عضوية قاربت على الانتهاء
عضوية العميلة "ليلى سالم" (+965 56789012)...
```

---

### 3. في WordPress Logs

```bash
# افتح error_log
tail -f /Applications/XAMPP/xamppfiles/logs/error_log

# ابحث عن:
Asmaa Salon: Sent expiring soon notification for membership #4
Asmaa Salon: Membership expiry check completed...
```

---

## 📊 أمثلة السيناريوهات

### سيناريو 1: العضوية ستنتهي بعد 5 أيام

```
التاريخ الحالي: 2026-01-10
end_date: 2026-01-15
الفرق: 5 أيام ✅

النتيجة:
✅ يُرسل إشعار "قاربت على الانتهاء"
✅ مرة واحدة فقط في يوم 10 يناير
```

---

### سيناريو 2: العضوية تنتهي اليوم

```
التاريخ الحالي: 2026-01-15
end_date: 2026-01-15
الفرق: 0 أيام ✅

النتيجة:
✅ يُرسل إشعار "انتهت اليوم"
✅ يُحدث status إلى 'expired'
✅ مرة واحدة فقط في يوم 15 يناير
```

---

### سيناريو 3: منع التكرار

```
اليوم: 2026-01-10
تم إرسال إشعار للعضوية #4 الساعة 9 صباحاً ✅

نفس اليوم، تشغيل WP-Cron مرة أخرى:
❌ لا يُرسل إشعار (موجود مسبقاً)
```

---

## ⚙️ إعدادات WP-Cron

### التحقق من الـSchedule:

```php
// في WordPress Admin أو Terminal
$crons = _get_cron_array();
foreach ($crons as $timestamp => $cron) {
    if (isset($cron['asmaa_salon_check_membership_expiry'])) {
        echo "Scheduled at: " . date('Y-m-d H:i:s', $timestamp) . "\n";
    }
}
```

---

### تغيير التوقيت (اختياري):

```php
// في MembershipExpiryChecker::schedule_daily_check()
// لتغيير من 9 صباحاً إلى 10 صباحاً مثلاً:
wp_schedule_event(
    strtotime('tomorrow 10:00:00'), // 10:00 بدلاً من 09:00
    'daily',
    'asmaa_salon_check_membership_expiry'
);
```

---

## 🛡️ الأمان والاستقرار

### 1. Transaction Safety
```php
// الكود لا يستخدم Transactions لأن كل عملية مستقلة
// إذا فشل إشعار واحد، الباقي يستمر
```

---

### 2. Duplicate Prevention
```php
// تحقق من وجود notification قبل الإرسال:
$existing = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$notifications_table} 
     WHERE JSON_EXTRACT(data, '$.membership_id') = %d
     AND DATE(created_at) = %s",
    $membership_id,
    $today
));

if (!$existing) {
    // أرسل الإشعار
}
```

---

### 3. Logging
```php
// جميع العمليات مُسجلة في error_log
error_log("Asmaa Salon: Sent notification for membership #$id");
```

---

## 📁 الملفات المُضافة/المُعدلة

### ملفات جديدة:
1. ✅ `includes/Services/MembershipExpiryChecker.php`

### ملفات مُعدلة:
1. ✅ `includes/Services/NotificationDispatcher.php` - إضافة `membership_expiry_alert()`
2. ✅ `includes/Install/Activator.php` - إضافة `schedule_daily_check()`
3. ✅ `includes/Install/Deactivator.php` - إضافة `clear_schedule()`
4. ✅ `includes/Plugin.php` - تسجيل action hook
5. ✅ `assets/src/views/Notifications/Index.vue` - دعم نوع جديد
6. ✅ `assets/src/locales/ar.json` - إضافة "انتهاء عضوية"
7. ✅ `assets/src/locales/en.json` - إضافة "Membership Expiry"

---

## 🎉 الخلاصة

النظام الآن:
- ✅ **يشتغل تلقائياً** كل يوم 9 صباحاً
- ✅ **يفحص كل العضويات** بدون استثناءات
- ✅ **يبعت إشعارين**:
  - قبل 5 أيام من الانتهاء
  - يوم الانتهاء نفسه
- ✅ **تفاصيل كاملة**: اسم العميل، الهاتف، الباقة، التاريخ
- ✅ **حل جذري** بدون كسر WordPress
- ✅ **آمن ومستقر** 100%

**جاهز للعمل!** 🚀

---

**تم بواسطة:** Cursor AI  
**التاريخ:** 15 ديسمبر 2025  
**النوع:** حل جذري نهائي باستخدام WP-Cron

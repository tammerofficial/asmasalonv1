# 🔍 Asmaa Salon System Health Check Tool

أداة CLI شاملة لفحص جميع مكونات نظام Asmaa Salon والتأكد من أنها تعمل بشكل صحيح.

## 📋 الاستخدام

```bash
# من مجلد WordPress الرئيسي
php wp-content/plugins/asmasalonv1/check-system.php

# أو من مجلد الـ plugin
cd wp-content/plugins/asmasalonv1
php check-system.php
```

## ✅ ما يتم فحصه

### 1. WordPress Environment
- تحميل WordPress
- إصدار PHP (8.0+)
- إصدار WordPress (6.0+)
- تفعيل REST API

### 2. Plugin Status
- وجود ملف الـ plugin
- حالة تفعيل الـ plugin
- إصدار الـ plugin
- وجود كلاس الـ plugin

### 3. Database Tables
فحص جميع جداول قاعدة البيانات:
- `asmaa_customers`
- `asmaa_services`
- `asmaa_staff`
- `asmaa_bookings`
- `asmaa_orders`
- `asmaa_order_items`
- `asmaa_invoices`
- `asmaa_payments`
- `asmaa_products`
- `asmaa_inventory_movements`
- `asmaa_loyalty_transactions`
- `asmaa_membership_plans`
- `asmaa_notifications`
- `asmaa_worker_calls`
- `asmaa_staff_ratings`
- وغيرها...

### 4. REST API Controllers
فحص جميع الـ Controllers (21 controller):
- Customers_Controller
- Services_Controller
- Staff_Controller
- Bookings_Controller
- Booking_Settings_Controller
- Orders_Controller
- Queue_Controller
- Invoices_Controller
- Payments_Controller
- Products_Controller
- Notifications_Controller
- Reports_Controller
- Loyalty_Controller
- Memberships_Controller
- Commissions_Controller
- Programs_Settings_Controller
- Inventory_Controller
- Worker_Calls_Controller
- Staff_Ratings_Controller
- POS_Controller
- Users_Controller

### 5. REST API Endpoints
فحص جميع الـ endpoints:
- `/ping`
- `/customers`
- `/services`
- `/staff`
- `/bookings`
- `/orders`
- `/queue`
- `/invoices`
- `/payments`
- `/products`
- `/notifications`
- `/reports`
- `/loyalty`
- `/memberships`
- `/commissions`
- `/pos`
- `/users`

### 6. Vue Router Routes
فحص جميع الـ routes في Vue Router:
- Dashboard (`/`)
- Bookings (`/bookings`, `/bookings/categories`, `/bookings/settings`, `/bookings/appearance`)
- Queue (`/queue`)
- Worker Calls (`/worker-calls`)
- POS (`/pos`)
- Orders (`/orders`)
- Invoices (`/invoices`)
- Payments (`/payments`)
- Services (`/services`)
- Products (`/products`)
- Customers (`/customers`)
- Staff (`/staff`)
- Loyalty (`/loyalty`)
- Memberships (`/memberships`)
- Commissions (`/commissions`)
- Notifications (`/notifications`)
- Reports (`/reports`)
- Core (`/core`)
- Programs Settings (`/programs/settings`)
- Display (`/display/queue`, `/display/staff-room`)
- Rating (`/rating`)
- Users (`/users`)
- Roles (`/roles`)

### 7. Vue Views
فحص جميع ملفات الـ Views:
- Dashboard.vue
- Bookings/Index.vue
- Queue/Index.vue
- POS/Index.vue
- Orders/Index.vue
- Invoices/Index.vue
- Payments/Index.vue
- Services/Index.vue
- Products/Index.vue
- Customers/Index.vue
- Staff/Index.vue
- Loyalty/Index.vue
- Memberships/Index.vue
- Commissions/Index.vue
- Notifications/Index.vue
- Reports/Index.vue
- Core/Index.vue
- Programs/Settings.vue
- Users/Index.vue
- Roles/Index.vue
- Display/Queue.vue
- Display/StaffRoom.vue
- Rating.vue

### 8. Pinia Stores
فحص جميع الـ stores:
- `bookings.js`
- `notifications.js`
- `ui.js`

### 9. Capabilities & Permissions
فحص:
- وجود كلاس Capabilities
- تسجيل جميع الـ capabilities
- وجود جميع الأدوار:
  - `asmaa_super_admin`
  - `asmaa_admin`
  - `asmaa_manager`
  - `asmaa_accountant`
  - `asmaa_receptionist`
  - `asmaa_cashier`
  - `asmaa_staff`

### 10. Build Assets
فحص:
- وجود مجلد البناء
- وجود `main.js`
- وجود ملفات CSS
- وجود JS chunks

## 📊 مثال على النتائج

```
================================================================================
                        ASMAA SALON SYSTEM HEALTH CHECK                         
                                 Version: 0.2.0                                 
                           Date: 2025-12-24 10:07:09                            
================================================================================

✓ Passed: 141
✗ Failed: 4
⚠ Warnings: 0
Total Checks: 145
Success Rate: 97.24%
```

## 🔧 إصلاح المشاكل

إذا ظهرت فحوصات فاشلة:

1. **جداول قاعدة البيانات المفقودة**: قم بتشغيل migrations أو activator
2. **Controllers غير موجودة**: تأكد من وجود الملفات في `includes/API/Controllers/`
3. **Views غير موجودة**: تأكد من وجود الملفات في `assets/src/views/`
4. **Assets غير مبنية**: قم بتشغيل `npm run build` في مجلد `assets/`

## 📝 ملاحظات

- الأداة تعمل من سطر الأوامر (CLI) فقط
- تحتاج إلى WordPress محمل بشكل صحيح
- بعض الفحوصات قد تفشل إذا لم يتم تفعيل الـ plugin بشكل صحيح

## 🚀 التطوير المستقبلي

يمكن إضافة:
- فحص الاتصال الفعلي بالـ API endpoints
- فحص البيانات في قاعدة البيانات
- فحص الأمان والصلاحيات
- تقرير HTML بدلاً من النص فقط


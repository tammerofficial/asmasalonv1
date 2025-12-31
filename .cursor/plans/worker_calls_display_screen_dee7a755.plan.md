---
name: Worker Calls Display Screen
overview: إنشاء صفحة Display جديدة لنداء الموظفات مناسبة لشاشة تلفزيون تعرض اسم الموظفة، رقم الكرسي، اسم العميلة، والخدمات المطلوبة في تخطيط Grid
todos:
  - id: add_chair_number_field
    content: إضافة حقل chair_number إلى جدول asmaa_staff_extended_data في migration
    status: pending
  - id: update_worker_calls_api
    content: تحديث Worker_Calls_Controller لإرجاع chair_number في API response
    status: pending
    dependencies:
      - add_chair_number_field
  - id: update_staff_controller
    content: تحديث Staff_Controller لدعم chair_number في create/update operations
    status: pending
    dependencies:
      - add_chair_number_field
  - id: create_display_component
    content: إنشاء WorkerCalls.vue component مع Grid Layout لعرض النداءات
    status: pending
    dependencies:
      - update_worker_calls_api
  - id: add_route
    content: إضافة route جديد /display/worker-calls في router
    status: pending
    dependencies:
      - create_display_component
  - id: build_assets
    content: Build frontend assets بعد إضافة المكون الجديد
    status: pending
    dependencies:
      - add_route
---

# خطة نظام عرض نداء الموظفات على شاشة التلفزيون

## الهدف

إنشاء صفحة Display جديدة لعرض نداءات الموظفات على شاشة تلفزيون كبيرة، تعرض:

- اسم الموظفة
- رقم الكرسي
- اسم العميلة
- الخدمات المطلوبة

## التغييرات المطلوبة

### 1. إضافة حقل رقم الكرسي إلى قاعدة البيانات

**ملف:** `includes/Database/Migrations/Create_Extended_Data_Tables.php`

إضافة حقل `chair_number` إلى جدول `asmaa_staff_extended_data`:

- نوع البيانات: `INT UNSIGNED NULL`
- إضافة index على الحقل
- تحديث migration method لإضافة الحقل إذا لم يكن موجوداً

### 2. تحديث API Controller لإرجاع رقم الكرسي

**ملف:** `includes/API/Controllers/Worker_Calls_Controller.php`

في method `get_items()`:

- إضافة `ext.chair_number AS staff_chair_number` إلى SELECT query
- التأكد من JOIN مع `asmaa_staff_extended_data` موجود

### 3. تحديث Staff Controller لدعم رقم الكرسي

**ملف:** `includes/API/Controllers/Staff_Controller.php`

في methods `create_item()` و `update_item()`:

- إضافة دعم لحقل `chair_number` في extended_data
- السماح بتحديث رقم الكرسي عند إنشاء/تحديث موظف

### 4. إنشاء صفحة Display جديدة

**ملف:** `assets/src/views/Display/WorkerCalls.vue`

إنشاء صفحة Display جديدة مشابهة لـ `Queue.vue` و `StaffRoom.vue`:

**المميزات:**

- Header مع العنوان والتاريخ والوقت
- Grid Layout لعرض عدة نداءات في نفس الوقت
- Card لكل نداء يعرض:
  - اسم الموظفة (كبير وواضح)
  - رقم الكرسي (بارز)
  - اسم العميلة
  - الخدمات المطلوبة (قائمة)
- تصميم مناسب لشاشة تلفزيون (خطوط كبيرة، ألوان واضحة)
- Auto-refresh كل 3-5 ثواني
- Animations عند ظهور نداءات جديدة
- Empty state عندما لا توجد نداءات

**التصميم:**

- استخدام نفس نمط التصميم من `Queue.vue` (Dark theme مع gradients)
- Grid responsive: 2-4 columns حسب حجم الشاشة
- Cards كبيرة وواضحة مع shadows و borders
- ألوان مختلفة حسب حالة النداء (pending, staff_called, customer_called)

### 5. إضافة Route جديد

**ملف:** `assets/src/router/index.js`

إضافة route:

```javascript
{
  path: '/display/worker-calls',
  name: 'DisplayWorkerCalls',
  component: () => import('../views/Display/WorkerCalls.vue'),
}
```

### 6. تحديث Worker Calls API Query

**ملف:** `includes/API/Controllers/Worker_Calls_Controller.php`

في `get_items()`:

- تصفية النداءات النشطة فقط (status: pending, staff_called, customer_called)
- ترتيب حسب `created_at DESC` أو `called_at DESC`
- إرجاع جميع الخدمات المرتبطة بالـ ticket (إذا كان هناك multiple services)

### 7. معالجة الخدمات المتعددة

إذا كان ticket يحتوي على عدة خدمات:

- في `Worker_Calls_Controller`: JOIN مع جدول services أو استخدام JSON field
- في Vue component: عرض قائمة بالخدمات بدلاً من خدمة واحدة

## البنية التقنية

### Data Flow:

```
Worker Calls API → WorkerCalls.vue → Display Cards
```

### API Response Structure:

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 123,
        "staff_name": "نورة",
        "staff_chair_number": 5,
        "customer_name": "سارة محمد",
        "service_name": "صبغة شعر",
        "status": "staff_called",
        "ticket_number": "T-001",
        "created_at": "2025-01-15 10:30:00"
      }
    ]
  }
}
```

### Component Structure:

```vue
<template>
  <div class="worker-calls-display">
    <!-- Header -->
    <!-- Grid of Call Cards -->
    <!-- Empty State -->
  </div>
</template>
```

## الملفات التي سيتم تعديلها

1. `includes/Database/Migrations/Create_Extended_Data_Tables.php` - إضافة chair_number
2. `includes/API/Controllers/Worker_Calls_Controller.php` - إضافة chair_number إلى response
3. `includes/API/Controllers/Staff_Controller.php` - دعم chair_number في create/update
4. `assets/src/views/Display/WorkerCalls.vue` - **ملف جديد**
5. `assets/src/router/index.js` - إضافة route

## خطوات التنفيذ

1. إضافة migration لحقل chair_number
2. تحديث Controllers لدعم chair_number
3. إنشاء Vue component للعرض
4. إضافة Route
5. Build assets
6. Test على شاشة كبيرة

## ملاحظات التصميم

- استخدام Design Tokens من `design-tokens.css`
- استخدام CoreUI Icons (cil-*)
- Responsive design للشاشات المختلفة
- Animations سلسة عند التحديث
- ألوان واضحة وبارزة للقراءة من مسافة بعيدة
---
name: Comprehensive Plan for WooCommerce Source of Truth and Cleanup
overview: هذه الخطة الشاملة تهدف إلى تحويل النظام بالكامل ليعمل على جداول WooCommerce/WordPress مباشرة، وإزالة كافة واجهات المزامنة، وفي النهاية حذف الجداول القديمة التي تم استبدالها لضمان نظافة قاعدة البيانات.
todos:
  - id: cleanup-frontend-sync-ui
    content: إزالة أزرار ومنطق المزامنة من واجهة الطلبات والإعدادات.
    status: pending
  - id: update-controllers-source-of-truth
    content: تحديث كافة الـ Controllers لاستخدام الجداول الأصلية حصراً.
    status: pending
  - id: pos-direct-wc-orders
    content: تحديث POS_Controller لإنشاء الطلبات في WooCommerce مباشرة.
    status: pending
  - id: drop-legacy-tables-migration
    content: إنشاء Migration لحذف الجداول القديمة (asmaa_orders, customers, products, staff, sync_log).
    status: pending
  - id: disable-sync-logic-backend
    content: تعطيل منطق المزامنة في WooCommerce_Integration_Service.
    status: pending
---

# الخطة الشاملة للتحول لمصدر الحقيقة الموحد وتنظيف النظام

بناءً على التوجيهات الأخيرة، سنقوم بتنفيذ عملية تحول جذري تتضمن الانتقال الكامل للبيانات الأصلية، إزالة أزرار المزامنة، وحذف الجداول القديمة نهائياً.

## 1. إزالة واجهات المزامنة (Frontend Cleanup)

بما أن النظام سيعمل مباشرة على الجداول الأصلية، يجب إزالة أي إشارة لعملية "المزامنة" لمنع الارتباك.

- **الطلبات**: حذف زر "مزامنة" ومنطق المزامنة من [`assets/src/views/Orders/Index.vue`](assets/src/views/Orders/Index.vue).
- **الإعدادات**: تبسيط [`assets/src/views/Settings/WooCommerce.vue`](assets/src/views/Settings/WooCommerce.vue) لإلغاء خيارات المزامنة اليدوية والتلقائية القديمة.

## 2. تحديث المكونات البرمجية (Backend Logic Update)

تعديل كافة الـ Controllers لتعمل مع الجداول الأصلية + جداول البيانات الإضافية (`extended_data`).

- **التقارير**: تحديث [`includes/API/Controllers/Reports_Controller.php`](includes/API/Controllers/Reports_Controller.php) ليعتمد كلياً على `wc_get_orders`.
- **نقاط البيع**: تعديل [`includes/API/Controllers/POS_Controller.php`](includes/API/Controllers/POS_Controller.php) لإنشاء طلبات WooCommerce مباشرة.
- **الموظفون والعملاء**: ضمان ربط كافة العمليات (عمولات، نقاط ولاء) بالمعرفات الأصلية (`wp_user_id` / `wc_customer_id`).

## 3. تنظيف قاعدة البيانات (Database Cleanup)

هذه الخطوة هي الأخطر وتتطلب التأكد من أن كافة المراجع في الكود قد تم تحديثها.

- **الإجراء**: إنشاء migration نهائي لحذف الجداول التالية:
    - `asmaa_orders`
    - `asmaa_order_items`
    - `asmaa_customers`
    - `asmaa_products`
    - `asmaa_staff`
    - `asmaa_wc_sync_log` (لم يعد هناك حاجة لسجلات المزامنة)

## 4. مراجعة خدمة التكامل (WooCommerce_Integration_Service)

- تعطيل كافة وظائف المزامنة (Sync) داخل [`includes/Services/WooCommerce_Integration_Service.php`](includes/Services/WooCommerce_Integration_Service.php) وتحويلها إلى خدمة مساعدة فقط لجلب البيانات الإضافية أو معالجة أحداث WooCommerce.

## المخطط المعماري النهائي:

```mermaid
graph TD
    DB[(WordPress / WC DB)] -->|Direct SQL / API| API[Asmaa Salon API]
    API -->|No Middle Layer| UI[Vue.js UI]
    
    API <-->|Primary Key Link| EXT[Asmaa Extended Data]
    EXT --> C[Customer Extra Data]
    EXT --> S[Staff Extra Data]
    EXT --> P[Product Extra Data]
    
    subgraph Deleted [Deleted Tables]
        O[asmaa_orders]
        C1[asmaa_customers]
        P1[asmaa_products]
        ST[asmaa_staff]
    end
    
    Deleted --- x((X))
```

## التزامات الخطة:

- **تحذير**: حذف الجداول سيؤدي لفقدان البيانات الموجودة فيها فقط وغير الموجودة في WooCommerce. يجب التأكد من اكتمال المزامنة النهائية قبل الحذف (أو اعتبار أن WooCommerce هو المرجع الحالي).
- توحيد كافة الاستعلامات لتستخدم `LEFT JOIN` مع جداول `extended_data` لضمان عدم فقدان مميزات الصالون الخاصة.
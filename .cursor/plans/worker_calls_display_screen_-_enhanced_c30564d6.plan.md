---
name: Worker Calls Display Screen - Enhanced
overview: "نظام عرض نداء الموظفات على شاشة تلفزيون مع تحسينات UX احترافية: Fullscreen, Audio Notifications, Sticky Calls, و Polling محسّن"
todos:
  - id: add_chair_number_field
    content: إضافة حقل chair_number إلى جدول asmaa_staff_extended_data في migration
    status: pending
  - id: update_worker_calls_api
    content: تحديث Worker_Calls_Controller مع LEFT JOIN و Fallback و تصفية الحالات النشطة
    status: pending
    dependencies:
      - add_chair_number_field
  - id: update_staff_controller
    content: تحديث Staff_Controller لدعم chair_number في create/update operations
    status: pending
    dependencies:
      - add_chair_number_field
  - id: create_audio_file
    content: إضافة ملف صوتي ding.mp3 للتنبيهات
    status: pending
  - id: create_display_component
    content: إنشاء WorkerCalls.vue مع Fullscreen, Audio Notifications, Sticky Calls, و AbortController polling
    status: pending
    dependencies:
      - update_worker_calls_api
      - create_audio_file
  - id: add_route_keepalive
    content: إضافة route جديد /display/worker-calls مع keepAlive meta
    status: pending
    dependencies:
      - create_display_component
  - id: build_assets
    content: Build frontend assets بعد إضافة المكون الجديد
    status: pending
    dependencies:
      - add_route_keepalive
---

# خطة نظام عرض نداء الموظفات على شاشة التلفزيون - النسخة المحسّنة

## الهدف

إنشاء صفحة Display احترافية لنداءات الموظفات على شاشة تلفزيون كبيرة، مع تحسينات UX عالية التأثير من واقع تجربة أنظمة Display.

## المتطلبات الأساسية

- اسم الموظفة
- رقم الكرسي
- اسم العميلة
- الخدمات المطلوبة
- Grid Layout لعرض عدة نداءات

## التحسينات عالية التأثير (High Impact)

### 1. Fullscreen و Keep-Alive

**المشكلة:** شاشات Smart TV تحتاج fullscreen تلقائي وعدم إعادة تحميل المكونات.

**الحل:**

- إضافة زر Fullscreen مخفي (يمكن تفعيله بـ F11 أو زر على الشاشة)
- استخدام `keep-alive` في Router لضمان عدم إعادة تحميل المكونات
- Auto-fullscreen عند تحميل الصفحة (optional)

**التنفيذ:**

```vue
<!-- في App.vue أو router -->
<keep-alive>
  <router-view v-if="$route.name === 'DisplayWorkerCalls'" />
</keep-alive>
```

### 2. التنبيهات الصوتية (Audio Notifications)

**المشكلة:** الموظفة قد لا ترى الشاشة إذا كانت مشغولة.

**الحل:**

- صوت "Ding" بسيط عند ظهور نداء جديد
- صوت عند تغيير حالة النداء من `pending` إلى `staff_called`
- استخدام HTML5 Audio API داخل Vue Component

**التنفيذ:**

```javascript
// في WorkerCalls.vue
const playNotificationSound = () => {
  const audio = new Audio('/wp-content/plugins/asmasalonv1/assets/sounds/ding.mp3');
  audio.volume = 0.5;
  audio.play().catch(e => console.log('Audio play failed:', e));
};

// Watch للنداءات الجديدة
watch(activeCalls, (newCalls, oldCalls) => {
  const newCallIds = newCalls.map(c => c.id);
  const oldCallIds = oldCalls.map(c => c.id);
  const newCall = newCalls.find(c => !oldCallIds.includes(c.id));
  
  if (newCall) {
    playNotificationSound();
  }
});
```

### 3. Sticky Calls (ثبات النداء)

**المشكلة:** النداء يختفي فوراً عند بدء الخدمة.

**الحل:**

- بقاء النداء لـ 5-10 ثوانٍ بعد تغيير الحالة إلى `accepted` أو `serving`
- عرض بحالة "In Progress" بلون مختلف
- إضافة timer لكل نداء لإخفائه بعد فترة

**التنفيذ:**

```javascript
// في WorkerCalls.vue
const stickyCalls = ref(new Map()); // Map<callId, hideTimeout>

watch(activeCalls, (newCalls) => {
  newCalls.forEach(call => {
    if (['accepted', 'serving'].includes(call.status)) {
      // إخفاء بعد 8 ثوانٍ
      const timeout = setTimeout(() => {
        stickyCalls.value.delete(call.id);
      }, 8000);
      stickyCalls.value.set(call.id, timeout);
    }
  });
});
```

### 4. SQL Query مع LEFT JOIN (Fallback)

**المشكلة:** النداء قد لا يظهر إذا لم يكن للموظفة رقم كرسي.

**الحل:**

- استخدام LEFT JOIN لضمان ظهور النداء حتى بدون chair_number
- Fallback value: عرض "غير محدد" أو رقم افتراضي

**التنفيذ في Worker_Calls_Controller:**

```php
$items = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        w.*,
        q.ticket_number,
        q.status AS queue_status,
        u.display_name AS customer_name,
        s.name AS service_name,
        st.display_name AS staff_name,
        COALESCE(ext.chair_number, 0) AS staff_chair_number, -- Fallback to 0
        ext.position AS staff_position
     FROM {$table_calls} w
     LEFT JOIN {$table_queue} q ON w.ticket_id = q.id
     LEFT JOIN {$wpdb->users} u ON q.wc_customer_id = u.ID
     LEFT JOIN {$table_services} s ON q.service_id = s.id
     LEFT JOIN {$wpdb->users} st ON w.wp_user_id = st.ID
     LEFT JOIN {$wpdb->prefix}asmaa_staff_extended_data ext ON ext.wp_user_id = w.wp_user_id
     WHERE w.status IN ('pending', 'staff_called', 'customer_called', 'accepted')
     ORDER BY w.called_at DESC, w.created_at DESC
     LIMIT %d OFFSET %d",
    $params['per_page'],
    $offset
));
```

### 5. Polling Strategy مع AbortController

**المشكلة:** تراكم الطلبات (Request Piling) عند بطء الشبكة.

**الحل:**

- استخدام AbortController لإلغاء الطلب السابق
- Timeout للطلبات (5 ثوانٍ)
- Exponential backoff عند فشل الطلبات

**التنفيذ:**

```javascript
// في WorkerCalls.vue
let abortController = null;
let retryCount = 0;
const MAX_RETRIES = 3;

const loadCalls = async () => {
  // إلغاء الطلب السابق
  if (abortController) {
    abortController.abort();
  }
  
  abortController = new AbortController();
  const timeoutId = setTimeout(() => abortController.abort(), 5000);
  
  try {
    const response = await api.get('/worker-calls', {
      params: {
        status: 'active', // أو array من الحالات
        per_page: 50,
        date: today()
      },
      signal: abortController.signal
    });
    
    clearTimeout(timeoutId);
    retryCount = 0;
    activeCalls.value = response.data?.data?.items || [];
  } catch (error) {
    clearTimeout(timeoutId);
    if (error.name !== 'AbortError' && retryCount < MAX_RETRIES) {
      retryCount++;
      setTimeout(loadCalls, Math.pow(2, retryCount) * 1000); // Exponential backoff
    }
  }
};
```

## التغييرات المطلوبة

### 1. إضافة حقل رقم الكرسي

**ملف:** `includes/Database/Migrations/Create_Extended_Data_Tables.php`

- إضافة `chair_number INT UNSIGNED NULL` إلى `asmaa_staff_extended_data`
- إضافة index: `KEY idx_chair_number (chair_number)`

### 2. تحديث Worker_Calls_Controller

**ملف:** `includes/API/Controllers/Worker_Calls_Controller.php`

- إضافة `COALESCE(ext.chair_number, 0) AS staff_chair_number` مع LEFT JOIN
- تصفية الحالات النشطة: `pending`, `staff_called`, `customer_called`, `accepted`
- دعم multiple services (إذا كان ticket يحتوي على عدة خدمات)

### 3. تحديث Staff_Controller

**ملف:** `includes/API/Controllers/Staff_Controller.php`

- دعم `chair_number` في `create_item()` و `update_item()`

### 4. إنشاء WorkerCalls.vue Component

**ملف:** `assets/src/views/Display/WorkerCalls.vue`

**المميزات:**

- Fullscreen button (F11 أو زر على الشاشة)
- Audio notifications عند نداءات جديدة
- Sticky calls (بقاء 8 ثوانٍ بعد acceptance)
- Polling مع AbortController
- Grid Layout (2-4 columns حسب الشاشة)
- Card design كبير وواضح

**Card Structure:**

```vue
<div class="call-card" 
     :class="getStatusClass(call.status)"
     :style="{ borderLeft: `8px solid ${getStatusColor(call.status)}` }">
  <div class="card-header">
    <h2 class="staff-name">{{ call.staff_name }}</h2>
    <div class="chair-badge">
      كرسي: {{ call.staff_chair_number || 'غير محدد' }}
    </div>
  </div>
  
  <div class="card-body">
    <div class="customer-section">
      <p class="label">العميلة:</p>
      <h3 class="customer-name">{{ call.customer_name }}</h3>
    </div>
    
    <div class="services-section">
      <span v-for="service in call.services" 
            class="service-badge">
        {{ service.name }}
      </span>
    </div>
  </div>
  
  <div class="card-footer">
    <span class="status-badge" :class="call.status">
      {{ getStatusText(call.status) }}
    </span>
  </div>
</div>
```

### 5. إضافة Route مع Keep-Alive

**ملف:** `assets/src/router/index.js`

```javascript
{
  path: '/display/worker-calls',
  name: 'DisplayWorkerCalls',
  component: () => import('../views/Display/WorkerCalls.vue'),
  meta: { keepAlive: true }
}
```

### 6. إضافة ملف صوتي

**ملف:** `assets/sounds/ding.mp3`

- صوت "Ding" بسيط وواضح
- مدة قصيرة (0.5-1 ثانية)
- حجم صغير (< 50KB)

## البنية التقنية

### Data Flow:

```
Worker Calls API (with AbortController) 
  → WorkerCalls.vue 
    → Audio Notification (on new call)
    → Sticky Calls Manager
    → Grid Display Cards
```

### State Management:

```javascript
const activeCalls = ref([]); // النداءات النشطة
const stickyCalls = ref(new Map()); // النداءات الثابتة
const lastCallIds = ref(new Set()); // لتتبع النداءات الجديدة
```

### Status Colors:

- `pending`: Yellow/Orange (#f59e0b)
- `staff_called`: Blue (#3b82f6)
- `customer_called`: Green (#10b981)
- `accepted/serving`: Purple (#8b5cf6) - Sticky state

## الملفات المطلوبة

1. `includes/Database/Migrations/Create_Extended_Data_Tables.php` - إضافة chair_number
2. `includes/API/Controllers/Worker_Calls_Controller.php` - تحسين Query + Fallback
3. `includes/API/Controllers/Staff_Controller.php` - دعم chair_number
4. `assets/src/views/Display/WorkerCalls.vue` - **ملف جديد مع جميع التحسينات**
5. `assets/src/router/index.js` - إضافة route مع keepAlive
6. `assets/sounds/ding.mp3` - **ملف صوتي جديد**

## خطوات التنفيذ

1. إضافة migration لحقل chair_number
2. تحديث Controllers (Worker_Calls + Staff)
3. إنشاء ملف صوتي ding.mp3
4. إنشاء WorkerCalls.vue مع جميع التحسينات:

   - Fullscreen functionality
   - Audio notifications
   - Sticky calls logic
   - AbortController polling
   - Grid layout

5. إضافة Route مع keepAlive
6. Build assets
7. Test على Smart TV

## ملاحظات التصميم

- استخدام Design Tokens من `design-tokens.css`
- استخدام CoreUI Icons (cil-*)
- خطوط كبيرة: Staff name (4xl), Customer name (3xl), Chair badge (2xl)
- ألوان واضحة وبارزة للقراءة من مسافة بعيدة
- Animations سلسة: fadeIn, slideIn, scale
- Responsive: 2 columns (mobile), 3-4 columns (TV)
- Dark theme مع gradients (مثل Queue.vue)
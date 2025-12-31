# 📚 وثيقة المشروع الشاملة - نظام إدارة صالون أسماء الجارالله

## 📋 جدول المحتويات

1. [نظرة عامة على المشروع](#نظرة-عامة-على-المشروع)
2. [المواصفات الفنية والتقنية](#المواصفات-الفنية-والتقنية)
3. [البنية المعمارية](#البنية-المعمارية)
4. [نظام الأمان](#نظام-الأمان)
5. [الأداء والتحسينات](#الأداء-والتحسينات)
6. [الثيم والألوان والشعار](#الثيم-والألوان-والشعار)
7. [مكونات النظام](#مكونات-النظام)
8. [قاعدة البيانات](#قاعدة-البيانات)
9. [واجهة المستخدم](#واجهة-المستخدم)
10. [التكاملات](#التكاملات)

---

## 🎯 نظرة عامة على المشروع

### تعريف المشروع
**نظام إدارة صالون أسماء الجارالله** هو بلاجن ووردبريس متكامل لإدارة صالونات التجميل والجمال. يوفر النظام إدارة شاملة لجميع عمليات الصالون من الحجوزات والطلبات إلى إدارة العملاء والموظفين والعمولات.

### الإصدار الحالي
- **الإصدار:** 0.2.0
- **تاريخ آخر تحديث:** ديسمبر 2025
- **الحالة:** ✅ جاهز للاستخدام الأساسي

### الميزات الرئيسية
- ✅ إدارة العملاء والخدمات والموظفين
- ✅ نظام الحجوزات المتقدم
- ✅ قائمة الانتظار (Queue Management)
- ✅ نظام الطلبات والفواتير والمدفوعات
- ✅ نظام الولاء (Loyalty Points)
- ✅ نظام العضويات (Memberships)
- ✅ نظام العمولات للموظفين
- ✅ إدارة المخزون والمنتجات
- ✅ التقارير والإحصائيات
- ✅ التكامل مع WooCommerce
- ✅ واجهة إدارة حديثة (Vue.js + CoreUI)

---

## 💻 المواصفات الفنية والتقنية

### التقنيات الأساسية

#### Backend (PHP)
- **PHP Version:** 8.0+
- **WordPress Version:** 6.0+
- **Architecture Pattern:** MVC (Model-View-Controller)
- **Namespace:** `AsmaaSalon\`
- **Autoloading:** PSR-4 Autoloader
- **Database:** MySQL/MariaDB (WordPress Database)

#### Frontend (JavaScript/Vue)
- **Vue.js:** 3.4.21
- **Vue Router:** 4.3.0 (Hash Mode)
- **State Management:** Pinia 2.1.7
- **UI Framework:** CoreUI Vue 5.4.0
- **Icons:** CoreUI Icons 2.0.1
- **Charts:** Chart.js 4.4.0 + Vue Chart.js 5.2.0
- **Calendar:** FullCalendar 6.1.19
- **HTTP Client:** Axios 1.6.7
- **Build Tool:** Vite 5.1.4
- **CSS Preprocessor:** Sass

### البنية التقنية

```
asmaa-salon/
├── asmaa-salon.php          # Plugin entry point
├── includes/
│   ├── Plugin.php           # Main plugin class (Singleton)
│   ├── API/
│   │   └── Controllers/     # REST API Controllers (23 controllers)
│   ├── Database/
│   │   ├── Schema.php        # Database schema definitions
│   │   └── Migrations/       # Database migrations
│   ├── Security/
│   │   └── Capabilities.php  # RBAC system
│   ├── Services/             # Business logic services
│   └── Install/              # Activation/Deactivation
├── assets/
│   ├── src/                  # Vue.js source files
│   │   ├── main.js           # Vue app entry
│   │   ├── App.vue           # Root component
│   │   ├── components/       # Reusable components
│   │   ├── views/            # Page views (45 views)
│   │   ├── stores/           # Pinia stores
│   │   ├── router/           # Vue Router config
│   │   ├── locales/          # i18n translations
│   │   └── style.css         # Global styles
│   └── build/                # Compiled assets
└── tests/                     # Test files
```

### متطلبات النظام

#### الخادم (Server)
- PHP 8.0 أو أحدث
- MySQL 5.7+ أو MariaDB 10.3+
- Apache/Nginx مع mod_rewrite
- WordPress 6.0 أو أحدث

#### المتصفح (Browser)
- Chrome/Edge (آخر إصدارين)
- Firefox (آخر إصدارين)
- Safari 14+
- دعم JavaScript ES6+

---

## 🏗️ البنية المعمارية

### نمط التصميم (Design Patterns)

#### 1. Singleton Pattern
```php
// Plugin.php
class Plugin {
    protected static ?Plugin $instance = null;
    
    public static function instance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

#### 2. MVC Pattern
- **Model:** Database tables + WordPress data
- **View:** Vue.js components + Blade templates
- **Controller:** REST API Controllers

#### 3. Repository Pattern (مستقبلي)
- فصل منطق الوصول للبيانات عن Controllers
- إعادة استخدام الكود
- سهولة الاختبار

### REST API Architecture

#### Namespace Structure
```
/wp-json/asmaa-salon/v1/
├── /ping                    # Health check
├── /customers                # Customer management
├── /services                 # Service management
├── /staff                    # Staff management
├── /bookings                 # Booking management
├── /queue                    # Queue management
├── /orders                   # Order management
├── /invoices                 # Invoice management
├── /payments                 # Payment management
├── /products                 # Product management
├── /inventory                # Inventory management
├── /loyalty                  # Loyalty points
├── /memberships              # Membership management
├── /commissions              # Staff commissions
├── /reports                  # Reports & analytics
├── /notifications            # Notifications
├── /pos                      # POS system
├── /users                    # User management
└── /settings                 # System settings
```

#### Response Standardization
```php
// Success Response
{
    "success": true,
    "data": {...},
    "message": "Operation completed"
}

// Error Response
{
    "code": "asmaa_salon_error",
    "message": "Error description",
    "data": {
        "status": 400
    }
}
```

### Frontend Architecture

#### Component Hierarchy
```
App.vue
├── Sidebar.vue          # Navigation sidebar
├── Topbar.vue           # Header with notifications
└── RouterView
    ├── Dashboard.vue
    ├── Customers/
    │   ├── Index.vue
    │   └── Profile.vue
    ├── Services/
    ├── Staff/
    ├── Bookings/
    ├── Queue/
    ├── Orders/
    ├── Invoices/
    ├── Payments/
    ├── Products/
    ├── Inventory/
    ├── Commissions/
    ├── Reports/
    ├── Loyalty/
    ├── Memberships/
    └── Settings/
```

#### State Management (Pinia)
```javascript
// Stores structure
stores/
├── ui.js              # UI state (theme, sidebar)
├── auth.js            # Authentication state
└── [feature].js       # Feature-specific stores
```

### Database Architecture

#### Table Prefix
جميع الجداول تستخدم prefix: `wp_asmaa_`

#### Core Tables (22 جدول)
1. **Customers** - العملاء
2. **Services** - الخدمات
3. **Staff** - الموظفين
4. **Staff_Ratings** - تقييمات الموظفين
5. **Bookings** - الحجوزات
6. **Queue_Tickets** - تذاكر الانتظار
7. **Worker_Calls** - استدعاءات الموظفين
8. **Orders** - الطلبات
9. **Order_Items** - عناصر الطلب
10. **Invoices** - الفواتير
11. **Invoice_Items** - عناصر الفاتورة
12. **Payments** - المدفوعات
13. **Products** - المنتجات
14. **Inventory_Movements** - حركات المخزون
15. **Membership_Plans** - خطط العضوية
16. **Customer_Memberships** - عضويات العملاء
17. **Membership_Service_Usage** - استخدام خدمات العضوية
18. **Membership_Extensions** - تمديدات العضوية
19. **Loyalty_Transactions** - معاملات الولاء
20. **Staff_Commissions** - عمولات الموظفين
21. **Commission_Settings** - إعدادات العمولات
22. **POS_Sessions** - جلسات الكاشير

#### Soft Delete Pattern
جميع الجداول تستخدم `deleted_at` للـ Soft Delete:
```sql
deleted_at DATETIME NULL
```

#### Indexing Strategy
- **Primary Keys:** جميع الجداول لها `id BIGINT UNSIGNED`
- **Foreign Keys:** مفاتيح خارجية مفهرسة
- **Search Fields:** `name`, `phone`, `email` مفهرسة
- **Status Fields:** `status`, `is_active` مفهرسة
- **Date Fields:** `created_at`, `updated_at` مفهرسة
- **Composite Indexes:** للاستعلامات المعقدة

---

## 🔒 نظام الأمان

### 1. المصادقة والتفويض (Authentication & Authorization)

#### WordPress Integration
- يستخدم نظام المصادقة المدمج في WordPress
- جميع الطلبات تتطلب تسجيل دخول (`is_user_logged_in()`)
- استخدام WordPress Nonce للـ CSRF Protection

#### Role-Based Access Control (RBAC)

##### الأدوار المخصصة (7 أدوار)
1. **asmaa_super_admin** - المدير الأعلى
   - جميع الصلاحيات
   - إدارة النظام بالكامل

2. **asmaa_admin** - المدير
   - إدارة شاملة لجميع الوحدات
   - إعدادات النظام

3. **asmaa_accountant** - المحاسب
   - عرض وتعديل الفواتير والمدفوعات
   - التقارير المالية
   - لا يمكنه حذف البيانات

4. **asmaa_manager** - مدير العمليات
   - إدارة الحجوزات والطلبات
   - إدارة الموظفين
   - التقارير

5. **asmaa_receptionist** - موظف الاستقبال
   - إنشاء وتعديل الحجوزات
   - إدارة قائمة الانتظار
   - عرض العملاء

6. **asmaa_cashier** - الكاشير
   - إنشاء الطلبات والفواتير
   - تسجيل المدفوعات
   - إدارة POS

7. **asmaa_staff** - الموظف
   - عرض الحجوزات الخاصة به
   - عرض العمولات الخاصة به
   - تحديث حالة الخدمات

##### Capabilities System (90+ صلاحية)
```php
// Format: asmaa_[resource]_[action]
'asmaa_customers_view'
'asmaa_customers_create'
'asmaa_customers_update'
'asmaa_customers_delete'
// ... إلخ
```

#### Permission Checks
```php
// في Controllers
protected function check_permission(string $capability): bool {
    return is_user_logged_in() && 
           current_user_can('manage_options');
}
```

### 2. حماية البيانات (Data Protection)

#### Input Sanitization
- جميع المدخلات يتم تنظيفها باستخدام WordPress functions:
  - `sanitize_text_field()`
  - `sanitize_email()`
  - `sanitize_textarea_field()`
  - `absint()` للأرقام

#### Output Escaping
- جميع المخرجات يتم escape باستخدام:
  - `esc_html()`
  - `esc_attr()`
  - `esc_url()`
  - `wp_kses_post()`

#### SQL Injection Prevention
- استخدام `$wpdb->prepare()` لجميع الاستعلامات
- استخدام WordPress Query API عند الإمكان
- لا يتم استخدام `$_GET` أو `$_POST` مباشرة في الاستعلامات

#### XSS Prevention
- تنظيف جميع المدخلات
- Escape جميع المخرجات
- استخدام WordPress Nonce

### 3. أمان REST API

#### Nonce Verification
```php
// كل طلب REST API يتطلب Nonce
wp_create_nonce('wp_rest')
```

#### Permission Callbacks
```php
register_rest_route($namespace, '/endpoint', [
    'permission_callback' => [$this, 'permission_callback'],
]);
```

#### Rate Limiting (مستقبلي)
- حماية من DDoS attacks
- تحديد عدد الطلبات لكل مستخدم

### 4. أمان قاعدة البيانات

#### Prepared Statements
```php
$wpdb->prepare(
    "SELECT * FROM {$table} WHERE id = %d AND status = %s",
    $id,
    $status
);
```

#### Database Prefix
- جميع الجداول تستخدم WordPress prefix
- منع SQL injection من خلال table names

### 5. أمان الملفات

#### File Upload Security
- التحقق من نوع الملف
- التحقق من حجم الملف
- تخزين الملفات في مجلد آمن
- منع تنفيذ الملفات المرفوعة

#### Path Traversal Prevention
- استخدام `realpath()` للتحقق من المسارات
- منع الوصول للملفات خارج المجلدات المسموحة

---

## ⚡ الأداء والتحسينات

### 1. تحسين قاعدة البيانات

#### Indexing
- **Primary Keys:** على جميع الجداول
- **Foreign Keys:** مفهرسة للـ JOINs السريعة
- **Search Fields:** `name`, `phone`, `email` مفهرسة
- **Status Fields:** `status`, `is_active` مفهرسة
- **Date Fields:** `created_at`, `updated_at` مفهرسة
- **Composite Indexes:** للاستعلامات المعقدة

```sql
-- مثال على Composite Index
KEY idx_staff_date (staff_id, booking_date)
KEY idx_booking_date_time (booking_date, booking_time)
```

#### Query Optimization
- استخدام `SELECT` محدود (لا `SELECT *`)
- استخدام `LIMIT` للـ pagination
- تجنب N+1 queries
- استخدام `JOIN` بدلاً من queries متعددة

#### Caching Strategy (مستقبلي)
- WordPress Transients API
- Object Caching
- Query Result Caching

### 2. تحسين Frontend

#### Code Splitting
```javascript
// Vite automatic code splitting
// كل route يتم تحميله بشكل منفصل
```

#### Lazy Loading
- Vue Router lazy loading للـ routes
- Lazy loading للصور
- Lazy loading للـ components الكبيرة

#### Asset Optimization
- **Minification:** CSS و JavaScript
- **Compression:** Gzip/Brotli
- **CDN:** للملفات الثابتة (مستقبلي)

#### Bundle Size
- Tree shaking
- Dead code elimination
- Dynamic imports

### 3. تحسين API

#### Response Caching
```php
// Cache API responses
wp_cache_set($cache_key, $data, 'asmaa_salon', 3600);
```

#### Pagination
- جميع endpoints تدعم pagination
- Default: 20 items per page
- Maximum: 100 items per page

#### Filtering & Searching
- Server-side filtering
- Server-side searching
- Reduced data transfer

### 4. تحسين الصور

#### Image Optimization
- استخدام WordPress image sizes
- Lazy loading للصور
- WebP format (مستقبلي)

### 5. Monitoring & Analytics

#### Performance Monitoring (مستقبلي)
- Response time tracking
- Query performance monitoring
- Error logging

---

## 🎨 الثيم والألوان والشعار

### نظام الألوان (Color System)

#### اللون الأساسي (Primary Color)
```css
--asmaa-primary: hsl(35, 30%, 61%); /* #BBA07A */
```

**اللون الذهبي الدافئ** يمثل الفخامة والجمال، مناسب لصالونات التجميل.

#### Primary Color Palette
```css
--asmaa-primary-50:  hsl(35, 70%, 97%);  /* Lightest */
--asmaa-primary-100: hsl(35, 65%, 94%);
--asmaa-primary-200: hsl(35, 60%, 87%);
--asmaa-primary-300: hsl(35, 55%, 77%);
--asmaa-primary-400: hsl(35, 45%, 67%);
--asmaa-primary-500: hsl(35, 30%, 61%);   /* Primary */
--asmaa-primary-600: hsl(35, 30%, 51%);
--asmaa-primary-700: hsl(35, 29%, 41%);
--asmaa-primary-800: hsl(35, 28%, 31%);
--asmaa-primary-900: hsl(35, 27%, 21%);
--asmaa-primary-950: hsl(35, 26%, 11%);  /* Darkest */
```

#### الألوان الإضافية
```css
--asmaa-success: hsl(142, 71%, 45%);  /* أخضر - نجاح */
--asmaa-warning: hsl(38, 92%, 50%);   /* برتقالي - تحذير */
--asmaa-danger:  hsl(0, 84%, 60%);    /* أحمر - خطأ */
--asmaa-secondary: hsl(218, 13%, 28%); /* رمادي - ثانوي */
--asmaa-info: hsl(210, 90%, 55%);     /* أزرق - معلومات */
```

#### Soft Color Variants
```css
/* للـ badges والـ pills */
--asmaa-primary-soft: hsla(35, 30%, 61%, 0.14);
--asmaa-primary-soft-border: hsla(35, 30%, 61%, 0.30);
```

### نظام الخطوط (Typography System)

#### الخط الأساسي
```css
--font-family-base: 'Cairo', -apple-system, BlinkMacSystemFont, 
                    'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
```

**خط Cairo** يدعم العربية والإنجليزية بشكل ممتاز.

#### Font Sizes
```css
--font-size-xs:   0.75rem;   /* 12px */
--font-size-sm:   0.875rem;  /* 14px */
--font-size-base: 1rem;      /* 16px */
--font-size-lg:   1.125rem;  /* 18px */
--font-size-xl:   1.25rem;   /* 20px */
--font-size-2xl:  1.5rem;    /* 24px */
--font-size-3xl:  1.875rem;  /* 30px */
--font-size-4xl:  2.25rem;   /* 36px */
--font-size-5xl:  3rem;      /* 48px */
```

#### Font Weights
```css
--font-weight-light:     300;
--font-weight-normal:   400;
--font-weight-medium:   500;
--font-weight-semibold: 600;
--font-weight-bold:     700;
--font-weight-extrabold: 800;
```

### نظام المسافات (Spacing System)
```css
--spacing-xs:  0.25rem;  /* 4px */
--spacing-sm:  0.5rem;   /* 8px */
--spacing-md:  0.75rem;  /* 12px */
--spacing-base: 1rem;    /* 16px */
--spacing-lg:  1.5rem;   /* 24px */
--spacing-xl:  2rem;     /* 32px */
--spacing-2xl: 3rem;     /* 48px */
--spacing-3xl: 4rem;     /* 64px */
```

### نظام الحدود (Border Radius)
```css
--radius-sm:   0.25rem;  /* 4px */
--radius-md:   0.375rem; /* 6px */
--radius-lg:   0.5rem;   /* 8px */
--radius-xl:   0.75rem;  /* 12px */
--radius-2xl:  1rem;     /* 16px */
--radius-full: 9999px;   /* Full circle */
```

### الثيمات (Themes)

#### Light Mode (الوضع النهاري)
```css
--bg-primary:    hsl(36, 33%, 97%);  /* خلفية رئيسية */
--bg-secondary:   hsl(35, 10%, 95%);  /* خلفية ثانوية */
--bg-tertiary:   hsl(36, 10%, 92%);  /* خلفية ثالثية */
--text-primary:   hsl(36, 10%, 16%);  /* نص رئيسي */
--text-secondary: hsl(36, 10%, 30%);  /* نص ثانوي */
--text-muted:    hsl(36, 10%, 45%);  /* نص خافت */
--border-color:  hsl(36, 10%, 90%);  /* لون الحدود */
```

#### Dark Mode (الوضع الليلي)
```css
--bg-primary:    hsl(36, 10%, 10%);  /* خلفية داكنة */
--bg-secondary:  hsl(36, 10%, 20%);  /* خلفية ثانوية */
--bg-tertiary:   hsl(36, 10%, 14%);  /* خلفية ثالثية */
--text-primary:  hsl(36, 10%, 98%);  /* نص فاتح */
--text-secondary: hsl(36, 10%, 88%); /* نص ثانوي */
--text-muted:    hsl(36, 10%, 72%);  /* نص خافت */
--border-color:  hsl(36, 10%, 20%);  /* لون الحدود */
```

### الظلال (Shadows)
```css
/* Light Mode */
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.10);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.12);

/* Dark Mode */
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.35);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.45);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.60);
```

### الشعار (Logo)

#### Logo Source
```php
// Priority order:
1. WordPress Custom Logo (Theme Customizer)
2. WordPress Site Icon
3. Fallback: Empty
```

#### Logo Configuration
```javascript
// في AsmaaSalonConfig
logoUrl: wp_get_attachment_image_url(custom_logo) || 
         get_site_icon_url(512) || 
         ''
```

### RTL Support (دعم العربية)

#### Direction
```css
[dir="rtl"] {
    direction: rtl;
    text-align: right;
}
```

#### Margin/Padding Utilities
```css
/* RTL-aware utilities */
[dir="rtl"] .ms-auto { margin-right: auto; }
[dir="rtl"] .me-auto { margin-left: auto; }
```

---

## 🧩 مكونات النظام

### 1. وحدات الإدارة الأساسية

#### إدارة العملاء (Customers)
- **CRUD Operations:** إنشاء، قراءة، تحديث، حذف
- **Search & Filter:** بحث متقدم بالاسم، الهاتف، البريد
- **Customer Profile:** ملف عميل شامل
- **Loyalty Points:** عرض نقاط الولاء
- **Memberships:** عرض العضويات النشطة
- **Visit History:** سجل الزيارات
- **WooCommerce Sync:** مزامنة تلقائية مع WooCommerce

#### إدارة الخدمات (Services)
- **Service Management:** إدارة الخدمات والتصنيفات
- **Pricing:** إدارة الأسعار والمدة
- **Service Images:** رفع صور الخدمات
- **Active/Inactive:** تفعيل/تعطيل الخدمات

#### إدارة الموظفين (Staff)
- **Staff Management:** إدارة بيانات الموظفين
- **Ratings System:** نظام تقييم الموظفين
- **Commissions:** عرض العمولات
- **Service Assignment:** ربط الخدمات بالموظفين
- **Performance Stats:** إحصائيات الأداء

### 2. نظام الحجوزات (Bookings)

#### Booking Management
- **Create Booking:** إنشاء حجز جديد
- **Calendar View:** عرض الحجوزات في تقويم
- **Status Management:** إدارة حالات الحجز
  - `pending` - قيد الانتظار
  - `confirmed` - مؤكد
  - `completed` - مكتمل
  - `cancelled` - ملغي
- **Reminders:** تذكيرات تلقائية
- **Booking Settings:** إعدادات الحجوزات

#### Booking Appearance
- **Customizable Display:** تخصيص عرض الحجوزات
- **Theme Settings:** إعدادات الثيم
- **Layout Options:** خيارات التخطيط

### 3. قائمة الانتظار (Queue)

#### Queue Management
- **Ticket Creation:** إنشاء تذاكر الانتظار
- **Status Tracking:** تتبع حالة التذاكر
  - `waiting` - في الانتظار
  - `called` - تم الاستدعاء
  - `in_service` - قيد الخدمة
  - `completed` - مكتمل
  - `cancelled` - ملغي
- **Auto-refresh:** تحديث تلقائي كل 5 ثوان
- **Call System:** نظام استدعاء العملاء
- **Display Screen:** شاشة عرض عامة

### 4. نظام الطلبات (Orders)

#### Order Management
- **Order Creation:** إنشاء طلب جديد
- **Order Items:** إدارة عناصر الطلب
- **Status Tracking:** تتبع حالة الطلب
- **POS Integration:** تكامل مع نظام الكاشير
- **WooCommerce Sync:** مزامنة مع WooCommerce

### 5. نظام الفواتير (Invoices)

#### Invoice Management
- **Invoice Generation:** إنشاء فواتير تلقائية
- **Invoice Items:** عناصر الفاتورة
- **Payment Tracking:** تتبع المدفوعات
- **Status Management:** إدارة حالات الفاتورة
  - `draft` - مسودة
  - `sent` - مرسلة
  - `paid` - مدفوعة
  - `overdue` - متأخرة
  - `cancelled` - ملغية

### 6. نظام المدفوعات (Payments)

#### Payment Management
- **Payment Recording:** تسجيل المدفوعات
- **Payment Methods:** طرق الدفع
  - Cash - نقدي
  - Card - بطاقة
  - Bank Transfer - تحويل بنكي
  - Online - أونلاين
- **Auto Invoice Update:** تحديث تلقائي للفواتير
- **Payment History:** سجل المدفوعات

### 7. إدارة المخزون (Inventory)

#### Product Management
- **Product CRUD:** إدارة المنتجات
- **Stock Tracking:** تتبع المخزون
- **Low Stock Alerts:** تنبيهات المخزون المنخفض
- **Inventory Movements:** حركات المخزون
- **Stock Adjustments:** تعديلات المخزون
- **WooCommerce Sync:** مزامنة مع WooCommerce

### 8. نظام الولاء (Loyalty)

#### Loyalty Points System
- **Points Earning:** كسب النقاط
- **Points Redemption:** استبدال النقاط
- **Points Adjustment:** تعديل النقاط
- **Transaction History:** سجل المعاملات
- **Points Expiry:** انتهاء صلاحية النقاط (مستقبلي)

### 9. نظام العضويات (Memberships)

#### Membership Plans
- **Plan Management:** إدارة الخطط
- **Plan Features:** ميزات كل خطة
- **Pricing:** أسعار الخطط
- **Duration:** مدة العضوية

#### Customer Memberships
- **Membership Assignment:** ربط العضويات بالعملاء
- **Renewal System:** نظام التجديد
- **Extension System:** نظام التمديد
- **Service Usage Tracking:** تتبع استخدام الخدمات
- **Expiry Notifications:** إشعارات انتهاء الصلاحية

### 10. نظام العمولات (Commissions)

#### Commission Management
- **Commission Calculation:** حساب العمولات
- **Commission Approval:** الموافقة على العمولات
- **Commission Settings:** إعدادات العمولات
- **Commission History:** سجل العمولات
- **Bulk Approval:** موافقة جماعية

### 11. التقارير والإحصائيات (Reports)

#### Report Types
- **Sales Reports:** تقارير المبيعات
- **Booking Reports:** تقارير الحجوزات
- **Customer Reports:** تقارير العملاء
- **Staff Reports:** تقارير الموظفين
- **Revenue Reports:** تقارير الإيرادات
- **Performance Reports:** تقارير الأداء

#### Dashboard Statistics
- **Today's Stats:** إحصائيات اليوم
- **Revenue Charts:** رسوم بيانية للإيرادات
- **Top Services:** الخدمات الأكثر طلباً
- **Top Staff:** الموظفين الأكثر أداءً

### 12. نظام الإشعارات (Notifications)

#### Notification Types
- **Booking Reminders:** تذكيرات الحجوزات
- **Membership Expiry:** انتهاء العضويات
- **Low Stock Alerts:** تنبيهات المخزون
- **Payment Reminders:** تذكيرات المدفوعات
- **System Notifications:** إشعارات النظام

### 13. نظام المستخدمين (Users)

#### User Management
- **User CRUD:** إدارة المستخدمين
- **Role Assignment:** تعيين الأدوار
- **Permission Management:** إدارة الصلاحيات
- **User Profile:** ملف المستخدم

### 14. نظام الإعدادات (Settings)

#### Settings Categories
- **General Settings:** الإعدادات العامة
- **Booking Settings:** إعدادات الحجوزات
- **Loyalty Settings:** إعدادات الولاء
- **Membership Settings:** إعدادات العضويات
- **Commission Settings:** إعدادات العمولات
- **WooCommerce Settings:** إعدادات WooCommerce
- **Notification Settings:** إعدادات الإشعارات

### 15. نظام الكاشير (POS)

#### POS Features
- **POS Sessions:** جلسات الكاشير
- **Quick Checkout:** دفع سريع
- **Receipt Printing:** طباعة الفواتير (مستقبلي)
- **Payment Processing:** معالجة المدفوعات

---

## 🗄️ قاعدة البيانات

### Database Schema Overview

#### Core Tables Structure

##### 1. Customers Table
```sql
wp_asmaa_customers
├── id (PK)
├── name
├── phone (UNIQUE)
├── email
├── address
├── city
├── date_of_birth
├── gender
├── notes
├── total_visits
├── total_spent
├── loyalty_points
├── last_visit_at
├── preferred_staff_id (FK)
├── is_active
├── wc_customer_id (WooCommerce sync)
├── wc_synced_at
├── created_at
├── updated_at
└── deleted_at (Soft Delete)
```

##### 2. Services Table
```sql
wp_asmaa_services
├── id (PK)
├── name
├── name_ar
├── description
├── price
├── duration (minutes)
├── category
├── is_active
├── image
├── created_at
├── updated_at
└── deleted_at
```

##### 3. Staff Table
```sql
wp_asmaa_staff
├── id (PK)
├── user_id (FK to wp_users)
├── name
├── phone
├── email
├── position
├── hire_date
├── salary
├── commission_rate
├── photo
├── is_active
├── rating (average)
├── total_ratings
├── total_services
├── total_revenue
├── service_ids (JSON)
├── notes
├── created_at
├── updated_at
└── deleted_at
```

##### 4. Bookings Table
```sql
wp_asmaa_bookings
├── id (PK)
├── customer_id (FK)
├── staff_id (FK)
├── service_id (FK)
├── booking_date
├── booking_time
├── end_time
├── status (pending/confirmed/completed/cancelled)
├── price
├── discount
├── final_price
├── notes
├── reminder_sent
├── source
├── queue_ticket_id (FK)
├── confirmed_at
├── completed_at
├── created_at
├── updated_at
└── deleted_at
```

##### 5. Queue Tickets Table
```sql
wp_asmaa_queue_tickets
├── id (PK)
├── customer_id (FK)
├── service_id (FK)
├── staff_id (FK)
├── ticket_number
├── status (waiting/called/in_service/completed/cancelled)
├── called_at
├── started_at
├── completed_at
├── notes
├── created_at
├── updated_at
└── deleted_at
```

##### 6. Orders Table
```sql
wp_asmaa_orders
├── id (PK)
├── customer_id (FK)
├── staff_id (FK)
├── order_number
├── status (pending/processing/completed/cancelled)
├── subtotal
├── discount
├── tax
├── total
├── payment_status (unpaid/partial/paid)
├── notes
├── wc_order_id (WooCommerce sync)
├── wc_synced_at
├── created_at
├── updated_at
└── deleted_at
```

##### 7. Invoices Table
```sql
wp_asmaa_invoices
├── id (PK)
├── order_id (FK)
├── customer_id (FK)
├── invoice_number
├── status (draft/sent/paid/overdue/cancelled)
├── subtotal
├── discount
├── tax
├── total
├── paid_amount
├── due_amount
├── due_date
├── issued_at
├── paid_at
├── created_at
├── updated_at
└── deleted_at
```

##### 8. Payments Table
```sql
wp_asmaa_payments
├── id (PK)
├── invoice_id (FK)
├── order_id (FK)
├── customer_id (FK)
├── amount
├── payment_method (cash/card/bank_transfer/online)
├── payment_date
├── reference_number
├── notes
├── wc_payment_id (WooCommerce sync)
├── created_at
├── updated_at
└── deleted_at
```

##### 9. Products Table
```sql
wp_asmaa_products
├── id (PK)
├── name
├── name_ar
├── description
├── sku
├── price
├── cost
├── stock_quantity
├── low_stock_threshold
├── category
├── image
├── is_active
├── wc_product_id (WooCommerce sync)
├── wc_synced_at
├── created_at
├── updated_at
└── deleted_at
```

##### 10. Inventory Movements Table
```sql
wp_asmaa_inventory_movements
├── id (PK)
├── product_id (FK)
├── movement_type (in/out/adjustment)
├── quantity
├── previous_stock
├── new_stock
├── reference_type (order/return/adjustment)
├── reference_id
├── notes
├── created_by (user_id)
├── created_at
└── updated_at
```

##### 11. Membership Plans Table
```sql
wp_asmaa_membership_plans
├── id (PK)
├── name_ar
├── name_en
├── description
├── price
├── duration_months
├── free_services_count
├── discount_percentage
├── is_active
├── created_at
├── updated_at
└── deleted_at
```

##### 12. Customer Memberships Table
```sql
wp_asmaa_customer_memberships
├── id (PK)
├── customer_id (FK)
├── membership_plan_id (FK)
├── start_date
├── end_date
├── status (active/expired/cancelled)
├── services_used
├── services_limit
├── auto_renew
├── created_at
├── updated_at
└── deleted_at
```

##### 13. Loyalty Transactions Table
```sql
wp_asmaa_loyalty_transactions
├── id (PK)
├── customer_id (FK)
├── transaction_type (earn/redeem/adjust)
├── points
├── balance_after
├── reference_type (order/booking/manual)
├── reference_id
├── notes
├── created_at
└── updated_at
```

##### 14. Staff Commissions Table
```sql
wp_asmaa_staff_commissions
├── id (PK)
├── staff_id (FK)
├── order_id (FK)
├── booking_id (FK)
├── commission_amount
├── commission_rate
├── status (pending/approved/paid)
├── approved_at
├── approved_by (user_id)
├── paid_at
├── notes
├── created_at
└── updated_at
```

### Relationships

#### Foreign Key Relationships
```
customers
  ├── preferred_staff_id → staff.id
  └── wc_customer_id → WooCommerce customer

staff
  └── user_id → wp_users.ID

bookings
  ├── customer_id → customers.id
  ├── staff_id → staff.id
  ├── service_id → services.id
  └── queue_ticket_id → queue_tickets.id

queue_tickets
  ├── customer_id → customers.id
  ├── service_id → services.id
  └── staff_id → staff.id

orders
  ├── customer_id → customers.id
  └── staff_id → staff.id

order_items
  ├── order_id → orders.id
  └── product_id → products.id

invoices
  ├── order_id → orders.id
  └── customer_id → customers.id

payments
  ├── invoice_id → invoices.id
  ├── order_id → orders.id
  └── customer_id → customers.id

customer_memberships
  ├── customer_id → customers.id
  └── membership_plan_id → membership_plans.id

loyalty_transactions
  └── customer_id → customers.id

staff_commissions
  ├── staff_id → staff.id
  ├── order_id → orders.id
  └── booking_id → bookings.id
```

### Indexing Strategy

#### Primary Indexes
- جميع الجداول لها `id` كـ Primary Key

#### Unique Indexes
- `customers.phone` - UNIQUE
- `orders.order_number` - UNIQUE
- `invoices.invoice_number` - UNIQUE
- `queue_tickets.ticket_number` - UNIQUE

#### Foreign Key Indexes
- جميع Foreign Keys مفهرسة

#### Search Indexes
- `customers.name`
- `customers.email`
- `services.name`
- `products.name`
- `products.sku`

#### Status Indexes
- `customers.is_active`
- `services.is_active`
- `staff.is_active`
- `bookings.status`
- `orders.status`
- `invoices.status`

#### Date Indexes
- `bookings.booking_date`
- `bookings.created_at`
- `orders.created_at`
- `invoices.created_at`

#### Composite Indexes
- `(staff_id, booking_date)` - للاستعلامات المعقدة
- `(booking_date, booking_time)` - للبحث في الحجوزات
- `(customer_id, status)` - للبحث في الطلبات

---

## 🖥️ واجهة المستخدم

### Frontend Architecture

#### Technology Stack
- **Vue.js 3.4.21** - Framework
- **Vue Router 4.3.0** - Routing (Hash Mode)
- **Pinia 2.1.7** - State Management
- **CoreUI Vue 5.4.0** - UI Components
- **Axios 1.6.7** - HTTP Client
- **Chart.js 4.4.0** - Charts
- **FullCalendar 6.1.19** - Calendar

#### Component Structure

##### Layout Components
```
components/
├── Layout/
│   ├── Sidebar.vue        # Navigation sidebar
│   ├── Topbar.vue         # Header with notifications
│   └── PageHeader.vue     # Page header component
```

##### Common Components
```
components/
├── Common/
│   ├── LoadingSpinner.vue
│   ├── EmptyState.vue
│   ├── StatCard.vue
│   ├── StatsCard.vue
│   └── NotificationsBell.vue
```

##### Feature Components
```
views/
├── Dashboard/
│   └── Index.vue
├── Customers/
│   ├── Index.vue
│   └── Profile.vue
├── Services/
│   └── Index.vue
├── Staff/
│   └── Index.vue
├── Bookings/
│   ├── Index.vue
│   ├── BookingCalendar.vue
│   ├── BookingCategories.vue
│   ├── BookingFormPreview.vue
│   └── BookingSettings.vue
├── Queue/
│   └── Index.vue
├── Orders/
│   └── Index.vue
├── Invoices/
│   └── Index.vue
├── Payments/
│   └── Index.vue
├── Products/
│   └── Index.vue
├── Inventory/
│   └── Index.vue
├── Commissions/
│   └── Index.vue
├── Reports/
│   └── Index.vue
├── Loyalty/
│   └── Index.vue
├── Memberships/
│   └── Index.vue
├── Display/
│   ├── Queue.vue
│   └── StaffRoom.vue
└── Settings/
    └── WooCommerce.vue
```

### Routing

#### Route Configuration
```javascript
const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard/Index.vue')
  },
  {
    path: '/customers',
    name: 'Customers',
    component: () => import('@/views/Customers/Index.vue')
  },
  // ... more routes
]
```

#### Hash Mode
- استخدام Hash Mode للتوافق مع WordPress
- URLs: `#/customers`, `#/bookings`

### State Management (Pinia)

#### Stores
```javascript
stores/
├── ui.js          # UI state (theme, sidebar)
├── auth.js        # Authentication
└── [feature].js   # Feature stores
```

#### UI Store
```javascript
// Theme management
const theme = ref('light')
const setTheme = (newTheme) => { ... }

// Sidebar state
const sidebarVisible = ref(true)
const toggleSidebar = () => { ... }
```

### API Integration

#### API Client
```javascript
// assets/src/core/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: window.AsmaaSalonConfig.restUrl,
  headers: {
    'X-WP-Nonce': window.AsmaaSalonConfig.nonce
  }
})
```

#### Request/Response Interceptors
- Automatic error handling
- Loading state management
- Token refresh (مستقبلي)

### Responsive Design

#### Breakpoints
- **Mobile:** < 768px
- **Tablet:** 768px - 1024px
- **Desktop:** > 1024px

#### Mobile Optimization
- Responsive sidebar
- Touch-friendly buttons
- Optimized tables
- Mobile navigation

---

## 🔌 التكاملات

### 1. WooCommerce Integration

#### Auto-Sync Features
- **Products:** مزامنة تلقائية للمنتجات
- **Orders:** مزامنة تلقائية للطلبات
- **Customers:** مزامنة تلقائية للعملاء
- **Payments:** مزامنة تلقائية للمدفوعات

#### Sync Direction
- **WooCommerce → Asmaa Salon:** تلقائي
- **Asmaa Salon → WooCommerce:** يدوي (مستقبلي)

#### Sync Log
```sql
wp_asmaa_wc_sync_log
├── entity_type (product/order/customer/payment)
├── entity_id
├── wc_entity_id
├── sync_direction (wc_to_asmaa/asmaa_to_wc)
├── status (success/failed)
├── error_message
└── synced_at
```

### 2. WordPress Integration

#### WordPress Features Used
- **User System:** استخدام نظام المستخدمين
- **Roles & Capabilities:** الأدوار والصلاحيات
- **REST API:** WordPress REST API
- **Nonce Security:** حماية CSRF
- **Media Library:** مكتبة الوسائط
- **Custom Logo:** الشعار المخصص

### 3. Future Integrations (مستقبلي)

#### Payment Gateways
- KNET
- Credit Card Processors
- Bank Integration

#### SMS Services
- Twilio
- Local SMS Providers

#### Email Services
- SMTP Configuration
- Email Templates

#### Calendar Integration
- Google Calendar
- Outlook Calendar

---

## 📊 الإحصائيات والأرقام

### الكود
- **Controllers:** 23 REST API Controllers
- **Database Tables:** 22 جدول
- **Vue Views:** 45+ views
- **Capabilities:** 90+ صلاحية
- **Roles:** 7 أدوار مخصصة

### الميزات
- **CRUD Operations:** كاملة لجميع الوحدات
- **Search & Filter:** متقدم
- **Pagination:** على جميع القوائم
- **Reports:** 4 أنواع تقارير
- **Notifications:** نظام إشعارات متكامل

---

## 🚀 التثبيت والبناء

### التثبيت
```bash
# 1. نسخ البلاجن
cp -r asmaa-salon wp-content/plugins/

# 2. تفعيل البلاجن من wp-admin
# 3. بناء Assets
cd wp-content/plugins/asmaa-salon/assets
npm install
npm run build
```

### التطوير
```bash
# Development mode
cd assets
npm run dev
```

### الإنتاج
```bash
# Production build
cd assets
npm run build
```

---

## 📝 الخلاصة

نظام إدارة صالون أسماء الجارالله هو نظام متكامل وشامل يوفر:

✅ **إدارة كاملة** لجميع عمليات الصالون  
✅ **واجهة حديثة** باستخدام Vue.js + CoreUI  
✅ **أمان عالي** مع نظام صلاحيات متقدم  
✅ **أداء ممتاز** مع تحسينات قاعدة البيانات  
✅ **تصميم جميل** بلون ذهبي فاخر  
✅ **تكامل سلس** مع WooCommerce  
✅ **قابلية التوسع** للمستقبل  

---

**الإصدار:** 0.2.0  
**آخر تحديث:** ديسمبر 2025  
**الحالة:** ✅ جاهز للاستخدام

---

*تم إعداد هذه الوثيقة بواسطة فريق تطوير نظام إدارة صالون أسماء الجارالله*


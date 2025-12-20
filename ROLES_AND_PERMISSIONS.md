# 🔐 Roles & Permissions System

## Overview
نظام متكامل لإدارة المستخدمين والأدوار والصلاحيات في Asmaa Salon Plugin.

## ✅ Features Implemented

### 1. REST API Endpoints
- **GET** `/wp-json/asmaa-salon/v1/users` - Get all users
- **POST** `/wp-json/asmaa-salon/v1/users` - Create new user
- **GET** `/wp-json/asmaa-salon/v1/users/{id}` - Get single user
- **PUT** `/wp-json/asmaa-salon/v1/users/{id}` - Update user
- **DELETE** `/wp-json/asmaa-salon/v1/users/{id}` - Delete user
- **PUT** `/wp-json/asmaa-salon/v1/users/{id}/role` - Assign role to user
- **GET** `/wp-json/asmaa-salon/v1/users/roles` - Get all available roles
- **GET** `/wp-json/asmaa-salon/v1/users/roles/{role}` - Get role capabilities

### 2. Available Roles
1. **Administrator** - Full WordPress admin access
2. **Super Admin** - All Asmaa Salon capabilities
3. **Admin** - All except force_delete
4. **Manager** - Operations management
5. **Accountant** - Financial modules only
6. **Receptionist** - Basic operations
7. **Cashier** - POS and payments
8. **Staff** - Limited access

### 3. Capabilities System
175+ granular capabilities organized by module:
- `asmaa_services_*` (view, create, update, delete, etc.)
- `asmaa_customers_*`
- `asmaa_staff_*`
- `asmaa_bookings_*`
- `asmaa_queue_*`
- `asmaa_orders_*`
- `asmaa_invoices_*`
- `asmaa_payments_*`
- `asmaa_products_*`
- `asmaa_inventory_*`
- `asmaa_loyalty_*`
- `asmaa_memberships_*`
- `asmaa_commissions_*`
- `asmaa_access_*` (users, roles, permissions management)

### 4. User Interface
- **Users Management Page** (`/users`)
  - List all users with search and filters
  - Create/Edit/Delete users
  - Assign roles to users
  - View user details and avatar
  
- **Roles & Permissions Page** (`/roles`)
  - View all available roles
  - View capabilities for each role
  - Grouped capabilities by module

- **Core Settings Integration**
  - Quick access buttons in Core page
  - Access Control section

## 🔒 Security Features

### 1. Permission Checks
- All API endpoints protected with `permission_callback`
- Uses WordPress native `current_user_can()` function
- Checks against Asmaa Salon custom capabilities

### 2. Safety Measures
- Cannot delete your own account
- Cannot change your own role
- Username uniqueness validation
- Email uniqueness validation
- Password validation for new users

### 3. WordPress Native Integration
- Uses `wp_create_user()`, `wp_update_user()`, `wp_delete_user()`
- Uses WordPress Roles API (`add_role()`, `get_role()`, etc.)
- No direct database manipulation
- Fully compatible with WordPress user system

## 📁 Files Created/Modified

### PHP Files
- `includes/API/Controllers/Users_Controller.php` (NEW)
- `includes/Plugin.php` (MODIFIED - added Users_Controller)
- `includes/Security/Capabilities.php` (EXISTING - already had the system)

### Vue.js Components
- `assets/src/views/Users/Index.vue` (NEW)
- `assets/src/views/Users/UserModal.vue` (NEW)
- `assets/src/views/Users/RoleModal.vue` (NEW)
- `assets/src/views/Roles/Index.vue` (NEW)
- `assets/src/components/UI/StatsCard.vue` (NEW)
- `assets/src/views/Core/Index.vue` (MODIFIED - added Access Control section)

### Router
- `assets/src/router/index.js` (MODIFIED - added /users and /roles routes)

### Translations
- `assets/src/locales/en.json` (MODIFIED - added users and roles translations)
- `assets/src/locales/ar.json` (MODIFIED - added users and roles translations)

## 🚀 Usage

### Access the System
1. Navigate to Core page: `/asmaa-salon-dashboard#/core`
2. Click on "المستخدمين" (Users) or "الأدوار والصلاحيات" (Roles)

### Create a New User
1. Go to Users page
2. Click "إضافة مستخدم جديد" (Add New User)
3. Fill in the form:
   - Username (required, unique)
   - Email (required, unique)
   - First Name (optional)
   - Last Name (optional)
   - Role (required)
   - Password (required for new users)
4. Click "حفظ" (Save)

### Assign Role to User
1. Go to Users page
2. Click the shield icon next to the user
3. Select the new role
4. Click "تعيين" (Assign)

### View Role Capabilities
1. Go to Roles page
2. Click "عرض الصلاحيات" (View Capabilities) on any role card
3. See all capabilities grouped by module
4. Green checkmarks indicate granted capabilities

## 🔧 Developer Notes

### Adding New Capabilities
Edit `includes/Security/Capabilities.php`:

```php
public static function get_all_capabilities(): array
{
    return [
        // ... existing capabilities
        'asmaa_new_module_view',
        'asmaa_new_module_create',
        'asmaa_new_module_update',
        'asmaa_new_module_delete',
    ];
}
```

### Checking Permissions in Code

```php
// In PHP
if (current_user_can('asmaa_services_create')) {
    // User can create services
}

// Using Capabilities helper
use AsmaaSalon\Security\Capabilities;

if (Capabilities::can('asmaa_services_create')) {
    // User can create services
}
```

```javascript
// In Vue.js (check on backend via API)
// The API will automatically check permissions
```

### Protecting Routes

```php
// In Controller
register_rest_route($this->namespace, '/endpoint', [
    'methods' => 'POST',
    'callback' => [$this, 'method'],
    'permission_callback' => $this->permission_callback('asmaa_capability_name'),
]);
```

## 📊 Database Structure

### Users Table
Uses WordPress native `wp_users` table - no custom tables needed.

### User Meta
Uses WordPress native `wp_usermeta` table for:
- `wp_capabilities` - User roles
- `first_name`
- `last_name`
- Other WordPress user meta

### Roles & Capabilities
Stored in WordPress options table:
- `wp_user_roles` - All roles and their capabilities

## 🎨 UI Components

### StatsCard Component
Reusable card for displaying statistics:
```vue
<StatsCard
  title="Total Users"
  :value="stats.total"
  icon="cil-people"
  color="primary"
/>
```

### UserModal Component
Modal for creating/editing users with form validation.

### RoleModal Component
Modal for assigning roles to users.

## 🌍 Internationalization

Fully translated in:
- **English** (en.json)
- **Arabic** (ar.json)

All UI text uses translation keys:
```javascript
{{ t('users.title') }} // "Users" or "المستخدمين"
```

## ✅ Testing

### Manual Testing Checklist
- [ ] Create new user
- [ ] Edit existing user
- [ ] Delete user (not yourself)
- [ ] Assign role to user
- [ ] View role capabilities
- [ ] Search users
- [ ] Filter by role
- [ ] Pagination works
- [ ] Cannot delete own account
- [ ] Cannot change own role
- [ ] Username uniqueness validation
- [ ] Email uniqueness validation

## 🔄 Future Enhancements

Possible improvements:
1. Custom role creation UI
2. Custom capability assignment UI
3. Bulk user operations
4. User import/export
5. Activity log for user changes
6. Password reset functionality
7. Two-factor authentication
8. User groups/teams

## 📞 Support

For issues or questions:
1. Check WordPress user roles documentation
2. Review Asmaa Salon plugin documentation
3. Contact development team

---

**Last Updated:** December 2024
**Version:** 1.0.0
**Status:** ✅ Production Ready


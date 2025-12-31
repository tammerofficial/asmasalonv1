import { createRouter, createWebHashHistory } from 'vue-router';

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('../views/Dashboard.vue'),
  },
  {
    path: '/core',
    name: 'Core',
    component: () => import('../views/Core/Index.vue'),
  },
  {
    path: '/customers',
    name: 'Customers',
    component: () => import('../views/Customers/Index.vue'),
  },
  {
    path: '/customers/:id',
    name: 'CustomerProfile',
    component: () => import('../views/Customers/Profile.vue'),
  },
  {
    path: '/staff',
    name: 'Staff',
    component: () => import('../views/Staff/Index.vue'),
  },
  {
    path: '/services',
    name: 'Services',
    component: () => import('../views/Services/Index.vue'),
  },
  {
    path: '/bookings',
    name: 'Bookings',
    component: () => import('../views/Bookings/Index.vue'),
  },
  {
    path: '/bookings/categories',
    name: 'BookingsCategories',
    component: () => import('../views/Bookings/BookingCategories.vue'),
  },
  {
    path: '/bookings/settings',
    name: 'BookingsSettings',
    component: () => import('../views/Bookings/BookingSettings.vue'),
  },
  {
    path: '/bookings/appearance',
    name: 'BookingsAppearance',
    component: () => import('../views/Bookings/BookingAppearance.vue'),
  },
  {
    path: '/queue',
    name: 'Queue',
    component: () => import('../views/Queue/Index.vue'),
  },
  {
    path: '/worker-calls',
    name: 'WorkerCalls',
    component: () => import('../views/WorkerCalls/Index.vue'),
  },
  {
    path: '/orders',
    name: 'Orders',
    component: () => import('../views/Orders/Index.vue'),
  },
  {
    path: '/invoices',
    name: 'Invoices',
    component: () => import('../views/Invoices/Index.vue'),
  },
  {
    path: '/payments',
    name: 'Payments',
    component: () => import('../views/Payments/Index.vue'),
  },
  {
    path: '/products',
    name: 'Products',
    component: () => import('../views/Products/Index.vue'),
  },
  {
    path: '/inventory',
    redirect: { name: 'Products', query: { tab: 'inventory' } },
  },
  {
    path: '/loyalty',
    name: 'Loyalty',
    component: () => import('../views/Loyalty/Index.vue'),
  },
  {
    path: '/memberships',
    name: 'Memberships',
    component: () => import('../views/Memberships/Index.vue'),
  },
  {
    path: '/commissions',
    name: 'Commissions',
    component: () => import('../views/Commissions/Index.vue'),
  },
  {
    path: '/programs/settings',
    name: 'ProgramsSettings',
    component: () => import('../views/Programs/Settings.vue'),
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('../views/Reports/Index.vue'),
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: () => import('../views/Notifications/Index.vue'),
  },
  {
    path: '/display/queue',
    name: 'DisplayQueue',
    component: () => import('../views/Display/Queue.vue'),
  },
  {
    path: '/display/staff-room',
    name: 'DisplayStaffRoom',
    component: () => import('../views/Display/StaffRoom.vue'),
  },
  {
    path: '/display/worker-calls',
    name: 'DisplayWorkerCalls',
    component: () => import('../views/Display/WorkerCalls.vue'),
    meta: { keepAlive: true },
  },
  {
    path: '/rating',
    name: 'Rating',
    component: () => import('../views/Rating.vue'),
  },
  {
    path: '/pos',
    name: 'POS',
    component: () => import('../views/POS/Index.vue'),
  },
  {
    path: '/users',
    name: 'Users',
    component: () => import('../views/Users/Index.vue'),
  },
  {
    path: '/roles',
    name: 'Roles',
    component: () => import('../views/Roles/Index.vue'),
  },
  {
    path: '/settings/woocommerce',
    name: 'WooCommerceSettings',
    component: () => import('../views/Settings/WooCommerce.vue'),
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

// Handle navigation errors
router.onError((error) => {
  console.error('Router error:', error);
});

export default router;

<template>
  <aside ref="sidebarRef" class="asmaa-salon-sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <img
          :src="logoSrc"
          alt="Asmaa Al-Jarallah Salon"
          class="logo-image"
          @error="onLogoError"
        />
      </div>
    </div>
    <nav class="sidebar-nav">
      <!-- Dashboard -->
      <router-link to="/" class="nav-item" :class="{ 'router-link-active': $route.name === 'Dashboard' }">
        <CIcon icon="cil-speedometer" class="nav-icon" />
        <span class="nav-text">{{ t('nav.dashboard') }}</span>
      </router-link>
      
      <!-- Operations Section -->
      <div class="nav-section-divider">
        <span class="nav-section-label">{{ t('sidebar.sections.operations') || 'Operations' }}</span>
      </div>
      <router-link to="/pos" class="nav-item" :class="{ 'router-link-active': $route.name === 'POS' }">
        <CIcon icon="cil-cart" class="nav-icon" />
        <span class="nav-text">POS</span>
      </router-link>
      <router-link to="/bookings" class="nav-item" :class="{ 'router-link-active': $route.name === 'Bookings' }">
        <CIcon icon="cil-calendar" class="nav-icon" />
        <span class="nav-text">{{ t('nav.bookings') }}</span>
      </router-link>
      <router-link to="/queue" class="nav-item" :class="{ 'router-link-active': $route.name === 'Queue' }">
        <CIcon icon="cil-list" class="nav-icon" />
        <span class="nav-text">{{ t('nav.queue') }}</span>
      </router-link>
      <router-link to="/worker-calls" class="nav-item" :class="{ 'router-link-active': $route.name === 'WorkerCalls' }">
        <CIcon icon="cil-bell" class="nav-icon" />
        <span class="nav-text">{{ t('nav.workerCalls') }}</span>
      </router-link>
      
      <!-- Sales & Finance Section -->
      <div class="nav-section-divider">
        <span class="nav-section-label">{{ t('sidebar.sections.transactions') || 'Sales & Finance' }}</span>
      </div>
      <router-link to="/orders" class="nav-item" :class="{ 'router-link-active': $route.name === 'Orders' }">
        <CIcon icon="cil-cart" class="nav-icon" />
        <span class="nav-text">{{ t('nav.orders') }}</span>
      </router-link>
      <router-link to="/invoices" class="nav-item" :class="{ 'router-link-active': $route.name === 'Invoices' }">
        <CIcon icon="cil-file" class="nav-icon" />
        <span class="nav-text">{{ t('nav.invoices') }}</span>
      </router-link>
      <router-link to="/payments" class="nav-item" :class="{ 'router-link-active': $route.name === 'Payments' }">
        <CIcon icon="cil-money" class="nav-icon" />
        <span class="nav-text">{{ t('nav.payments') }}</span>
      </router-link>
      <router-link to="/commissions" class="nav-item" :class="{ 'router-link-active': $route.name === 'Commissions' }">
        <CIcon icon="cil-dollar" class="nav-icon" />
        <span class="nav-text">{{ t('nav.commissions') }}</span>
      </router-link>

      <!-- Catalog Section -->
      <div class="nav-section-divider">
        <span class="nav-section-label">{{ t('sidebar.sections.catalog') || 'Catalog' }}</span>
      </div>
      <router-link to="/services" class="nav-item" :class="{ 'router-link-active': $route.name === 'Services' }">
        <CIcon icon="cil-spreadsheet" class="nav-icon" />
        <span class="nav-text">{{ t('nav.services') }}</span>
      </router-link>
      <router-link to="/products" class="nav-item" :class="{ 'router-link-active': $route.name === 'Products' }">
        <CIcon icon="cil-basket" class="nav-icon" />
        <span class="nav-text">{{ t('nav.products') }}</span>
      </router-link>
      
      <!-- Customers Section -->
      <div class="nav-section-divider">
        <span class="nav-section-label">{{ t('sidebar.sections.people') || 'Customers & CRM' }}</span>
      </div>
      <router-link to="/customers" class="nav-item" :class="{ 'router-link-active': $route.name === 'Customers' }">
        <CIcon icon="cil-people" class="nav-icon" />
        <span class="nav-text">{{ t('nav.customers') }}</span>
      </router-link>
      <router-link to="/customers/wallet" class="nav-item" :class="{ 'router-link-active': $route.name === 'WalletMembers' }">
        <CIcon icon="cil-wallet" class="nav-icon" />
        <span class="nav-text">{{ t('nav.walletMembers') || 'Wallet Members' }}</span>
      </router-link>
      <router-link to="/loyalty" class="nav-item" :class="{ 'router-link-active': $route.name === 'Loyalty' }">
        <CIcon icon="cil-star" class="nav-icon" />
        <span class="nav-text">{{ t('nav.loyalty') }}</span>
      </router-link>
      <router-link to="/memberships" class="nav-item" :class="{ 'router-link-active': $route.name === 'Memberships' }">
        <CIcon icon="cil-credit-card" class="nav-icon" />
        <span class="nav-text">{{ t('nav.memberships') }}</span>
      </router-link>
      <router-link to="/apple-wallet/simulator" class="nav-item" :class="{ 'router-link-active': $route.name === 'AppleWalletSimulator' }">
        <CIcon icon="cil-screen-smartphone" class="nav-icon" />
        <span class="nav-text">{{ t('nav.appleWalletSimulator') || 'Apple Wallet Simulator' }}</span>
      </router-link>
      
      <!-- HR Section -->
      <div class="nav-section-divider">
        <span class="nav-section-label">{{ t('sidebar.sections.hr') || 'HR' }}</span>
      </div>
      <router-link to="/staff" class="nav-item" :class="{ 'router-link-active': $route.name === 'Staff' }">
        <CIcon icon="cil-user" class="nav-icon" />
        <span class="nav-text">{{ t('nav.staff') }}</span>
      </router-link>
      
      <!-- Analytics Section -->
      <div class="nav-section-divider">
        <span class="nav-section-label">{{ t('sidebar.sections.analytics') || 'Analytics' }}</span>
      </div>
      <router-link to="/reports" class="nav-item" :class="{ 'router-link-active': $route.name === 'Reports' }">
        <CIcon icon="cil-chart-line" class="nav-icon" />
        <span class="nav-text">{{ t('nav.reports') }}</span>
      </router-link>
      <router-link to="/notifications" class="nav-item" :class="{ 'router-link-active': $route.name === 'Notifications' }">
        <CIcon icon="cil-bell" class="nav-icon" />
        <span class="nav-text">{{ t('nav.notifications') }}</span>
      </router-link>

      <!-- Settings Section -->
      <div class="nav-section-divider">
        <span class="nav-section-label">{{ t('sidebar.sections.settings') || 'Settings' }}</span>
      </div>
      <router-link to="/core" class="nav-item" :class="{ 'router-link-active': $route.name === 'Core' }">
        <CIcon icon="cil-settings" class="nav-icon" />
        <span class="nav-text">{{ t('nav.core') }}</span>
      </router-link>
      <router-link to="/programs/settings" class="nav-item" :class="{ 'router-link-active': $route.name === 'ProgramsSettings' }">
        <CIcon icon="cil-settings" class="nav-icon" />
        <span class="nav-text">{{ t('nav.programsSettings') }}</span>
      </router-link>
    </nav>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useTranslation } from '@/composables/useTranslation';
import { useUiStore } from '@/stores/ui';

const router = useRouter();
const route = useRoute();
const { t } = useTranslation();

const sidebarRef = ref(null);
const isVisible = ref(true);

const uiStore = useUiStore();

// Brand logos
// - Light dashboard: use dark logo
// - Dark dashboard: use light logo
const LOGO_ON_LIGHT = 'https://asmaaljarallah.com/wp-content/uploads/2025/03/logoDark.png';
const LOGO_ON_DARK = 'https://asmaaljarallah.com/wp-content/uploads/2021/09/logo_light.png';

const logoSrc = computed(() => (uiStore.theme === 'dark' ? LOGO_ON_DARK : LOGO_ON_LIGHT));

const onLogoError = (e) => {
  // fallback to wp localized logo (if provided) to avoid broken image
  const fallback = window?.AsmaaSalonConfig?.logoUrl;
  if (fallback && e?.target?.src !== fallback) {
    e.target.src = fallback;
  }
};

// Expose toggle method to parent (for App.vue)
const toggleVisibility = () => {
  isVisible.value = !isVisible.value;
  if (sidebarRef.value) {
    if (isVisible.value) {
      sidebarRef.value.classList.remove('hide');
    } else {
      sidebarRef.value.classList.add('hide');
    }
  }
};

defineExpose({
  toggleVisibility
});
</script>

<style scoped>
.asmaa-salon-sidebar {
  width: 260px;
  background: var(--bg-primary);
  color: var(--text-primary);
  height: 100vh;
  box-shadow: var(--shadow-lg);
  position: fixed;
  top: 0;
  overflow-y: auto;
  z-index: 100;
  flex-shrink: 0;
  border-right: 1px solid var(--border-color);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease, border-color 0.3s ease;
}

/* LTR Layout */
[dir="ltr"] .asmaa-salon-sidebar {
  left: 0;
  border-right: 1px solid var(--border-color);
  border-left: none;
}

[dir="ltr"] .asmaa-salon-sidebar.hide {
  transform: translateX(-100%);
}

/* RTL Layout */
[dir="rtl"] .asmaa-salon-sidebar {
  right: 0;
  border-left: 1px solid var(--border-color);
  border-right: none;
}

[dir="rtl"] .asmaa-salon-sidebar.hide {
  transform: translateX(100%);
}

.sidebar-header {
  padding: var(--spacing-xl) var(--spacing-lg);
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-primary);
  display: flex;
  justify-content: center;
  align-items: center;
  transition: background-color 0.3s ease, border-color 0.3s ease;
}

.logo {
  display: flex;
  align-items: center;
  gap: 14px;
  cursor: pointer;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.logo-image {
  max-height: 70px;
  width: auto;
  display: block;
}

.logo:hover {
  transform: scale(1.02);
}

.sidebar-nav {
  padding: var(--spacing-xl) var(--spacing-md);
}

.nav-section-divider {
  margin: var(--spacing-xl) var(--spacing-base) var(--spacing-sm);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-color);
  transition: border-color 0.3s ease;
}

.nav-section-label {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family-base);
  text-transform: uppercase;
  letter-spacing: var(--letter-spacing-wide);
  color: var(--text-muted);
  display: block;
  margin-bottom: var(--spacing-sm);
  transition: color 0.3s ease;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: var(--spacing-md) var(--spacing-base);
  margin-bottom: var(--spacing-xs);
  color: var(--text-primary);
  text-decoration: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: var(--radius-lg);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  font-family: var(--font-family-base);
  line-height: var(--line-height-normal);
  position: relative;
  overflow: hidden;
  opacity: 0.8;
}

.nav-item::before {
  content: '';
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 0;
  background: var(--asmaa-primary);
  border-radius: 0 3px 3px 0;
  transition: height 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* LTR */
[dir="ltr"] .nav-item::before {
  left: 0;
  border-radius: 0 3px 3px 0;
}

[dir="ltr"] .nav-item:hover {
  transform: translateX(4px);
}

/* RTL */
[dir="rtl"] .nav-item::before {
  right: 0;
  border-radius: 3px 0 0 3px;
}

[dir="rtl"] .nav-item:hover {
  transform: translateX(-4px);
}

.nav-item:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  opacity: 1;
}

.nav-item:hover::before {
  height: 24px;
}

.nav-item.router-link-active {
  background: rgba(187, 160, 122, 0.1);
  color: var(--asmaa-primary);
  font-weight: var(--font-weight-semibold);
  opacity: 1;
  box-shadow: var(--shadow-sm);
}

.nav-item.router-link-active::before {
  height: 100%;
  background: var(--asmaa-primary);
}

.nav-icon {
  width: 22px;
  height: 22px;
  color: currentColor;
  flex-shrink: 0;
}

/* Add spacing between icon and text */
[dir="rtl"] .nav-icon {
  margin-left: 8px;
}

[dir="ltr"] .nav-icon {
  margin-right: 8px;
}

.nav-item.router-link-active .nav-icon {
  filter: drop-shadow(0 2px 6px rgba(187, 160, 122, 0.6));
}

/* Add spacing between indicator bar and icon for active items */
[dir="rtl"] .nav-item.router-link-active .nav-icon {
  margin-right: 12px;
}

[dir="ltr"] .nav-item.router-link-active .nav-icon {
  margin-left: 12px;
}

.nav-text {
  flex: 1;
  letter-spacing: 0.2px;
}

/* Scrollbar */
.asmaa-salon-sidebar::-webkit-scrollbar {
  width: 6px;
}

.asmaa-salon-sidebar::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
}

.asmaa-salon-sidebar::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
  transition: background 0.2s;
}

.asmaa-salon-sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

/* Responsive */
@media (max-width: 1024px) {
  [dir="ltr"] .asmaa-salon-sidebar {
    transform: translateX(-100%);
  }
  
  [dir="ltr"] .asmaa-salon-sidebar.show {
    transform: translateX(0);
  }
  
  [dir="rtl"] .asmaa-salon-sidebar {
    transform: translateX(100%);
  }
  
  [dir="rtl"] .asmaa-salon-sidebar.show {
    transform: translateX(0);
  }
}
</style>

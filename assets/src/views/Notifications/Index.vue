<template>
  <div class="notifications-page">
    <!-- Page Header -->
    <PageHeader :title="t('notifications.title')" :subtitle="t('notifications.subtitle')">
      <template #icon>
        <CIcon icon="cil-bell" />
      </template>

      <template #actions>
        <CButton color="secondary" variant="outline" @click="refresh" class="me-2" :disabled="loading">
          <CIcon icon="cil-reload" class="me-2" :class="{ 'spinning': loading }" />
          {{ t('common.refresh') }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="markAllRead" :disabled="unreadCount === 0 || loading">
          <CIcon icon="cil-check-circle" class="me-2" />
          {{ t('notifications.markAllRead') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('notifications.all')"
        :value="items.length"
        badge-variant="info"
        color="blue"
        :clickable="true"
        @click="() => { readFilter = 'all'; currentPage = 1; }"
      >
        <template #icon>
          <CIcon icon="cil-bell" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('notifications.unread')"
        :value="unreadCount"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="() => { readFilter = 'unread'; currentPage = 1; }"
      >
        <template #icon>
          <CIcon icon="cil-bell-exclamation" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('notifications.read')"
        :value="items.length - unreadCount"
        badge-variant="success"
        color="green"
        :clickable="true"
        @click="() => { readFilter = 'read'; currentPage = 1; }"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('notifications.types.system')"
        :value="getTypeCount('system')"
        badge-variant="secondary"
        color="purple"
        :clickable="true"
        @click="() => { typeFilter = 'system'; currentPage = 1; }"
      >
        <template #icon>
          <CIcon icon="cil-settings" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('notifications.lowStock')"
        :value="getTypeCount('low_stock')"
        badge-variant="warning"
        color="orange"
        :clickable="true"
        @click="() => { typeFilter = 'low_stock'; currentPage = 1; }"
      >
        <template #icon>
          <CIcon icon="cil-warning" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('memberships.title')"
        :value="getTypeCount('membership_expiry')"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="() => { typeFilter = 'membership_expiry'; currentPage = 1; }"
      >
        <template #icon>
          <CIcon icon="cil-credit-card" />
        </template>
      </StatCard>
    </div>

    <!-- Filters -->
    <Card :title="t('common.filter')" icon="cil-filter" class="filters-card">
      <CRow class="g-3">
        <CCol :md="4">
          <CInputGroup class="search-input-group">
            <CInputGroupText class="search-icon-wrapper">
              <CIcon icon="cil-magnifying-glass" />
            </CInputGroupText>
            <CFormInput
              v-model="searchQuery"
              :placeholder="t('common.search') + '...'"
              class="filter-input search-input"
            />
          </CInputGroup>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="readFilter" class="filter-select">
            <option value="all">{{ t('notifications.filter.all') }}</option>
            <option value="unread">{{ t('notifications.filter.unread') }}</option>
            <option value="read">{{ t('notifications.filter.read') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="typeFilter" class="filter-select">
            <option value="all">{{ t('notifications.types.all') }}</option>
            <option value="booking">{{ t('notifications.types.booking') }}</option>
            <option value="order">{{ t('notifications.types.order') }}</option>
            <option value="payment">{{ t('notifications.types.payment') }}</option>
            <option value="invoice">{{ t('notifications.types.invoice') }}</option>
            <option value="system">{{ t('notifications.types.system') }}</option>
            <option value="low_stock">{{ t('notifications.types.low_stock') }}</option>
            <option value="membership_expiry">{{ t('notifications.types.membership_expiry') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="2">
          <CButton color="secondary" variant="outline" @click="resetFilters" class="w-100 reset-btn">
            <CIcon icon="cil-reload" class="me-1" />
            {{ t('common.reset') }}
          </CButton>
        </CCol>
      </CRow>
    </Card>

    <!-- Notifications List -->
    <Card :title="t('notifications.all')" icon="cil-list">
      <template #actions>
        <div class="card-actions">
          <span class="result-count">
            {{ filteredItems.length }} {{ t('common.results') || 'results' }}
          </span>
        </div>
      </template>

      <!-- Loading -->
      <LoadingSpinner v-if="loading && items.length === 0" :text="t('common.loading')" />

      <!-- Empty State -->
      <EmptyState 
        v-else-if="filteredItems.length === 0"
        :title="searchQuery || readFilter !== 'all' || typeFilter !== 'all' ? t('common.noData') : t('notifications.empty')"
        :description="t('notifications.noNotifications')"
        icon-color="gray"
      >
        <template #icon>
          <CIcon icon="cil-bell" size="3xl" />
        </template>
        <template #action>
          <CButton v-if="searchQuery || readFilter !== 'all' || typeFilter !== 'all'" color="secondary" variant="outline" @click="resetFilters">
            {{ t('common.reset') }}
          </CButton>
        </template>
      </EmptyState>

      <!-- Notifications List -->
      <div v-else class="notifications-list">
        <div
          v-for="n in paginatedItems"
          :key="n.id"
          class="notification-item"
          :class="{ unread: !n.read_at }"
          @click="openNotification(n)"
        >
          <div class="notification-icon" :class="`notification-icon-${getTypeColor(n)}`">
            <CIcon :icon="getNotificationIcon(n)" />
          </div>
          <div class="notification-content">
            <div class="notification-header">
              <div class="notification-title">{{ pickText(n, 'title') }}</div>
              <div class="notification-time">{{ formatTime(n.created_at) }}</div>
            </div>
            <div class="notification-message">{{ pickText(n, 'message') }}</div>
            <div class="notification-meta">
              <CBadge v-if="!n.read_at" color="primary" class="unread-badge">
                {{ t('notifications.unread') }}
              </CBadge>
              <CBadge :color="getTypeColor(n)" class="type-badge">
                {{ getTypeLabel(n) }}
              </CBadge>
            </div>
          </div>
          <div class="notification-actions" @click.stop>
            <CButton
              v-if="!n.read_at"
              color="secondary"
              variant="ghost"
              size="sm"
              @click="markRead(n.id)"
              :title="t('notifications.markRead')"
            >
              <CIcon icon="cil-check" />
            </CButton>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <template #footer v-if="filteredItems.length > perPage">
        <div class="pagination-footer">
          <div class="pagination-info">
            {{ t('common.showing') || 'Showing' }} 
            {{ (currentPage - 1) * perPage + 1 }} - 
            {{ Math.min(currentPage * perPage, filteredItems.length) }} 
            {{ t('common.of') || 'of' }} 
            {{ filteredItems.length }}
          </div>
          <div class="pagination">
            <CButton
              color="secondary"
              variant="outline"
              size="sm"
              @click="currentPage--"
              :disabled="currentPage === 1"
            >
              <CIcon icon="cil-chevron-left" />
              {{ t('common.previous') }}
            </CButton>
            <span class="page-info">
              {{ t('common.page') }} {{ currentPage }} / {{ totalPages }}
            </span>
            <CButton
              color="secondary"
              variant="outline"
              size="sm"
              @click="currentPage++"
              :disabled="currentPage === totalPages"
            >
              {{ t('common.next') }}
              <CIcon icon="cil-chevron-right" />
            </CButton>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { CButton, CBadge, CRow, CCol, CFormInput, CFormSelect, CInputGroup, CInputGroupText } from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useRouter } from 'vue-router';
import { useTranslation } from '@/composables/useTranslation';
import { useNotificationsStore } from '@/stores/notifications';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import EmptyState from '@/components/UI/EmptyState.vue';

const router = useRouter();
const { t, currentLocale } = useTranslation();
const store = useNotificationsStore();

// State
const searchQuery = ref('');
const readFilter = ref('all');
const typeFilter = ref('all');
const currentPage = ref(1);
const perPage = ref(20);

// Computed
const items = computed(() => store.items);
const unreadCount = computed(() => store.unreadCount);
const loading = computed(() => store.loading);

// Filtered items
const filteredItems = computed(() => {
  let result = [...items.value];

  // Search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter((n) => {
      const title = pickText(n, 'title').toLowerCase();
      const message = pickText(n, 'message').toLowerCase();
      return title.includes(query) || message.includes(query);
    });
  }

  // Read/Unread filter
  if (readFilter.value === 'unread') {
    result = result.filter((n) => !n.read_at);
  } else if (readFilter.value === 'read') {
    result = result.filter((n) => n.read_at);
  }

  // Type filter
  if (typeFilter.value !== 'all') {
    result = result.filter((n) => {
      try {
        const data = n?.data ? JSON.parse(n.data) : {};
        return data.type === typeFilter.value || n.type === typeFilter.value;
      } catch {
        return false;
      }
    });
  }

  return result;
});

// Pagination
const totalPages = computed(() => Math.ceil(filteredItems.value.length / perPage.value));
const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return filteredItems.value.slice(start, end);
});

// Watch filters to reset page
watch([searchQuery, readFilter, typeFilter], () => {
  currentPage.value = 1;
});

// Methods
const refresh = async () => {
  await store.fetch({ perPage: 100, noCache: true });
};

const markAllRead = async () => {
  await store.markAllRead();
};

const markRead = async (id) => {
  await store.markRead(id);
};

const openNotification = async (n) => {
  if (!n.read_at) {
    await store.markRead(n.id);
  }

  try {
    const data = n?.data ? JSON.parse(n.data) : {};
    const route = data?.action?.route;
    const query = data?.action?.query || null;
    if (route) {
      router.push({ path: route, query: query || undefined });
    }
  } catch {
    // ignore
  }
};

const pickText = (n, field) => {
  try {
    const data = n?.data ? JSON.parse(n.data) : {};
    const lang = currentLocale.value || 'ar';
    const v = lang === 'ar' ? data?.[`${field}_ar`] : data?.[`${field}_en`];
    return v || data?.[`${field}_ar`] || data?.[`${field}_en`] || '—';
  } catch {
    return '—';
  }
};

const formatTime = (dt) => {
  if (!dt) return '';
  try {
    const date = new Date(dt.replace(' ', 'T'));
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return t('common.justNow');
    if (diffMins < 60) return `${diffMins} ${t('common.minutesAgo')}`;
    if (diffHours < 24) return `${diffHours} ${t('common.hoursAgo')}`;
    if (diffDays < 7) return `${diffDays} ${t('common.daysAgo')}`;

    return date.toLocaleString(currentLocale.value === 'ar' ? 'ar-KW' : 'en-US', {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return String(dt);
  }
};

const getNotificationIcon = (n) => {
  try {
    const data = n?.data ? JSON.parse(n.data) : {};
    const type = data.type || n.type || '';
    const iconMap = {
      booking: 'cil-calendar',
      order: 'cil-cart',
      payment: 'cil-money',
      invoice: 'cil-file',
      system: 'cil-bell',
      low_stock: 'cil-warning',
      membership_expiry: 'cil-credit-card',
    };
    return iconMap[type] || 'cil-bell';
  } catch {
    return 'cil-bell';
  }
};

const getTypeLabel = (n) => {
  try {
    const data = n?.data ? JSON.parse(n.data) : {};
    const type = data.type || n.type || 'system';
    return t(`notifications.types.${type}`) || type;
  } catch {
    return t('notifications.types.system');
  }
};

const getTypeColor = (n) => {
  try {
    const data = n?.data ? JSON.parse(n.data) : {};
    const type = data.type || n.type || 'system';
    const colorMap = {
      booking: 'info',
      order: 'warning',
      payment: 'success',
      invoice: 'primary',
      system: 'secondary',
      low_stock: 'danger',
      membership_expiry: 'warning',
    };
    return colorMap[type] || 'secondary';
  } catch {
    return 'secondary';
  }
};

const getTypeCount = (type) => {
  return items.value.filter((n) => {
    try {
      const data = n?.data ? JSON.parse(n.data) : {};
      return data.type === type || n.type === type;
    } catch {
      return false;
    }
  }).length;
};

const resetFilters = () => {
  searchQuery.value = '';
  readFilter.value = 'all';
  typeFilter.value = 'all';
  currentPage.value = 1;
};

onMounted(() => {
  store.fetch({ perPage: 100, noCache: true });
  store.startPolling(20000);
});
</script>

<style scoped>
.notifications-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

/* Filters Card */
.filters-card {
  margin-bottom: 0;
}

.search-input-group {
  height: 38px;
}

.search-icon-wrapper {
  background: var(--bg-secondary);
  border-color: var(--border-color);
  color: var(--text-muted);
}

.filter-input,
.filter-select {
  border-color: var(--border-color);
  background: var(--bg-primary);
  color: var(--text-primary);
  transition: all 0.2s;
}

.filter-input:focus,
.filter-select:focus {
  border-color: var(--asmaa-primary);
  box-shadow: 0 0 0 0.2rem rgba(187, 160, 122, 0.25);
  background: var(--bg-primary);
}

.search-input {
  padding-left: 0;
}

.reset-btn {
  border-color: var(--border-color);
  color: var(--text-primary);
}

.reset-btn:hover {
  background: var(--bg-secondary);
  border-color: var(--asmaa-primary);
  color: var(--asmaa-primary);
}

/* Card Actions */
.card-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.result-count {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-secondary);
}

/* Notifications List */
.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.notification-item {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  background: var(--bg-primary);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification-item:hover {
  background: var(--bg-secondary);
  border-color: var(--asmaa-primary);
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(187, 160, 122, 0.15);
}

.notification-item.unread {
  background: rgba(187, 160, 122, 0.06);
  border-color: rgba(187, 160, 122, 0.3);
  border-left-width: 3px;
}

[dir="rtl"] .notification-item.unread {
  border-left-width: 1px;
  border-right-width: 3px;
}

.notification-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1.25rem;
}

.notification-icon-info {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.notification-icon-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.notification-icon-success {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.notification-icon-primary {
  background: rgba(187, 160, 122, 0.1);
  color: var(--asmaa-primary);
}

.notification-icon-secondary {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.notification-icon-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.notification-icon-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.notification-title {
  font-weight: 700;
  color: var(--text-primary);
  font-size: 0.9375rem;
  flex: 1;
  line-height: 1.4;
}

.notification-time {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 600;
  white-space: nowrap;
}

.notification-message {
  color: var(--text-secondary);
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.5;
  margin-bottom: 0.75rem;
}

.notification-meta {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.unread-badge,
.type-badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.625rem;
  font-weight: 700;
  border-radius: 6px;
}

.notification-actions {
  flex-shrink: 0;
  display: flex;
  align-items: flex-start;
}

/* Pagination Footer */
.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
  gap: 1rem;
  flex-wrap: wrap;
}

.pagination-info {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-secondary);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.75rem;
}

.page-info {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-secondary);
  padding: 0 0.5rem;
}

/* Button Styles */
.btn-primary-custom {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  border: none;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
  transition: all 0.3s;
}

.btn-primary-custom:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
  box-shadow: 0 6px 16px rgba(187, 160, 122, 0.4);
  transform: translateY(-2px);
}

/* Spinning animation */
.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .notification-item {
    flex-direction: column;
  }

  .notification-actions {
    align-self: flex-end;
  }

  .pagination-footer {
    flex-direction: column;
  }

  .pagination {
    width: 100%;
  }
}
</style>

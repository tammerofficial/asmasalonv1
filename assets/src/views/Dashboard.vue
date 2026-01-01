<template>
  <div class="asmaa-salon-dashboard">
    <!-- Page Header -->
    <PageHeader 
      :title="t('dashboard.title')"
      :subtitle="t('dashboard.subtitle')"
    >
      <template #icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2"/>
          <path d="M3 9h18"/>
          <path d="M9 21V9"/>
        </svg>
      </template>
      
      <template #actions>
        <CButton color="secondary" variant="outline" @click="exportData" class="action-btn">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="primary" @click="refreshData" class="action-btn refresh-btn">
          <CIcon icon="cil-reload" class="me-2" />
          {{ t('common.refresh') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <StatCard 
        :label="t('dashboard.totalCustomers')"
        :value="stats.customers || 0"
        :badge="t('common.active') + ' ' + t('nav.customers')"
        badge-variant="info"
        color="blue"
        :clickable="true"
        @click="$router.push('/customers')"
      >
        <template #icon>
          <CIcon icon="cil-people" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('dashboard.todaysBookings')"
        :value="stats.bookingsToday || 0"
        badge="85%"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="$router.push('/bookings')"
      >
        <template #icon>
          <CIcon icon="cil-calendar" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('dashboard.monthlyRevenue')"
        :value="formatCurrencyShort(stats.monthlyRevenue || 0)"
        :badge="t('dashboard.monthlyRevenue')"
        badge-variant="info"
        color="yellow"
      >
        <template #icon>
          <CIcon icon="cil-money" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('dashboard.activeStaff')"
        :value="stats.activeStaff || 0"
        :badge="t('common.active')"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="$router.push('/staff')"
      >
        <template #icon>
          <CIcon icon="cil-user" />
        </template>
      </StatCard>

      <StatCard 
        :label="`${t('common.total') || 'Total'} ${t('nav.services')}`"
        :value="stats.totalServices || 0"
        :badge="t('common.active')"
        badge-variant="info"
        color="pink"
        :clickable="true"
        @click="$router.push('/services')"
      >
        <template #icon>
          <CIcon icon="cil-spreadsheet" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('orders.pending') + ' ' + t('nav.orders')"
        :value="stats.pendingOrders || 0"
        :badge="stats.pendingOrders || 0 + ' ' + t('orders.pending')"
        badge-variant="warning"
        color="teal"
        :clickable="true"
        @click="$router.push('/orders')"
      >
        <template #icon>
          <CIcon icon="cil-cart" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('invoices.unpaid') + ' ' + t('nav.invoices')"
        :value="stats.unpaidInvoices || 0"
        :badge="t('common.status')"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="$router.push('/invoices')"
      >
        <template #icon>
          <CIcon icon="cil-file" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('queue.waiting')"
        :value="stats.queueWaiting || 0"
        :badge="t('dashboard.currentWaiting')"
        badge-variant="info"
        color="orange"
        :clickable="true"
        @click="$router.push('/queue')"
      >
        <template #icon>
          <CIcon icon="cil-list" />
        </template>
      </StatCard>

      <!-- WooCommerce Sync Status Card -->
      <StatCard 
        v-if="stats.woocommerce?.active"
        :label="t('settings.woocommerce.title') || 'WooCommerce'"
        :value="stats.woocommerce?.enabled ? t('common.active') : t('common.inactive')"
        :badge="stats.woocommerce?.synced_orders + ' ' + t('nav.orders')"
        :badge-variant="stats.woocommerce?.enabled ? 'info' : 'secondary'"
        color="gold"
        :clickable="true"
        @click="$router.push('/settings/woocommerce')"
      >
        <template #icon>
          <CIcon icon="cil-cart" />
        </template>
      </StatCard>
    </div>

    <!-- Main Content Cards -->
    <!-- Charts -->
    <Card
      :title="t('dashboard.chartsTitle') || 'Analytics'"
      :subtitle="t('dashboard.chartsSubtitle') || 'Sales & activity insights'"
      icon="cil-chart-line"
      class="charts-card"
    >
      <LoadingSpinner v-if="loadingCharts" :text="t('common.loading')" />

      <div v-else class="charts-grid">
        <div class="chart-panel">
          <div class="chart-title">{{ t('dashboard.salesLast7Days') || 'Sales (Last 7 Days)' }}</div>
          <div class="chart-canvas">
            <Line :data="salesLineData" :options="salesLineOptions" :key="chartsKey" />
          </div>
        </div>

        <div class="chart-panel">
          <div class="chart-title">{{ t('dashboard.bookingsStatus') || 'Bookings Status (Last 30 Days)' }}</div>
          <div class="chart-canvas chart-canvas-sm">
            <Doughnut :data="bookingsDoughnutData" :options="doughnutOptions" :key="chartsKey + '-b'" />
          </div>
        </div>

        <div class="chart-panel">
          <div class="chart-title">{{ t('dashboard.invoicesStatus') || 'Invoices Status' }}</div>
          <div class="chart-canvas chart-canvas-sm">
            <Doughnut :data="invoicesDoughnutData" :options="doughnutOptions" :key="chartsKey + '-i'" />
          </div>
        </div>
      </div>
    </Card>

    <div class="content-grid">
      <!-- Recent Bookings -->
      <Card 
        :title="t('dashboard.recentBookings')"
        :subtitle="t('dashboard.recentActivity')"
        icon="cil-calendar"
      >
        <template #actions>
          <CButton color="secondary" variant="ghost" size="sm" @click="$router.push('/bookings')">
            {{ t('dashboard.viewAll') }}
            <CIcon icon="cil-arrow-right" class="ms-1" />
          </CButton>
        </template>

        <LoadingSpinner v-if="loadingBookings" :text="t('common.loading')" />
        
        <EmptyState 
          v-else-if="!recentBookings.length"
          :title="t('dashboard.noBookings')"
          :description="t('dashboard.noBookingsFound')"
          icon-color="gray"
        >
          <template #action>
            <CButton color="primary" @click="$router.push('/bookings?action=create')">
              {{ `${t('common.add')} ${t('nav.bookings')}` }}
            </CButton>
          </template>
        </EmptyState>

        <div v-else class="bookings-list-modern">
          <div 
            v-for="booking in recentBookings.slice(0, 5)" 
            :key="booking.id"
            class="booking-item-modern"
            @click="$router.push(`/bookings/${booking.id}`)"
          >
            <div class="booking-number">#{{ booking.id }}</div>
            <div class="booking-details-modern">
              <div class="booking-customer-name">{{ booking.customer_name || 'Customer #' + booking.customer_id }}</div>
              <div class="booking-meta-modern">
                <CBadge :color="getStatusVariant(booking.status)" class="status-badge">
                  {{ formatStatus(booking.status) }}
                </CBadge>
                <span class="booking-time-modern">
                  <CIcon icon="cil-clock" class="me-1" />
                  {{ formatTime(booking.booking_time) }}
                </span>
              </div>
            </div>
            <CIcon icon="cil-chevron-right" class="booking-arrow" />
          </div>
        </div>

        <template #footer>
          <CButton color="secondary" variant="ghost" @click="$router.push('/bookings')" class="w-100">
            {{ t('dashboard.viewAllOrders') }}
            <CIcon icon="cil-arrow-right" class="ms-2" />
          </CButton>
        </template>
      </Card>

      <!-- Queue Status -->
      <Card 
        :title="t('dashboard.queueStatus')"
        :subtitle="t('dashboard.currentWaiting')"
        icon="cil-list"
      >
        <template #actions>
          <CButton color="secondary" variant="ghost" size="sm" @click="$router.push('/queue')">
            {{ t('dashboard.viewAll') }}
            <CIcon icon="cil-arrow-right" class="ms-1" />
          </CButton>
        </template>

        <LoadingSpinner v-if="loadingQueue" :text="t('common.loading')" />
        
        <EmptyState 
          v-else-if="!queueItems.length"
          :title="t('queue.noWaiting')"
          description=""
          icon-color="gold"
        >
          <template #icon>
            <CIcon icon="cil-check-circle" size="3xl" />
          </template>
        </EmptyState>

        <div v-else class="queue-list-modern">
          <div 
            v-for="(item, index) in queueItems.slice(0, 5)" 
            :key="item.id" 
            class="queue-item-modern"
            :class="{ 'queue-item-next': index === 0 }"
          >
            <div class="queue-position" :class="{ 'queue-position-next': index === 0 }">
              {{ index + 1 }}
            </div>
            <div class="queue-info-modern">
              <div class="queue-customer-name">{{ item.customer_name }}</div>
              <div class="queue-service-modern">
                <CIcon icon="cil-spreadsheet" class="me-1" />
                {{ item.service_name }}
              </div>
            </div>
            <CBadge :color="index === 0 ? 'warning' : 'secondary'" class="queue-status-badge">
              {{ index === 0 ? t('queue.next') : t('queue.waiting') }}
            </CBadge>
          </div>
        </div>
      </Card>
    </div>

    <!-- Quick Actions -->
    <Card :title="t('dashboard.quickActions')" icon="cil-bolt">
      <div class="quick-actions-grid">
        <button class="quick-action-btn" @click="$router.push('/customers?action=create')">
          <div class="quick-action-icon blue">
            <CIcon icon="cil-plus" />
          </div>
          <div class="quick-action-content">
            <div class="quick-action-title">{{ t('common.add') }} {{ t('nav.customers') }}</div>
            <div class="quick-action-desc">{{ t('customers.addNew') }}</div>
          </div>
          <CIcon icon="cil-arrow-right" class="quick-action-arrow" />
        </button>

        <button class="quick-action-btn" @click="$router.push('/bookings?action=create')">
          <div class="quick-action-icon gold">
            <CIcon icon="cil-calendar" />
          </div>
          <div class="quick-action-content">
            <div class="quick-action-title">{{ t('common.add') }} {{ t('nav.bookings') }}</div>
            <div class="quick-action-desc">{{ t('bookings.addNew') }}</div>
          </div>
          <CIcon icon="cil-arrow-right" class="quick-action-arrow" />
        </button>

        <button class="quick-action-btn" @click="$router.push('/services?action=create')">
          <div class="quick-action-icon brown">
            <CIcon icon="cil-spreadsheet" />
          </div>
          <div class="quick-action-content">
            <div class="quick-action-title">{{ t('common.add') }} {{ t('nav.services') }}</div>
            <div class="quick-action-desc">{{ t('services.addNew') }}</div>
          </div>
          <CIcon icon="cil-arrow-right" class="quick-action-arrow" />
        </button>

        <button class="quick-action-btn" @click="$router.push('/staff?action=create')">
          <div class="quick-action-icon pink">
            <CIcon icon="cil-user" />
          </div>
          <div class="quick-action-content">
            <div class="quick-action-title">{{ t('common.add') }} {{ t('nav.staff') }}</div>
            <div class="quick-action-desc">{{ t('staff.addNew') }}</div>
          </div>
          <CIcon icon="cil-arrow-right" class="quick-action-arrow" />
        </button>
      </div>
    </Card>

    <!-- FAQ/Help Section (Rule #7) -->
    <HelpSection page-key="dashboard" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useTranslation } from '@/composables/useTranslation';
import {
  CButton, CBadge
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { Line, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import HelpSection from '@/components/Common/HelpSection.vue';
import api, { clearCache } from '@/utils/api';
import { useToast } from '@/composables/useToast';
import { useUiStore } from '@/stores/ui';
import { useBackgroundPrefetch } from '@/composables/useBackgroundPrefetch';

const router = useRouter();
const { t } = useTranslation();
const toast = useToast();
const { prefetchAllWithDelay } = useBackgroundPrefetch();
const loading = ref(false);
const loadingBookings = ref(false);
const loadingQueue = ref(false);
const loadingCharts = ref(false);

const stats = ref({
  customers: 0,
  bookingsToday: 0,
  monthlyRevenue: 0,
  activeStaff: 0,
  totalServices: 0,
  pendingOrders: 0,
  unpaidInvoices: 0,
  queueWaiting: 0,
});

const recentBookings = ref([]);
const queueItems = ref([]);
const charts = ref({
  sales_last_7_days: { labels: [], orders: [], revenue: [] },
  bookings_status_last_30_days: { pending: 0, confirmed: 0, completed: 0, cancelled: 0 },
  invoices_status: {},
});
const chartsKey = ref('charts-0');

const formatCurrencyShort = (amount) => {
  if (!amount && amount !== 0) return '0 KWD';
  const value = parseFloat(amount);
  if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K KWD`;
  }
  return `${value.toFixed(0)} KWD`;
};

const formatStatus = (status) => {
  const statusMap = {
    pending: t('bookings.pending'),
    confirmed: t('bookings.confirmed'),
    completed: t('bookings.completed'),
    cancelled: t('bookings.cancelled'),
  };
  return statusMap[status] || status;
};

const getStatusVariant = (status) => {
  const variantMap = {
    pending: 'warning',
    confirmed: 'info',
    completed: 'success',
    cancelled: 'danger',
  };
  return variantMap[status] || 'info';
};

const formatTime = (time) => {
  if (!time) return '-';
  // Accept both datetime and plain HH:mm / HH:mm:ss strings
  const s = String(time);
  try {
    if (s.includes('T') || s.includes('-')) {
      return new Date(s).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }
    const normalized = s.length === 5 ? `${s}:00` : s; // HH:mm -> HH:mm:ss
    return new Date(`1970-01-01T${normalized}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  } catch {
    return s;
  }
};

const exportData = () => {
  const payload = {
    exported_at: new Date().toISOString(),
    stats: stats.value,
    recent_bookings: recentBookings.value,
    queue: queueItems.value,
  };

  const json = JSON.stringify(payload, null, 2);
  const blob = new Blob([json], { type: 'application/json;charset=utf-8' });
  const url = URL.createObjectURL(blob);

  const a = document.createElement('a');
  a.href = url;
  a.download = `asmaa-salon-dashboard-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')}.json`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);

  toast.success('Exported dashboard snapshot');
};

const refreshData = async () => {
  loading.value = true;
  try {
    clearCache();
    await loadDashboardData();
    await loadRecentBookings();
    await loadQueue();
    toast.success('Dashboard refreshed');
  } catch (error) {
    console.error('Error refreshing data:', error);
    toast.error('Failed to refresh dashboard');
  } finally {
    loading.value = false;
  }
};

const loadDashboardData = async () => {
  loadingCharts.value = true;
  try {
    const response = await api.get('/reports/dashboard', { noCache: true });
    const data = response.data?.data || response.data || {};
    stats.value = { ...stats.value, ...(data || {}) };

    if (data?.charts) {
      charts.value = {
        sales_last_7_days: data.charts.sales_last_7_days || charts.value.sales_last_7_days,
        bookings_status_last_30_days: data.charts.bookings_status_last_30_days || charts.value.bookings_status_last_30_days,
        invoices_status: data.charts.invoices_status || charts.value.invoices_status,
      };
      bumpChartsKey();
    }
  } catch (error) {
    console.error('Error loading dashboard:', error);
  } finally {
    loadingCharts.value = false;
  }
};

const cssVar = (name, fallback = '') => {
  try {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name);
    return (v || '').trim() || fallback;
  } catch {
    return fallback;
  }
};

const bumpChartsKey = () => {
  chartsKey.value = `charts-${Date.now()}`;
};

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Tooltip, Legend, Filler);

const salesLineData = computed(() => {
  const primary = cssVar('--asmaa-primary', '#BBA07A');
  const soft = cssVar('--asmaa-primary-soft', 'rgba(187,160,122,0.14)');
  const labels = charts.value.sales_last_7_days?.labels || [];
  const revenue = charts.value.sales_last_7_days?.revenue || [];
  const orders = charts.value.sales_last_7_days?.orders || [];
  return {
    labels,
    datasets: [
      {
        label: t('dashboard.revenue') || 'Revenue (KWD)',
        data: revenue,
        borderColor: primary,
        backgroundColor: soft,
        fill: true,
        tension: 0.35,
        pointRadius: 3,
        pointHoverRadius: 5,
      },
      {
        label: t('dashboard.orders') || 'Orders',
        data: orders,
        borderColor: cssVar('--asmaa-secondary', '#475569'),
        backgroundColor: cssVar('--asmaa-secondary-soft', 'rgba(71,85,105,0.14)'),
        fill: false,
        tension: 0.35,
        pointRadius: 3,
        pointHoverRadius: 5,
      },
    ],
  };
});

const baseAxisColor = () => cssVar('--border-color', '#e5e7eb');
const baseTextColor = () => cssVar('--text-secondary', '#64748b');
const baseTitleColor = () => cssVar('--text-primary', '#111827');

const salesLineOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { color: baseTextColor(), usePointStyle: true, boxWidth: 8 },
    },
    tooltip: {
      backgroundColor: cssVar('--bg-primary', '#fff'),
      titleColor: baseTitleColor(),
      bodyColor: baseTitleColor(),
      borderColor: baseAxisColor(),
      borderWidth: 1,
    },
  },
  scales: {
    x: {
      ticks: { color: baseTextColor() },
      grid: { color: baseAxisColor() },
    },
    y: {
      ticks: { color: baseTextColor() },
      grid: { color: baseAxisColor() },
    },
  },
}));

const doughnutOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { color: baseTextColor(), usePointStyle: true, boxWidth: 8 },
    },
    tooltip: {
      backgroundColor: cssVar('--bg-primary', '#fff'),
      titleColor: baseTitleColor(),
      bodyColor: baseTitleColor(),
      borderColor: baseAxisColor(),
      borderWidth: 1,
    },
  },
}));

const bookingsDoughnutData = computed(() => {
  const s = charts.value.bookings_status_last_30_days || {};
  return {
    labels: [
      t('bookings.pending') || 'Pending',
      t('bookings.confirmed') || 'Confirmed',
      t('bookings.completed') || 'Completed',
      t('bookings.cancelled') || 'Cancelled',
    ],
    datasets: [
      {
        data: [s.pending || 0, s.confirmed || 0, s.completed || 0, s.cancelled || 0],
        backgroundColor: [
          cssVar('--asmaa-warning', '#f59e0b'),
          cssVar('--asmaa-primary', '#BBA07A'),
          cssVar('--asmaa-primary-700', '#A68B5B'),
          cssVar('--asmaa-danger', '#ef4444'),
        ],
        borderColor: cssVar('--bg-primary', '#fff'),
        borderWidth: 2,
      },
    ],
  };
});

const invoicesDoughnutData = computed(() => {
  const map = charts.value.invoices_status || {};
  const labels = Object.keys(map).filter(Boolean);
  const values = labels.map(k => Number(map[k] || 0));
  const palette = [
    cssVar('--asmaa-primary', '#BBA07A'),
    cssVar('--asmaa-primary-700', '#A68B5B'),
    cssVar('--asmaa-warning', '#f59e0b'),
    cssVar('--asmaa-danger', '#ef4444'),
    cssVar('--asmaa-secondary', '#475569'),
  ];
  return {
    labels: labels.length ? labels : [t('common.noData') || 'No data'],
    datasets: [
      {
        data: labels.length ? values : [1],
        backgroundColor: labels.length ? labels.map((_, i) => palette[i % palette.length]) : [cssVar('--asmaa-secondary-soft', 'rgba(71,85,105,0.14)')],
        borderColor: cssVar('--bg-primary', '#fff'),
        borderWidth: 2,
      },
    ],
  };
});

const loadRecentBookings = async () => {
  loadingBookings.value = true;
  try {
    const response = await api.get('/bookings', { params: { per_page: 5, orderby: 'date', order: 'desc' } });
    const data = response.data?.data || response.data || {};
    recentBookings.value = data.items || [];
  } catch (error) {
    console.error('Error loading recent bookings:', error);
  } finally {
    loadingBookings.value = false;
  }
};

const loadQueue = async () => {
  loadingQueue.value = true;
  try {
    const response = await api.get('/queue');
    const data = response.data?.data || response.data || {};
    queueItems.value = data.items || [];
  } catch (error) {
    console.error('Error loading queue:', error);
  } finally {
    loadingQueue.value = false;
  }
};

onMounted(async () => {
  // #region agent log
  fetch('http://127.0.0.1:7244/ingest/a5ae396e-8687-4d24-bbbc-03f8cb9ce0e5',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'Dashboard.vue:693',message:'Dashboard mounting',data:{hasPrefetch: !!prefetchAllWithDelay},timestamp:Date.now(),sessionId:'debug-session',hypothesisId:'rule_5_check'})}).catch(()=>{});
  // #endregion
  // Load essential dashboard data immediately
  await Promise.all([
    loadDashboardData(),
    loadRecentBookings(),
    loadQueue(),
  ]);
  
  // Start background prefetch after dashboard loads (Rule #5)
  prefetchAllWithDelay().catch(() => {
    // Silent error handling - prefetch errors never affect UX
  });
});

// Re-render charts when theme changes (Day/Night)
const uiStore = useUiStore();
watch(
  () => uiStore.theme,
  () => bumpChartsKey()
);
</script>

<style scoped>
.asmaa-salon-dashboard {
  padding: 0;
  width: 100%;
  margin: 0;
  background: var(--bg-secondary);
}

/* Header action buttons */
.refresh-btn {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-900) 100%);
  border: none;
  box-shadow: var(--shadow-md);
  transition: all 0.3s;
}

.refresh-btn:hover {
  background: linear-gradient(135deg, var(--asmaa-primary-dark) 0%, var(--asmaa-primary) 100%);
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-xl);
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-xl);
}

/* Charts */
.charts-card :deep(.card-body) {
  padding-top: var(--spacing-sm);
}
.charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: var(--spacing-base);
}
.chart-panel {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-xl);
  padding: var(--spacing-base);
}
.chart-title {
  font-weight: 900;
  color: var(--text-primary);
  font-size: var(--font-size-sm);
  margin-bottom: var(--spacing-sm);
}
.chart-canvas {
  position: relative;
  height: 260px;
}
.chart-canvas-sm {
  height: 260px;
}

@media (max-width: 1200px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  .chart-canvas,
  .chart-canvas-sm {
    height: 240px;
  }
}

/* Bookings List */
.bookings-list-modern {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.booking-item-modern {
  display: flex;
  align-items: center;
  gap: var(--spacing-base);
  padding: var(--spacing-base);
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  transition: all var(--transition-base);
  cursor: pointer;
}

.booking-item-modern:hover {
  background: var(--bg-tertiary);
  border-color: var(--asmaa-primary);
  transform: translateX(4px);
  box-shadow: var(--shadow-md);
}

[dir="rtl"] .booking-item-modern:hover {
  transform: translateX(-4px);
}

.booking-arrow {
  color: var(--text-muted);
  transition: all var(--transition-base);
  flex-shrink: 0;
}

.booking-item-modern:hover .booking-arrow {
  color: var(--asmaa-primary);
  transform: translateX(4px);
}

[dir="rtl"] .booking-item-modern:hover .booking-arrow {
  transform: translateX(-4px);
}

.booking-number {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--asmaa-primary);
  min-width: 80px;
}

.booking-details-modern {
  flex: 1;
  min-width: 0;
}

.booking-customer-name {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.booking-meta-modern {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.status-badge {
  font-size: var(--font-size-xs);
  font-weight: 600;
  padding: var(--spacing-xs) var(--spacing-sm);
}

.booking-time-modern {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  font-weight: 500;
}

/* Queue List */
.queue-list-modern {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.queue-item-modern {
  display: flex;
  align-items: center;
  gap: var(--spacing-base);
  padding: var(--spacing-base);
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  transition: all var(--transition-base);
}

.queue-item-modern:hover {
  background: var(--bg-tertiary);
  border-color: var(--border-color);
  transform: translateX(2px);
}

.queue-item-next {
  background: var(--asmaa-primary-soft);
  border-color: var(--asmaa-primary-soft-border);
}

.queue-position-next {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-800) 100%);
  color: white;
  font-weight: 800;
}

.queue-status-badge {
  font-size: var(--font-size-xs);
  padding: var(--spacing-xs) var(--spacing-sm);
}

.queue-position {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--asmaa-primary);
  min-width: 40px;
  text-align: center;
}

.queue-info-modern {
  flex: 1;
  min-width: 0;
}

.queue-customer-name {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.queue-service-modern {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  font-weight: 500;
  display: flex;
  align-items: center;
}

/* Quick Actions */
.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-base);
}

.quick-action-btn {
  display: flex;
  align-items: center;
  gap: var(--spacing-base);
  padding: var(--spacing-lg);
  background: var(--bg-primary);
  border: 2px solid var(--border-color);
  border-radius: var(--radius-xl);
  cursor: pointer;
  transition: all var(--transition-base);
  text-align: left;
  width: 100%;
}

[dir="rtl"] .quick-action-btn {
  text-align: right;
}

.quick-action-btn:hover {
  border-color: var(--asmaa-primary);
  background: var(--bg-secondary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.quick-action-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-xl);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--font-size-xl);
  flex-shrink: 0;
}

.quick-action-icon.blue {
  background: var(--asmaa-primary-soft);
  color: var(--asmaa-primary);
}

.quick-action-icon.gold {
  background: var(--asmaa-primary-soft);
  color: var(--asmaa-primary);
}

.quick-action-icon.brown {
  background: var(--asmaa-primary-soft);
  color: var(--asmaa-primary-800);
}

.quick-action-icon.pink {
  background: var(--asmaa-primary-soft);
  color: var(--asmaa-primary-800);
}

.quick-action-content {
  flex: 1;
  min-width: 0;
}

.quick-action-title {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.quick-action-desc {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
}

.quick-action-arrow {
  color: var(--text-muted);
  transition: all var(--transition-base);
  flex-shrink: 0;
}

.quick-action-btn:hover .quick-action-arrow {
  color: var(--asmaa-primary);
  transform: translateX(4px);
}

[dir="rtl"] .quick-action-btn:hover .quick-action-arrow {
  transform: translateX(-4px);
}

/* Responsive */
@media (max-width: 1200px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .asmaa-salon-dashboard {
    padding: var(--spacing-base);
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .quick-actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<template>
  <div class="reports-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('reports.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-chart-line" class="me-1" />
          {{ t('reports.analyticsDashboard') }}
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" variant="outline" class="nano-btn-outline" @click="exportReport">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="loadAllData" :disabled="loading">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
    </div>
    </div>

    <!-- Tabs Navigation (Modern Pills) -->
    <CNav variant="pills" class="nano-tabs mb-4">
      <CNavItem v-for="tab in reportTabs" :key="tab.id">
        <CNavLink 
          :active="activeTab === tab.id" 
          @click="activeTab = tab.id"
          class="nano-tab-link"
        >
          <CIcon :icon="tab.icon" class="me-2" />
          {{ tab.label }}
        </CNavLink>
          </CNavItem>
        </CNav>

    <!-- Quick Stats Bar (Nano Banana Style - Contextual) -->
    <div class="nano-stats-bar mb-4">
      <template v-if="activeTab === 'overview'">
        <div class="stat-card-nano">
          <div class="stat-icon-bg orders"><CIcon icon="cil-cart" /></div>
          <div class="stat-info">
            <div class="stat-value text-info">{{ overview?.summary?.orders ?? 0 }}</div>
            <div class="stat-label">{{ t('reports.totalOrders') }}</div>
                </div>
              </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg revenue"><CIcon icon="cil-money" /></div>
          <div class="stat-info">
            <div class="stat-value text-success">{{ formatCurrency(overview?.summary?.revenue ?? 0) }}</div>
            <div class="stat-label">{{ t('reports.grossRevenue') }}</div>
                </div>
              </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg commission"><CIcon icon="cil-dollar" /></div>
          <div class="stat-info">
            <div class="stat-value text-primary">{{ formatCurrency(overview?.summary?.commissions_total ?? 0) }}</div>
            <div class="stat-label">{{ t('reports.totalCommissions') }}</div>
                </div>
              </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg loyalty"><CIcon icon="cil-star" /></div>
          <div class="stat-info">
            <div class="stat-value text-warning">{{ overview?.summary?.loyalty_earned ?? 0 }}</div>
            <div class="stat-label">{{ t('loyalty.points') }}</div>
                </div>
              </div>
      </template>
      <template v-else-if="activeTab === 'sales'">
        <div class="stat-card-nano">
          <div class="stat-icon-bg total"><CIcon icon="cil-calendar" /></div>
          <div class="stat-info">
            <div class="stat-value">{{ salesSummary.days }}</div>
            <div class="stat-label">{{ t('reports.daysTracked') }}</div>
                </div>
              </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg orders"><CIcon icon="cil-cart" /></div>
          <div class="stat-info">
            <div class="stat-value text-info">{{ salesSummary.totalOrders }}</div>
            <div class="stat-label">{{ t('reports.totalOrders') }}</div>
                </div>
              </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg revenue"><CIcon icon="cil-money" /></div>
          <div class="stat-info">
            <div class="stat-value text-success">{{ formatCurrency(salesSummary.totalRevenue) }}</div>
            <div class="stat-label">{{ t('reports.totalRevenue') }}</div>
                </div>
              </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg avg"><CIcon icon="cil-chart" /></div>
          <div class="stat-info">
            <div class="stat-value text-primary">{{ formatCurrency(salesSummary.averageOrder) }}</div>
            <div class="stat-label">{{ t('reports.avgOrderValue') }}</div>
                </div>
              </div>
      </template>
              </div>

    <!-- Main Content Panel -->
    <div class="nano-panel">
      <div v-if="loading" class="text-center p-5">
        <CSpinner color="primary" />
                </div>
      <div v-else class="tab-content-wrapper">
        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="overview-grid">
          <CRow class="g-4">
            <CCol md="8">
              <div class="chart-card p-4 rounded-4 border h-100 shadow-sm bg-tertiary">
                <h5 class="fw-bold mb-4">{{ t('reports.revenueOverview') }}</h5>
                <div class="chart-placeholder bg-light rounded-4 d-flex align-items-center justify-content-center" style="height: 350px">
                  <CIcon icon="cil-chart" size="xl" class="text-muted opacity-20" />
              </div>
            </div>
              </CCol>
            <CCol md="4">
              <div class="p-4 rounded-4 border h-100 shadow-sm bg-tertiary">
                <h5 class="fw-bold mb-4">{{ t('reports.topServices') }}</h5>
                <div class="d-flex flex-column gap-3">
                  <div v-for="s in [1,2,3,4,5]" :key="s" class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-secondary">
                    <span class="fw-bold">Service {{ s }}</span>
                    <CBadge color="primary" shape="rounded-pill">45 {{ t('reports.sales') }}</CBadge>
            </div>
        </div>
              </div>
              </CCol>
            </CRow>
        </div>

        <!-- Sales Tab -->
        <div v-if="activeTab === 'sales'">
          <div class="nano-table-container">
            <table class="nano-table w-100">
                <thead>
                  <tr>
                  <th class="text-start">{{ t('common.date') }}</th>
                  <th>{{ t('reports.orders') }}</th>
                  <th class="text-end">{{ t('reports.revenue') }}</th>
                  <th class="text-end">{{ t('reports.commissions') }}</th>
                  <th>{{ t('reports.growth') }}</th>
                  </tr>
                </thead>
                <tbody>
                <tr v-for="day in [1,2,3,4,5]" :key="day" class="nano-table-row">
                  <td class="text-start fw-bold">2023-10-{{ day }}</td>
                  <td>12</td>
                  <td class="text-end text-success fw-bold">150.000 KWD</td>
                  <td class="text-end">15.000 KWD</td>
                  <td><CBadge color="success">+12%</CBadge></td>
                  </tr>
                </tbody>
            </table>
            </div>
        </div>
            </div>
        </div>

    <!-- Filters Section (Collapsible) -->
    <div class="nano-filters-panel mt-4 p-4 rounded-4 bg-secondary shadow-sm">
      <h6 class="fw-bold mb-3"><CIcon icon="cil-filter" class="me-2" />{{ t('reports.filters') }}</h6>
            <CRow class="g-3">
        <CCol md="3">
          <label class="small text-muted fw-bold mb-1">{{ t('reports.dateRange') }}</label>
          <CFormSelect v-model="filters.range">
            <option value="today">{{ t('common.today') }}</option>
            <option value="yesterday">{{ t('common.yesterday') }}</option>
            <option value="this_week">{{ t('common.thisWeek') }}</option>
            <option value="this_month">{{ t('common.thisMonth') }}</option>
            <option value="custom">{{ t('common.customRange') }}</option>
          </CFormSelect>
              </CCol>
        <CCol md="3" v-if="filters.range === 'custom'">
          <label class="small text-muted fw-bold mb-1">{{ t('common.startDate') }}</label>
          <CFormInput type="date" v-model="filters.start_date" />
              </CCol>
        <CCol md="3" v-if="filters.range === 'custom'">
          <label class="small text-muted fw-bold mb-1">{{ t('common.endDate') }}</label>
          <CFormInput type="date" v-model="filters.end_date" />
              </CCol>
        <CCol md="3" class="d-flex align-items-end">
          <CButton color="primary" class="w-100 nano-btn" @click="loadAllData">{{ t('reports.applyFilters') }}</CButton>
              </CCol>
            </CRow>
        </div>

    <HelpSection page-key="reports" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  CButton, CBadge, CRow, CCol, CSpinner, CFormInput, CFormSelect, 
  CNav, CNavItem, CNavLink 
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import HelpSection from '@/components/Common/HelpSection.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();

// State
const loading = ref(false);
const activeTab = ref('overview');
const overview = ref(null);
const salesData = ref([]);
const filters = ref({
  range: 'this_month',
  start_date: '',
  end_date: ''
});

const reportTabs = [
  { id: 'overview', label: t('reports.overview'), icon: 'cil-chart-line' },
  { id: 'sales', label: t('reports.sales'), icon: 'cil-cart' },
  { id: 'commissions', label: t('reports.commissions'), icon: 'cil-dollar' },
  { id: 'staff', label: t('reports.staffPerformance'), icon: 'cil-user' },
  { id: 'customers', label: t('reports.customers'), icon: 'cil-people' },
  { id: 'inventory', label: t('reports.inventory'), icon: 'cil-basket' }
];

const salesSummary = computed(() => {
  return {
    days: 30,
    totalOrders: 450,
    totalRevenue: 12500.500,
    averageOrder: 27.778
  };
});

// Methods
const loadAllData = async () => {
  loading.value = true;
  try {
    const res = await api.get('/reports/overview', { params: { ...filters.value } });
    overview.value = res.data?.data || res.data;
  } catch (e) {
    console.error('Failed to load reports:', e);
  } finally {
    loading.value = false;
  }
};

const exportReport = () => {
  toast.success(t('reports.exported'));
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
};

onMounted(() => {
  loadAllData();
});
</script>

<style scoped>
.reports-page {
  font-family: 'Cairo', sans-serif;
  background: var(--bg-primary);
  min-height: 100vh;
}

.nano-btn {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(142, 126, 120, 0.3);
}
.nano-btn-outline {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  border: 2px solid var(--asmaa-primary);
  color: var(--asmaa-primary);
}

.nano-tabs {
  background: var(--bg-tertiary);
  padding: 0.5rem;
  border-radius: 16px;
  gap: 0.5rem;
}
.nano-tab-link {
  border-radius: 12px !important;
  font-weight: 700 !important;
  padding: 0.75rem 1.5rem !important;
  color: var(--text-muted) !important;
  border: none !important;
  transition: all 0.3s !important;
}
.nano-tab-link.active {
  background: var(--asmaa-primary) !important;
  color: white !important;
  box-shadow: 0 4px 12px rgba(142, 126, 120, 0.3);
}

.nano-stats-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.stat-card-nano {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: var(--shadow-sm);
  transition: all 0.3s;
}
.stat-card-nano:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

.stat-icon-bg {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}
.stat-icon-bg.orders { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
.stat-icon-bg.revenue { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.commission { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); }
.stat-icon-bg.loyalty { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-icon-bg.total { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
.stat-icon-bg.avg { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }

.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; margin-top: 4px; }

.nano-panel {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 2rem;
  box-shadow: var(--shadow-sm);
}

.nano-table-container {
  overflow-x: auto;
}
.nano-table {
  border-collapse: separate;
  border-spacing: 0 0.75rem;
}
.nano-table th {
  padding: 1rem;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 1px;
  border-bottom: 2px solid var(--border-color);
  text-align: center;
}
.nano-table-row {
  background: var(--bg-tertiary);
  transition: all 0.3s;
}
.nano-table-row td {
  padding: 1.25rem 1rem;
  vertical-align: middle;
  text-align: center;
}
.nano-table-row:hover {
  transform: scale(1.01);
  box-shadow: var(--shadow-sm);
}
.nano-table-row td:first-child { border-radius: 16px 0 0 16px; }
.nano-table-row td:last-child { border-radius: 0 16px 16px 0; }

[dir="rtl"] .nano-table-row td:first-child { border-radius: 0 16px 16px 0; }
[dir="rtl"] .nano-table-row td:last-child { border-radius: 16px 0 0 16px; }

.chart-card {
  transition: all 0.3s;
}
.chart-card:hover {
  border-color: var(--asmaa-primary) !important;
}

@media (max-width: 768px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>

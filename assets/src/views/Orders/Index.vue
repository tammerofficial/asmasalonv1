<template>
  <div class="orders-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('orders.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-cart" class="me-1" />
          {{ stats.total || 0 }} {{ t('orders.totalOrders') }}
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" class="nano-btn" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />{{ t('orders.addNew') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="loadOrders" :disabled="loading">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano clickable" @click="resetFilters">
        <div class="stat-icon-bg orders"><CIcon icon="cil-cart" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.total || 0 }}</div>
          <div class="stat-label">{{ t('orders.totalOrders') }}</div>
        </div>
      </div>
      <div class="stat-card-nano clickable" @click="filters.status = 'pending'; loadOrders()">
        <div class="stat-icon-bg pending"><CIcon icon="cil-clock" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">{{ stats.pending || 0 }}</div>
          <div class="stat-label">{{ t('orders.pendingOrders') }}</div>
        </div>
      </div>
      <div class="stat-card-nano clickable" @click="filters.status = 'completed'; loadOrders()">
        <div class="stat-icon-bg completed"><CIcon icon="cil-check-circle" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">{{ stats.completed || 0 }}</div>
          <div class="stat-label">{{ t('status.completed') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg revenue"><CIcon icon="cil-money" /></div>
        <div class="stat-info">
          <div class="stat-value text-info">{{ formatCurrencyShort(stats.totalRevenue || 0) }}</div>
          <div class="stat-label">{{ t('reports.totalRevenue') }}</div>
        </div>
      </div>
    </div>

    <!-- Modern Filters Bar -->
    <div class="nano-filters-bar p-3 bg-secondary rounded-4 mb-4 shadow-sm border border-light">
      <CRow class="g-3 align-items-center">
        <CCol md="4">
          <CInputGroup>
            <CInputGroupText class="bg-transparent border-0"><CIcon icon="cil-magnifying-glass" /></CInputGroupText>
            <CFormInput v-model="filters.search" :placeholder="t('common.search')" @input="debounceSearch" class="border-0 bg-transparent" />
          </CInputGroup>
        </CCol>
        <CCol md="3">
          <CFormSelect v-model="filters.status" @change="loadOrders" class="rounded-3">
            <option value="">{{ t('orders.allStatuses') }}</option>
            <option value="pending">{{ t('status.pending') }}</option>
            <option value="processing">{{ t('status.processing') }}</option>
            <option value="completed">{{ t('status.completed') }}</option>
            <option value="cancelled">{{ t('status.cancelled') }}</option>
          </CFormSelect>
        </CCol>
        <CCol md="3">
          <CButton color="primary" variant="ghost" class="w-100" @click="resetFilters">
            <CIcon icon="cil-filter-x" class="me-1" />{{ t('common.reset') }}
          </CButton>
        </CCol>
      </CRow>
    </div>

    <!-- Orders Display Panel -->
    <div class="nano-panel">
      <div v-if="loading" class="text-center p-5">
        <CSpinner color="primary" />
      </div>
      <div v-else-if="orders.length === 0" class="text-center p-5 text-muted opacity-50">
        <CIcon icon="cil-inbox" size="xl" class="mb-3" />
        <p>{{ t('orders.noOrders') }}</p>
      </div>
      <div v-else>
        <div class="nano-table-container">
          <table class="nano-table w-100">
          <thead>
              <tr>
                <th class="text-start">{{ t('orders.orderNumber') }}</th>
                <th>{{ t('common.customer') }}</th>
                <th>{{ t('common.items') }}</th>
                <th class="text-end">{{ t('common.total') }}</th>
                <th>{{ t('common.status') }}</th>
                <th>{{ t('common.date') }}</th>
                <th>{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
              <tr v-for="order in orders" :key="order.id" class="nano-table-row">
                <td class="text-start"><strong class="text-primary">#{{ order.id }}</strong></td>
                <td>
                  <div class="fw-bold">{{ order.customer_name || t('common.walkIn') }}</div>
                  <div class="small text-muted">{{ order.customer_phone }}</div>
              </td>
                <td><CBadge color="secondary" shape="rounded-pill">{{ order.item_count || 0 }} {{ t('common.items') }}</CBadge></td>
                <td class="text-end fw-bold text-success">{{ formatCurrency(order.total || 0) }}</td>
                <td>
                  <CBadge :color="getStatusColor(order.status)" shape="rounded-pill" class="px-3">
                    {{ order.status?.toUpperCase() }}
                </CBadge>
              </td>
                <td>{{ new Date(order.created_at).toLocaleString() }}</td>
                <td>
                  <div class="d-flex gap-2 justify-content-center">
                    <CButton size="sm" color="info" variant="ghost" @click="viewOrder(order)"><CIcon icon="cil-external-link" /></CButton>
                    <CButton size="sm" color="primary" variant="ghost" @click="printOrder(order)"><CIcon icon="cil-print" /></CButton>
                </div>
              </td>
            </tr>
          </tbody>
          </table>
      </div>

      <!-- Pagination -->
        <div v-if="pagination.total_pages > 1" class="d-flex justify-content-center mt-5">
          <CPagination :pages="pagination.total_pages" :active-page="pagination.current_page" @update:active-page="changePage" />
          </div>
        </div>
    </div>

    <!-- Modals -->
    <CModal :visible="showViewModal" @close="showViewModal = false" size="lg" alignment="center">
      <CModalHeader>
        <CModalTitle><CIcon icon="cil-cart" class="me-2" />Order #{{ selectedOrder?.id }}</CModalTitle>
      </CModalHeader>
      <CModalBody v-if="selectedOrder" class="p-4">
        <!-- Order Details View -->
        <div class="d-flex justify-content-between mb-4">
          <div>
            <div class="small text-muted fw-bold mb-1">{{ t('common.customer').toUpperCase() }}</div>
            <h4 class="fw-bold">{{ selectedOrder.customer_name || t('common.walkIn') }}</h4>
              </div>
          <div class="text-end">
            <div class="small text-muted fw-bold mb-1">{{ t('common.status').toUpperCase() }}</div>
            <CBadge :color="getStatusColor(selectedOrder.status)" shape="rounded-pill" class="px-3 py-2 fs-6">
              {{ selectedOrder.status?.toUpperCase() }}
                </CBadge>
            </div>
          </div>

        <div class="items-table-wrapper rounded-4 border bg-tertiary overflow-hidden mb-4">
          <table class="table mb-0">
            <thead class="bg-secondary">
              <tr>
                <th class="border-0">{{ t('common.item') }}</th>
                <th class="border-0 text-center">{{ t('common.qty') }}</th>
                <th class="border-0 text-end">{{ t('common.price') }}</th>
                <th class="border-0 text-end">{{ t('common.total') }}</th>
                </tr>
              </thead>
              <tbody>
              <tr v-for="item in selectedOrder.items" :key="item.id">
                <td>{{ item.name }}</td>
                <td class="text-center">{{ item.quantity }}</td>
                <td class="text-end">{{ formatCurrency(item.unit_price) }}</td>
                <td class="text-end fw-bold">{{ formatCurrency(item.total) }}</td>
                </tr>
              </tbody>
          </table>
          </div>

        <div class="order-summary-box p-3 bg-secondary rounded-4">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">{{ t('common.subtotal') }}</span>
            <span class="fw-bold">{{ formatCurrency(selectedOrder.subtotal) }}</span>
          </div>
          <div class="d-flex justify-content-between mb-2 text-danger" v-if="selectedOrder.discount > 0">
            <span>{{ t('common.discount') }}</span>
            <span>-{{ formatCurrency(selectedOrder.discount) }}</span>
          </div>
          <div class="d-flex justify-content-between mt-2 pt-2 border-top">
            <h4 class="fw-bold mb-0">{{ t('common.total').toUpperCase() }}</h4>
            <h4 class="fw-bold text-primary mb-0">{{ formatCurrency(selectedOrder.total) }}</h4>
          </div>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" variant="ghost" @click="showViewModal = false">{{ t('common.close') }}</CButton>
        <CButton color="primary" class="nano-btn" @click="printOrder(selectedOrder)">
          <CIcon icon="cil-print" class="me-2" />{{ t('orders.printReceipt') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <HelpSection page-key="orders" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  CButton, CBadge, CRow, CCol, CSpinner, CFormInput, CFormSelect, 
  CInputGroup, CInputGroupText, CPagination, CModal, CModalHeader, 
  CModalTitle, CModalBody, CModalFooter 
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import { useRouter } from 'vue-router';
import HelpSection from '@/components/Common/HelpSection.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();

// State
const loading = ref(false);
const orders = ref([]);
const stats = ref({ total: 0, pending: 0, completed: 0, totalRevenue: 0 });
const pagination = ref({ current_page: 1, per_page: 15, total: 0, total_pages: 0 });
const filters = ref({ search: '', status: '' });

// View Modal
const showViewModal = ref(false);
const selectedOrder = ref(null);

// Methods
const loadOrders = async () => {
  loading.value = true;
  try {
    const res = await api.get('/orders', {
      params: {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
        ...filters.value
      }
    });
    const data = res.data?.data || res.data;
    orders.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
    
    // Stats
    const statsRes = await api.get('/orders/stats');
    stats.value = statsRes.data?.data || statsRes.data || stats.value;
  } catch (e) {
    console.error('Failed to load orders:', e);
  } finally {
    loading.value = false;
  }
};

const debounceSearch = () => {
  clearTimeout(window.oSearchTimer);
  window.searchTimer = setTimeout(loadOrders, 500);
};

const resetFilters = () => {
  filters.value = { search: '', status: '' };
  loadOrders();
};

const changePage = (page) => {
  pagination.value.current_page = page;
    loadOrders();
};

const getStatusColor = (status) => {
  switch (status) {
    case 'pending': return 'warning';
    case 'processing': return 'info';
    case 'completed': return 'success';
    case 'cancelled': return 'danger';
    default: return 'secondary';
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
};

const formatCurrencyShort = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    notation: 'compact',
    minimumFractionDigits: 1
  }).format(amount || 0);
};

const viewOrder = (order) => {
  selectedOrder.value = order;
  showViewModal.value = true;
};

const printOrder = (order) => {
  toast.info(t('orders.sendingToPrinter'));
};

const openCreateModal = () => router.push('/pos');

onMounted(() => {
  loadOrders();
});
</script>

<style scoped>
.orders-page {
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
.stat-card-nano.clickable { cursor: pointer; }
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
.stat-icon-bg.pending { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-icon-bg.completed { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.revenue { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }

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

@media (max-width: 768px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>

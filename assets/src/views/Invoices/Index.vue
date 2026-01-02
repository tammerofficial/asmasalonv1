<template>
  <div class="invoices-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('invoices.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-file" class="me-1" />
          {{ stats.total || invoices.length }} {{ t('invoices.totalInvoices') }}
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" class="nano-btn" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />{{ t('invoices.addNew') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="loadInvoices" :disabled="loading">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano clickable" @click="resetFilters">
        <div class="stat-icon-bg total"><CIcon icon="cil-file" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.total || 0 }}</div>
          <div class="stat-label">{{ t('invoices.totalInvoices') }}</div>
        </div>
      </div>
      <div class="stat-card-nano clickable" @click="filters.status = 'paid'; loadInvoices()">
        <div class="stat-icon-bg paid"><CIcon icon="cil-check-circle" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">{{ stats.paid || 0 }}</div>
          <div class="stat-label">{{ t('status.paid') }}</div>
        </div>
      </div>
      <div class="stat-card-nano clickable" @click="filters.status = 'unpaid'; loadInvoices()">
        <div class="stat-icon-bg unpaid"><CIcon icon="cil-warning" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">{{ stats.unpaid || 0 }}</div>
          <div class="stat-label">{{ t('status.unpaid') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg amount"><CIcon icon="cil-money" /></div>
        <div class="stat-info">
          <div class="stat-value text-info">{{ formatCurrencyShort(stats.totalAmount || 0) }}</div>
          <div class="stat-label">{{ t('common.totalAmount') }}</div>
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
          <CFormSelect v-model="filters.status" @change="loadInvoices" class="rounded-3">
            <option value="">{{ t('invoices.allStatuses') }}</option>
            <option value="draft">{{ t('status.draft') }}</option>
            <option value="sent">{{ t('status.sent') }}</option>
            <option value="paid">{{ t('status.paid') }}</option>
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

    <!-- Invoices Panel -->
    <div class="nano-panel">
      <div v-if="loading" class="text-center p-5">
        <CSpinner color="primary" />
      </div>
      <div v-else-if="invoices.length === 0" class="text-center p-5 text-muted opacity-50">
        <CIcon icon="cil-description" size="xl" class="mb-3" />
        <p>{{ t('invoices.noInvoices') }}</p>
      </div>
      <div v-else>
        <div class="nano-table-container">
          <table class="nano-table w-100">
          <thead>
              <tr>
                <th class="text-start">{{ t('invoices.invoiceNumber') }}</th>
                <th>{{ t('common.customer') }}</th>
                <th class="text-end">{{ t('common.amount') }}</th>
                <th>{{ t('common.status') }}</th>
                <th>{{ t('common.dueDate') }}</th>
                <th>{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
              <tr v-for="inv in invoices" :key="inv.id" class="nano-table-row">
                <td class="text-start fw-bold">#{{ inv.id }}</td>
                <td>
                  <div class="fw-bold">{{ inv.customer_name }}</div>
                  <div class="small text-muted">{{ inv.customer_phone }}</div>
              </td>
                <td class="text-end fw-bold text-success">{{ formatCurrency(inv.total_amount || 0) }}</td>
                <td>
                  <CBadge :color="getStatusColor(inv.status)" shape="rounded-pill" class="px-3">
                    {{ inv.status?.toUpperCase() }}
                </CBadge>
              </td>
                <td>{{ new Date(inv.due_date).toLocaleDateString() }}</td>
                <td>
                  <div class="d-flex gap-2 justify-content-center">
                    <CButton size="sm" color="info" variant="ghost" @click="viewInvoice(inv)"><CIcon icon="cil-external-link" /></CButton>
                    <CButton size="sm" color="primary" variant="ghost" @click="printInvoice(inv)"><CIcon icon="cil-print" /></CButton>
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

    <HelpSection page-key="invoices" />
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
const invoices = ref([]);
const stats = ref({ total: 0, paid: 0, unpaid: 0, totalAmount: 0 });
const pagination = ref({ current_page: 1, per_page: 15, total: 0, total_pages: 0 });
const filters = ref({ search: '', status: '' });

// Methods
const loadInvoices = async () => {
  loading.value = true;
  try {
    const res = await api.get('/invoices', {
      params: {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
        ...filters.value
      }
    });
    const data = res.data?.data || res.data;
    invoices.value = data.items || [];
    pagination.value = data.pagination || pagination.value;

    // Stats
    const statsRes = await api.get('/invoices/stats');
    stats.value = statsRes.data?.data || statsRes.data || stats.value;
  } catch (e) {
    console.error('Failed to load invoices:', e);
  } finally {
    loading.value = false;
  }
};

const debounceSearch = () => {
  clearTimeout(window.iSearchTimer);
  window.searchTimer = setTimeout(loadInvoices, 500);
};

const resetFilters = () => {
  filters.value = { search: '', status: '' };
  loadInvoices();
};

const changePage = (page) => {
  pagination.value.current_page = page;
    loadInvoices();
};

const getStatusColor = (status) => {
  switch (status) {
    case 'paid': return 'success';
    case 'partial': return 'info';
    case 'sent': return 'warning';
    case 'overdue': return 'danger';
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

const viewInvoice = (inv) => router.push(`/invoices/${inv.id}`);
const printInvoice = (inv) => toast.info(t('orders.sendingToPrinter'));
const openCreateModal = () => router.push('/pos');

onMounted(() => {
  loadInvoices();
});
</script>

<style scoped>
.invoices-page {
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
.stat-icon-bg.total { background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%); }
.stat-icon-bg.paid { background: linear-gradient(135deg, var(--asmaa-success) 0%, #059669 100%); }
.stat-icon-bg.unpaid { background: linear-gradient(135deg, var(--asmaa-warning) 0%, #d97706 100%); }
.stat-icon-bg.amount { background: linear-gradient(135deg, var(--asmaa-info) 0%, #0284c7 100%); }

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

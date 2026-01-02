<template>
  <div class="customers-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('customers.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-people" class="me-1" />
          {{ stats.total || customers.length }} {{ t('customers.title') }}
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" class="nano-btn" @click="showCreateModal = true">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('customers.addNew') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="loadCustomers" :disabled="loading">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano clickable" @click="filters.status = 'active'; loadCustomers()">
        <div class="stat-icon-bg customers"><CIcon icon="cil-people" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.active || 0 }}</div>
          <div class="stat-label">{{ t('status.active') }} {{ t('customers.title') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg visits"><CIcon icon="cil-calendar-check" /></div>
        <div class="stat-info">
          <div class="stat-value text-info">{{ stats.totalVisits || 0 }}</div>
          <div class="stat-label">{{ t('customers.totalVisits') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg spending"><CIcon icon="cil-money" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">{{ formatCurrencyShort(stats.totalSpending || 0) }}</div>
          <div class="stat-label">{{ t('customers.totalSpending') }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg points"><CIcon icon="cil-star" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">{{ stats.totalPoints || 0 }}</div>
          <div class="stat-label">{{ t('customers.totalPoints') }}</div>
        </div>
      </div>
    </div>

    <!-- Modern Filters Bar -->
    <div class="nano-filters-bar p-3 bg-secondary rounded-4 mb-4 shadow-sm border border-light">
      <CRow class="g-3 align-items-center">
        <CCol md="4">
          <CInputGroup>
            <CInputGroupText class="bg-transparent border-0"><CIcon icon="cil-magnifying-glass" /></CInputGroupText>
            <CFormInput v-model="filters.search" :placeholder="t('customers.searchPlaceholder')" @input="debounceSearch" class="border-0 bg-transparent" />
            </CInputGroup>
          </CCol>
        <CCol md="3">
          <CFormSelect v-model="filters.status" @change="loadCustomers" class="rounded-3">
              <option value="">{{ t('customers.allStatuses') }}</option>
              <option value="active">{{ t('status.active') }}</option>
              <option value="inactive">{{ t('status.inactive') }}</option>
            </CFormSelect>
          </CCol>
        <CCol md="3">
          <CFormSelect v-model="filters.gender" @change="loadCustomers" class="rounded-3">
              <option value="">{{ t('customers.allGenders') }}</option>
              <option value="male">{{ t('customers.male') }}</option>
              <option value="female">{{ t('customers.female') }}</option>
            </CFormSelect>
          </CCol>
        <CCol md="2">
          <CButton color="primary" variant="ghost" class="w-100" @click="resetFilters">
            <CIcon icon="cil-filter-x" class="me-1" />{{ t('common.reset') }}
            </CButton>
          </CCol>
        </CRow>
      </div>

    <!-- Customers Display -->
    <div class="nano-panel">
      <div v-if="loading" class="text-center p-5">
        <CSpinner color="primary" />
          </div>
      <div v-else-if="customers.length === 0" class="text-center p-5">
        <EmptyState :title="t('common.noData')" :description="t('customers.title')" />
        </div>
      <div v-else>
        <div class="nano-grid">
          <div v-for="customer in customers" :key="customer.id" class="customer-nano-card" @click="viewCustomer(customer)">
            <div class="customer-badge-status" :class="customer.status"></div>
            <div class="customer-avatar-main">{{ customer.name?.charAt(0) }}</div>
            <div class="customer-main-info mt-3 text-center">
              <h5 class="fw-bold mb-1">{{ customer.name }}</h5>
              <p class="text-muted small mb-3">{{ customer.phone || t('common.noPhone') }}</p>
            </div>
            <div class="customer-stats-row d-flex justify-content-between border-top pt-3">
              <div class="stat">
                <span class="label">{{ t('loyalty.points') }}</span>
                <span class="value text-warning fw-bold">{{ customer.points || 0 }}</span>
            </div>
              <div class="stat">
                <span class="label">{{ t('customers.wallet') }}</span>
                <span class="value text-success fw-bold">{{ formatCurrencyShort(customer.wallet_balance || 0) }}</span>
          </div>
              </div>
            <div class="customer-actions-hover mt-3">
              <CButton size="sm" color="info" variant="ghost" @click.stop="editCustomer(customer)">
                <CIcon icon="cil-pencil" />
              </CButton>
              <CButton size="sm" color="primary" variant="ghost" @click.stop="viewHistory(customer)">
                <CIcon icon="cil-history" />
              </CButton>
              <CButton size="sm" color="danger" variant="ghost" @click.stop="confirmDelete(customer)">
                <CIcon icon="cil-trash" />
              </CButton>
              </div>
            </div>
          </div>

        <!-- Pagination -->
        <div v-if="pagination.total_pages > 1" class="d-flex justify-content-center mt-5">
          <CPagination :pages="pagination.total_pages" :active-page="pagination.current_page" @update:active-page="changePage" />
              </div>
              </div>
            </div>

    <!-- Modals (Simplified) -->
    <CModal :visible="showCreateModal" @close="showCreateModal = false" size="lg" alignment="center">
      <CModalHeader>
        <CModalTitle>{{ editingCustomer ? t('customers.edit') : t('customers.addNew') }}</CModalTitle>
      </CModalHeader>
      <CModalBody class="p-4">
        <!-- Customer Form -->
        <CRow class="g-3">
          <CCol md="6">
            <label class="form-label fw-bold">{{ t('customers.fullName') }} *</label>
            <CFormInput v-model="customerForm.name" required />
          </CCol>
          <CCol md="6">
            <label class="form-label fw-bold">{{ t('customers.phone') }} *</label>
            <CFormInput v-model="customerForm.phone" type="tel" required />
          </CCol>
          <CCol md="6">
            <label class="form-label fw-bold">{{ t('customers.email') }}</label>
            <CFormInput v-model="customerForm.email" type="email" />
          </CCol>
          <CCol md="6">
            <label class="form-label fw-bold">{{ t('common.status') }}</label>
            <CFormSelect v-model="customerForm.status">
              <option value="active">{{ t('status.active') }}</option>
              <option value="inactive">{{ t('status.inactive') }}</option>
          </CFormSelect>
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" variant="ghost" @click="showCreateModal = false">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="nano-btn" @click="saveCustomer" :disabled="saving">
          {{ saving ? t('common.saving') : t('common.save') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <HelpSection page-key="customers" />
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
import EmptyState from '@/components/UI/EmptyState.vue';
import HelpSection from '@/components/Common/HelpSection.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();

// State
const loading = ref(false);
const saving = ref(false);
const customers = ref([]);
const stats = ref({
  total: 0,
  active: 0,
  inactive: 0,
  totalVisits: 0,
  totalSpending: 0,
  totalPoints: 0
});
const pagination = ref({
  current_page: 1,
  per_page: 12,
  total: 0,
  total_pages: 0
});
const filters = ref({
  search: '',
  status: '',
  gender: ''
});

// Form State
const showCreateModal = ref(false);
const editingCustomer = ref(null);
const customerForm = ref({
  name: '',
  phone: '',
  email: '',
  gender: 'female',
  status: 'active'
});

// Methods
const loadCustomers = async () => {
  loading.value = true;
  try {
    const response = await api.get('/customers', {
      params: {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
        ...filters.value
      }
    });
    const data = response.data?.data || response.data;
    customers.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
    
    // Load stats
    const statsRes = await api.get('/customers/stats');
    stats.value = statsRes.data?.data || statsRes.data || stats.value;
  } catch (e) {
    console.error('Failed to load customers:', e);
  } finally {
    loading.value = false;
  }
};

const debounceSearch = () => {
  clearTimeout(window.searchTimer);
  window.searchTimer = setTimeout(() => {
    pagination.value.current_page = 1;
  loadCustomers();
  }, 500);
};

const resetFilters = () => {
  filters.value = { search: '', status: '', gender: '' };
  loadCustomers();
};

const changePage = (page) => {
  pagination.value.current_page = page;
    loadCustomers();
};

const editCustomer = (customer) => {
  editingCustomer.value = customer;
  customerForm.value = { ...customer };
  showCreateModal.value = true;
};

const saveCustomer = async () => {
  saving.value = true;
  try {
    if (editingCustomer.value) {
      await api.put(`/customers/${editingCustomer.value.id}`, customerForm.value);
      toast.success(t('customers.updated'));
    } else {
      await api.post('/customers', customerForm.value);
      toast.success(t('customers.added'));
    }
    showCreateModal.value = false;
    loadCustomers();
  } catch (e) {
    toast.error(t('customers.failedToSave'));
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async (customer) => {
  if (confirm(t('customers.confirmDelete', { name: customer.name }))) {
  try {
    await api.delete(`/customers/${customer.id}`);
      toast.success(t('customers.deleted'));
    loadCustomers();
    } catch (e) {
      toast.error(t('customers.failedToDelete'));
    }
  }
};

const viewCustomer = (customer) => {
  router.push(`/customers/${customer.id}`);
};

const viewHistory = (customer) => {
  router.push({ path: '/pos', query: { customer_id: customer.id, tab: 'history' } });
};

const formatCurrencyShort = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    notation: 'compact',
    minimumFractionDigits: 1
  }).format(amount || 0);
};

onMounted(() => {
  loadCustomers();
});
</script>

<style scoped>
.customers-page {
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
.stat-icon-bg.customers { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
.stat-icon-bg.visits { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
.stat-icon-bg.spending { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.points { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; margin-top: 4px; }

.nano-panel {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 2rem;
  box-shadow: var(--shadow-sm);
}

.nano-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.5rem;
}

.customer-nano-card {
  background: var(--bg-tertiary);
  border-radius: 24px;
  padding: 1.5rem;
  position: relative;
  border: 1px solid var(--border-color);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  overflow: hidden;
}
.customer-nano-card:hover {
  transform: translateY(-8px);
  border-color: var(--asmaa-primary);
  box-shadow: var(--shadow-md);
}

.customer-badge-status {
  position: absolute;
  top: 15px;
  right: 15px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  }
.customer-badge-status.active { background: #10b981; box-shadow: 0 0 8px #10b981; }
.customer-badge-status.inactive { background: #ef4444; }

.customer-avatar-main {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, #d4b996 100%);
  color: white;
  font-size: 1.75rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  box-shadow: 0 4px 10px rgba(142, 126, 120, 0.3);
}

.customer-stats-row .stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.customer-stats-row .stat .label { font-size: 0.625rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; }
.customer-stats-row .stat .value { font-size: 0.875rem; }

.customer-actions-hover {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.3s;
}
.customer-nano-card:hover .customer-actions-hover {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 768px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>

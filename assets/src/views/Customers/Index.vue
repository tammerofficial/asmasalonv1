<template>
  <div class="customers-page">
    <PageHeader 
      :title="t('customers.title')"
      :subtitle="t('customers.title') + ' - ' + t('dashboard.subtitle')"
    >
      <template #icon>
        <CIcon icon="cil-people" />
      </template>
      
      <template #actions>
        <CButton color="primary" class="btn-primary-custom" @click="showCreateModal = true">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('customers.addNew') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('customers.title')"
        :value="stats.total || customers.length"
        :badge="stats.active + ' ' + t('status.active')"
        badge-variant="success"
        color="gold"
        :clickable="true"
        @click="filters.status = 'active'; loadCustomers()"
      >
        <template #icon>
          <CIcon icon="cil-people" />
        </template>
      </StatCard>

      <StatCard 
        label="Total Visits"
        :value="stats.totalVisits"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-calendar-check" />
        </template>
      </StatCard>

      <StatCard 
        label="Total Spending"
        :value="formatCurrencyShort(stats.totalSpending)"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-money" />
        </template>
      </StatCard>

      <StatCard 
        label="Total Points"
        :value="stats.totalPoints"
        badge-variant="warning"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-star" />
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
                v-model="filters.search"
                :placeholder="t('customers.searchPlaceholder')"
                @input="debounceSearch"
                class="filter-input search-input"
              />
            </CInputGroup>
          </CCol>
          <CCol :md="3">
            <CFormSelect v-model="filters.status" @change="loadCustomers" class="filter-select">
              <option value="">{{ t('customers.allStatuses') }}</option>
              <option value="active">{{ t('status.active') }}</option>
              <option value="inactive">{{ t('status.inactive') }}</option>
            </CFormSelect>
          </CCol>
          <CCol :md="3">
            <CFormSelect v-model="filters.gender" @change="loadCustomers" class="filter-select">
              <option value="">{{ t('customers.allGenders') }}</option>
              <option value="male">{{ t('customers.male') }}</option>
              <option value="female">{{ t('customers.female') }}</option>
            </CFormSelect>
          </CCol>
          <CCol :md="2">
            <CButton 
              color="secondary" 
              variant="outline" 
              @click="resetFilters" 
              class="w-100 reset-btn"
            >
              <CIcon icon="cil-reload" class="me-1" />
              {{ t('common.reset') }}
            </CButton>
          </CCol>
        </CRow>
    </Card>

    <!-- Table -->
    <Card :title="t('customers.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="customers.length === 0"
        :title="t('common.noData')"
        :description="t('customers.title') + ' - ' + t('common.noData')"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" @click="showCreateModal = true">
            {{ t('customers.addNew') }}
          </CButton>
        </template>
      </EmptyState>

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern customers-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-id">#</th>
              <th class="th-name">{{ t('customers.name') }}</th>
              <th class="th-phone">{{ t('customers.phone') }}</th>
              <th class="th-email">{{ t('customers.email') }}</th>
              <th class="th-visits">{{ t('customers.visits') }}</th>
              <th class="th-spending">{{ t('customers.spending') }}</th>
              <th class="th-points">{{ t('customers.points') }}</th>
              <th class="th-status">{{ t('common.status') }}</th>
              <th class="th-actions">{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="customer in customers" :key="customer.id" class="table-row customer-row">
              <td class="td-id">
                <span class="customer-id-badge">#{{ customer.id }}</span>
              </td>
              <td class="td-name">
                <div class="customer-name-cell">
                  <strong class="customer-name">{{ customer.name }}</strong>
                  <small class="customer-location" v-if="customer.city">
                    <CIcon icon="cil-location-pin" class="location-icon" />
                    {{ customer.city }}
                  </small>
                </div>
              </td>
              <td class="td-phone">
                <a :href="`tel:${customer.phone}`" class="phone-link">
                  <CIcon icon="cil-phone" class="phone-icon" />
                  <span>{{ customer.phone }}</span>
                </a>
              </td>
              <td class="td-email">
                <a v-if="customer.email" :href="`mailto:${customer.email}`" class="email-link">
                  <CIcon icon="cil-envelope" class="email-icon" />
                  <span>{{ customer.email }}</span>
                </a>
                <span v-else class="text-muted no-email">-</span>
              </td>
              <td class="td-visits">
                <CBadge class="unified-badge visits-badge">
                  <CIcon icon="cil-calendar-check" class="badge-icon" />
                  <span>{{ customer.total_visits || 0 }}</span>
                </CBadge>
              </td>
              <td class="td-spending">
                <strong class="spending-amount unified-amount">
                  <CIcon icon="cil-money" class="money-icon" />
                  {{ formatCurrency(customer.total_spent || 0) }}
                </strong>
              </td>
              <td class="td-points">
                <CBadge class="unified-badge points-badge">
                  <CIcon icon="cil-star" class="badge-icon" />
                  <span>{{ customer.loyalty_points || 0 }}</span>
                </CBadge>
              </td>
              <td class="td-status">
                <CBadge 
                  class="unified-badge status-badge"
                  :class="customer.is_active ? 'status-active' : 'status-inactive'"
                >
                  <CIcon 
                    :icon="customer.is_active ? 'cil-check-circle' : 'cil-x-circle'" 
                    class="badge-icon" 
                  />
                  <span>{{ customer.is_active ? t('status.active') : t('status.inactive') }}</span>
                </CBadge>
              </td>
              <td class="td-actions">
                <div class="actions-group">
                  <button 
                    class="action-btn" 
                    @click="viewCustomer(customer)" 
                    :title="t('common.view')"
                  >
                    <CIcon icon="cil-info" />
                  </button>
                  <button 
                    class="action-btn" 
                    @click="editCustomer(customer)" 
                    :title="t('common.edit')"
                  >
                    <CIcon icon="cil-pencil" />
                  </button>
                  <button 
                    class="action-btn" 
                    @click="deleteCustomer(customer)" 
                    :title="t('common.delete')"
                  >
                    <CIcon icon="cil-trash" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </CTable>
      </div>

      <!-- Pagination -->
      <template #footer>
        <div v-if="pagination.total_pages > 1" class="d-flex justify-content-between align-items-center">
          <div class="text-muted">
            {{ t('common.view') }} {{ (pagination.current_page - 1) * pagination.per_page + 1 }} 
            {{ t('common.to') || 'to' }} 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
            {{ t('common.of') || 'of' }} 
            {{ pagination.total }}
          </div>
          <CPagination
            :pages="pagination.total_pages"
            :active-page="pagination.current_page"
            @update:active-page="changePage"
          />
        </div>
      </template>
    </Card>

    <!-- View Customer Modal -->
    <CModal :visible="showViewModal" @close="closeViewModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-user" class="me-2" />
          {{ t('customers.customerDetails') || 'Customer Details' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody v-if="viewingCustomer">
        <div class="customer-details-view">
          <!-- Customer Header -->
          <div class="customer-header">
            <div class="customer-avatar">
              <CIcon icon="cil-user" />
            </div>
            <div class="customer-header-info">
              <h4 class="customer-name-large">{{ viewingCustomer.name }}</h4>
              <CBadge 
                :color="viewingCustomer.is_active ? 'success' : 'secondary'" 
                class="status-badge-large"
              >
                <CIcon 
                  :icon="viewingCustomer.is_active ? 'cil-check-circle' : 'cil-x-circle'" 
                  class="me-1" 
                />
                {{ viewingCustomer.is_active ? t('status.active') : t('status.inactive') }}
              </CBadge>
            </div>
          </div>

          <!-- Customer Stats -->
          <div class="customer-stats-grid">
            <div class="stat-item">
              <CIcon icon="cil-calendar-check" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('customers.visits') }}</div>
                <div class="stat-value">{{ viewingCustomer.total_visits || 0 }}</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-money" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('customers.spending') }}</div>
                <div class="stat-value">{{ formatCurrency(viewingCustomer.total_spent || 0) }}</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-star" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('customers.points') }}</div>
                <div class="stat-value">{{ viewingCustomer.loyalty_points || 0 }}</div>
              </div>
            </div>
          </div>

          <!-- Customer Information -->
          <div class="customer-info-grid">
            <div class="info-row">
              <div class="info-label">
                <CIcon icon="cil-phone" class="me-2" />
                {{ t('customers.phone') }}
              </div>
              <div class="info-value">
                <a :href="`tel:${viewingCustomer.phone}`" class="info-link">
                  {{ viewingCustomer.phone }}
                </a>
              </div>
            </div>
            <div class="info-row" v-if="viewingCustomer.email">
              <div class="info-label">
                <CIcon icon="cil-envelope" class="me-2" />
                {{ t('customers.email') }}
              </div>
              <div class="info-value">
                <a :href="`mailto:${viewingCustomer.email}`" class="info-link">
                  {{ viewingCustomer.email }}
                </a>
              </div>
            </div>
            <div class="info-row" v-if="viewingCustomer.city">
              <div class="info-label">
                <CIcon icon="cil-location-pin" class="me-2" />
                {{ t('customers.city') }}
              </div>
              <div class="info-value">{{ viewingCustomer.city }}</div>
            </div>
            <div class="info-row" v-if="viewingCustomer.gender">
              <div class="info-label">
                <CIcon icon="cil-user" class="me-2" />
                {{ t('customers.gender') }}
              </div>
              <div class="info-value">
                {{ viewingCustomer.gender === 'male' ? t('customers.male') : t('customers.female') }}
              </div>
            </div>
            <div class="info-row" v-if="viewingCustomer.notes">
              <div class="info-label">
                <CIcon icon="cil-notes" class="me-2" />
                {{ t('customers.notes') }}
              </div>
              <div class="info-value">{{ viewingCustomer.notes }}</div>
            </div>
            <div class="info-row">
              <div class="info-label">
                <CIcon icon="cil-calendar" class="me-2" />
                {{ t('common.createdAt') || 'Created At' }}
              </div>
              <div class="info-value">
                {{ viewingCustomer.created_at ? new Date(viewingCustomer.created_at).toLocaleDateString() : '-' }}
              </div>
            </div>
          </div>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeViewModal">{{ t('common.close') || 'Close' }}</CButton>
        <CButton 
          color="primary" 
          @click="() => { closeViewModal(); editCustomer(viewingCustomer); }"
        >
          <CIcon icon="cil-pencil" class="me-2" />
          {{ t('common.edit') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Create/Edit Modal -->
    <CModal :visible="showCreateModal || showEditModal" @close="closeModal" size="lg">
      <CModalHeader>
        <CModalTitle>{{ showEditModal ? t('customers.edit') : t('customers.addNew') }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm>
          <CFormInput v-model="form.name" :label="t('customers.name')" required />
          <CFormInput v-model="form.phone" :label="t('customers.phone')" required />
          <CFormInput v-model="form.email" type="email" :label="t('customers.email')" />
          <CFormSelect v-model="form.gender" :label="t('customers.gender')">
            <option value="">{{ t('common.select') || 'Select...' }}</option>
            <option value="male">{{ t('customers.male') }}</option>
            <option value="female">{{ t('customers.female') }}</option>
          </CFormSelect>
          <CFormInput v-model="form.city" :label="t('customers.city')" />
          <CFormTextarea v-model="form.notes" :label="t('customers.notes')" rows="3" />
          <CFormCheck v-model="form.is_active" :label="t('status.active')" />
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" @click="saveCustomer">{{ t('common.save') }}</CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  CButton,
  CCard,
  CCardBody,
  CTable,
  CBadge,
  CButtonGroup,
  CSpinner,
  CPagination,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CForm,
  CFormInput,
  CFormSelect,
  CFormTextarea,
  CFormCheck,
  CInputGroup,
  CInputGroupText,
  CRow,
  CCol,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useRoute, useRouter } from 'vue-router';
import { useTranslation } from '@/composables/useTranslation';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const route = useRoute();
const router = useRouter();

const customers = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showViewModal = ref(false);
const viewingCustomer = ref(null);
const editingCustomer = ref(null);

const stats = computed(() => {
  const total = customers.value.length;
  const active = customers.value.filter(c => c.is_active).length;
  const totalVisits = customers.value.reduce((sum, c) => sum + (c.total_visits || 0), 0);
  const totalSpending = customers.value.reduce((sum, c) => sum + (parseFloat(c.total_spent) || 0), 0);
  const totalPoints = customers.value.reduce((sum, c) => sum + (c.loyalty_points || 0), 0);
  
  return {
    total,
    active,
    totalVisits,
    totalSpending,
    totalPoints,
  };
});

const filters = ref({
  search: '',
  status: '',
  gender: '',
});

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});

const form = ref({
  name: '',
  phone: '',
  email: '',
  gender: '',
  city: '',
  notes: '',
  is_active: true,
});

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount);
};

const formatCurrencyShort = (amount) => {
  if (!amount && amount !== 0) return '0 KWD';
  const value = parseFloat(amount);
  if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K KWD`;
  }
  return `${value.toFixed(0)} KWD`;
};

const loadCustomers = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
      ...filters.value,
    };

    // Remove empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === '') delete params[key];
    });

    const response = await api.get('/customers', { params });
    const data = response.data?.data || response.data || {};
    customers.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading customers:', error);
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  pagination.value.current_page = page;
  loadCustomers();
};

const resetFilters = () => {
  filters.value = { search: '', status: '', gender: '' };
  pagination.value.current_page = 1;
  loadCustomers();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadCustomers();
  }, 500);
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingCustomer.value = null;
  form.value = {
    name: '',
    phone: '',
    email: '',
    gender: '',
    city: '',
    notes: '',
    is_active: true,
  };
};

const editCustomer = (customer) => {
  editingCustomer.value = customer;
  form.value = { ...customer };
  showEditModal.value = true;
};

const saveCustomer = async () => {
  try {
    if (editingCustomer.value) {
      await api.put(`/customers/${editingCustomer.value.id}`, form.value);
    } else {
      await api.post('/customers', form.value);
    }
    closeModal();
    loadCustomers();
  } catch (error) {
    console.error('Error saving customer:', error);
    alert(t('customers.saveError'));
  }
};

const deleteCustomer = async (customer) => {
  if (!confirm(`${t('customers.deleteConfirm')} ${customer.name}?`)) return;

  try {
    await api.delete(`/customers/${customer.id}`);
    loadCustomers();
  } catch (error) {
    console.error('Error deleting customer:', error);
    alert(t('customers.deleteError'));
  }
};

const viewCustomer = (customer) => {
  router.push({ name: 'CustomerProfile', params: { id: String(customer.id) } });
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewingCustomer.value = null;
};

onMounted(() => {
  loadCustomers();

  // Deep link: /customers?action=create
  if (route.query?.action === 'create') {
    showCreateModal.value = true;
    const nextQuery = { ...route.query };
    delete nextQuery.action;
    router.replace({ query: nextQuery });
  }
});
</script>

<style scoped>
.customers-page {
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

/* Filters */
.filters-card {
  border: 1px solid var(--border-color);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.search-input-group {
  position: relative;
}

.search-icon-wrapper {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  color: white;
  border-color: var(--asmaa-primary);
}

.filter-input,
.filter-select {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid var(--border-color);
}

.filter-input:focus,
.filter-select:focus {
  border-color: var(--asmaa-primary);
  box-shadow: 0 0 0 3px rgba(187, 160, 122, 0.15);
  outline: none;
}

.search-input:focus {
  border-left: none;
}

.reset-btn {
  transition: all 0.3s;
}

.reset-btn:hover {
  background: var(--asmaa-primary);
  color: white;
  border-color: var(--asmaa-primary);
  transform: translateY(-1px);
}

/* Primary Button */
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

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
}

.customers-table {
  margin: 0;
  border-collapse: separate;
  border-spacing: 0;
}

.table-header-row {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  border-bottom: 2px solid var(--asmaa-primary);
}

.table-header-row th {
  padding: 1rem 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
  border-bottom: none;
  white-space: nowrap;
}

.th-id {
  width: 80px;
  text-align: center;
}

.th-name {
  min-width: 200px;
}

.th-phone,
.th-email {
  min-width: 180px;
}

.th-visits,
.th-points {
  width: 120px;
  text-align: center;
}

.th-spending {
  width: 140px;
  text-align: right;
}

.th-status {
  width: 120px;
  text-align: center;
}

.th-actions {
  width: 140px;
  text-align: center;
}

/* Table Rows */
.customer-row {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid var(--border-color);
}

.customer-row:last-child {
  border-bottom: none;
}

.customer-row:hover {
  background: linear-gradient(90deg, rgba(187, 160, 122, 0.05) 0%, rgba(187, 160, 122, 0.02) 100%);
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(187, 160, 122, 0.1);
}

[dir="rtl"] .customer-row:hover {
  transform: translateX(-4px);
}

.customer-row td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  border-bottom: 1px solid var(--border-color);
}

/* Customer ID */
.customer-id-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  color: white;
  font-weight: 800;
  font-size: 0.875rem;
  box-shadow: 0 2px 8px rgba(187, 160, 122, 0.3);
  transition: all 0.3s;
}

.customer-row:hover .customer-id-badge {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

/* Customer Name */
.customer-name-cell {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.customer-name {
  display: block;
  color: var(--text-primary);
  font-weight: 700;
  font-size: 0.9375rem;
  margin: 0;
}

.customer-location {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: var(--text-secondary);
  margin: 0;
}

.location-icon {
  width: 14px;
  height: 14px;
  color: var(--asmaa-primary);
}

/* Phone & Email Links */
.phone-link,
.email-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-primary);
  text-decoration: none;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  transition: all 0.3s;
  font-size: 0.875rem;
}

.phone-icon,
.email-icon {
  width: 16px;
  height: 16px;
  color: var(--asmaa-primary);
  transition: all 0.3s;
}

.phone-link:hover,
.email-link:hover {
  background: rgba(187, 160, 122, 0.1);
  color: var(--asmaa-primary);
  transform: translateX(2px);
}

[dir="rtl"] .phone-link:hover,
[dir="rtl"] .email-link:hover {
  transform: translateX(-2px);
}

.phone-link:hover .phone-icon,
.email-link:hover .email-icon {
  transform: scale(1.2);
}

.no-email {
  font-size: 0.875rem;
  color: var(--text-muted);
}

/* Unified Badges - All using system color */
.unified-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: all 0.3s;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.15) 0%, rgba(187, 160, 122, 0.1) 100%);
  color: var(--asmaa-primary);
  border: 1px solid rgba(187, 160, 122, 0.3);
}

.unified-badge .badge-icon {
  width: 14px;
  height: 14px;
  color: var(--asmaa-primary);
}

.unified-badge:hover {
  transform: translateY(-2px);
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.25) 0%, rgba(187, 160, 122, 0.15) 100%);
  box-shadow: 0 4px 8px rgba(187, 160, 122, 0.2);
  border-color: var(--asmaa-primary);
}

.status-badge.status-active {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.2) 0%, rgba(187, 160, 122, 0.15) 100%);
  color: var(--asmaa-primary);
  border-color: rgba(187, 160, 122, 0.4);
}

.status-badge.status-inactive {
  background: linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);
  color: var(--asmaa-secondary);
  border-color: var(--asmaa-secondary-soft-border);
}

.status-badge.status-inactive .badge-icon {
  color: var(--asmaa-secondary);
}

.badge-icon {
  width: 14px;
  height: 14px;
}

/* Unified Spending Amount */
.unified-amount {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--asmaa-primary);
  font-weight: 700;
  font-size: 0.9375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  transition: all 0.3s;
}

.unified-amount:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.15) 0%, rgba(187, 160, 122, 0.1) 100%);
  transform: translateY(-1px);
}

.money-icon {
  width: 16px;
  height: 16px;
  color: var(--asmaa-primary);
}

/* Actions */
.actions-group {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.action-btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: translate(-50%, -50%);
  transition: width 0.3s, height 0.3s;
}

.action-btn:hover::before {
  width: 100px;
  height: 100px;
}

.action-btn CIcon {
  position: relative;
  z-index: 1;
  width: 18px;
  height: 18px;
  transition: all 0.3s;
}

.action-btn {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  color: white;
  box-shadow: 0 2px 6px rgba(187, 160, 122, 0.3);
}

.action-btn:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

.action-btn:active {
  transform: translateY(0) scale(0.95);
}

/* Responsive */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .customers-table {
    font-size: 0.8125rem;
  }

  .table-header-row th,
  .customer-row td {
    padding: 0.75rem 0.5rem;
  }

  .customer-id-badge {
    width: 40px;
    height: 40px;
    font-size: 0.75rem;
  }

  .action-btn {
    width: 32px;
    height: 32px;
  }

  .action-btn CIcon {
    width: 16px;
    height: 16px;
  }

  .th-name,
  .th-phone,
  .th-email {
    min-width: 150px;
  }
}

@media (max-width: 576px) {
  .actions-group {
    flex-direction: column;
    gap: 0.25rem;
  }

  .action-btn {
    width: 100%;
    height: 32px;
  }
}

/* Customer Details Modal */
.customer-details-view {
  padding: 0.5rem 0;
}

.customer-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(187, 160, 122, 0.2);
}

.customer-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.customer-avatar CIcon {
  width: 40px;
  height: 40px;
}

.customer-header-info {
  flex: 1;
}

.customer-name-large {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
}

.status-badge-large {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
}

.customer-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.08) 0%, rgba(187, 160, 122, 0.03) 100%);
  border-radius: 10px;
  border: 1px solid rgba(187, 160, 122, 0.15);
  transition: all 0.3s;
}

.stat-item:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.12) 0%, rgba(187, 160, 122, 0.06) 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(187, 160, 122, 0.15);
}

.stat-icon {
  width: 32px;
  height: 32px;
  color: var(--asmaa-primary);
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-primary);
}

.customer-info-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 8px;
  border: 1px solid var(--border-color);
  transition: all 0.3s;
}

.info-row:hover {
  background: rgba(187, 160, 122, 0.05);
  border-color: rgba(187, 160, 122, 0.3);
}

.info-label {
  min-width: 140px;
  font-weight: 600;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.info-label CIcon {
  width: 18px;
  height: 18px;
  color: var(--asmaa-primary);
}

.info-value {
  flex: 1;
  color: var(--text-primary);
  font-size: 0.9375rem;
}

.info-link {
  color: var(--asmaa-primary);
  text-decoration: none;
  transition: all 0.3s;
}

.info-link:hover {
  color: rgba(187, 160, 122, 0.8);
  text-decoration: underline;
}

@media (max-width: 768px) {
  .customer-stats-grid {
    grid-template-columns: 1fr;
  }

  .customer-header {
    flex-direction: column;
    text-align: center;
  }

  .info-row {
    flex-direction: column;
    gap: 0.5rem;
  }

  .info-label {
    min-width: auto;
  }
}
</style>

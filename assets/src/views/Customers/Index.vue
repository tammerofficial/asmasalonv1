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
                  <div class="d-flex align-items-center gap-2">
                    <strong class="customer-name">{{ customer.name }}</strong>
                    <CButton
                      v-if="customer.wc_customer_id"
                      color="success"
                      size="sm"
                      variant="outline"
                      :href="`/wp-admin/user-edit.php?user_id=${customer.wc_customer_id}`"
                      target="_blank"
                      :title="'WooCommerce Customer ID: ' + customer.wc_customer_id"
                      class="wc-link-btn"
                    >
                      <CIcon icon="cil-cart" class="me-1" />
                      WC
                    </CButton>
                  </div>
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

    <!-- FAQ/Help Section (Rule #7) -->
    <HelpSection page-key="customers" />
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
import HelpSection from '@/components/Common/HelpSection.vue';
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
  gap: var(--spacing-lg);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-lg);
}

/* Filters */
.filters-card {
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
}

.search-input-group {
  position: relative;
}

.search-icon-wrapper {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-800) 100%);
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
  box-shadow: 0 0 0 3px var(--asmaa-primary-soft);
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
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-900) 100%);
  border: none;
  box-shadow: var(--shadow-md);
  transition: all 0.3s;
}

.btn-primary-custom:hover {
  background: linear-gradient(135deg, var(--asmaa-primary-dark) 0%, var(--asmaa-primary) 100%);
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
  border-radius: var(--radius-xl);
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
}

.customers-table {
  margin: 0;
  border-collapse: separate;
  border-spacing: 0;
}

.table-header-row {
  background: linear-gradient(135deg, var(--asmaa-primary-soft) 0%, var(--asmaa-primary-soft) 100%);
  border-bottom: 2px solid var(--asmaa-primary);
}

.table-header-row th {
  padding: var(--spacing-base) var(--spacing-md);
  font-weight: 700;
  color: var(--text-primary);
  text-transform: uppercase;
  font-size: var(--font-size-xs);
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
  background: linear-gradient(90deg, var(--asmaa-primary-soft) 0%, var(--asmaa-primary-soft) 100%);
  transform: translateX(4px);
  box-shadow: var(--shadow-sm);
}

[dir="rtl"] .customer-row:hover {
  transform: translateX(-4px);
}

.customer-row td {
  padding: var(--spacing-base) var(--spacing-md);
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
  border-radius: var(--radius-xl);
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-800) 100%);
  color: white;
  font-weight: 800;
  font-size: var(--font-size-sm);
  box-shadow: var(--shadow-sm);
  transition: all 0.3s;
}

.customer-row:hover .customer-id-badge {
  transform: scale(1.1);
  box-shadow: var(--shadow-md);
}

/* Customer Name */
.customer-name-cell {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.customer-name {
  display: block;
  color: var(--text-primary);
  font-weight: 700;
  font-size: var(--font-size-sm);
  margin: 0;
}

.customer-location {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-xs);
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
  gap: var(--spacing-sm);
  color: var(--text-primary);
  text-decoration: none;
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-md);
  transition: all 0.3s;
  font-size: var(--font-size-sm);
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
  background: var(--asmaa-primary-soft);
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
  font-size: var(--font-size-sm);
  color: var(--text-muted);
}

/* Unified Badges - All using system color */
.unified-badge {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-lg);
  font-size: var(--font-size-xs);
  font-weight: 600;
  transition: all 0.3s;
  background: var(--asmaa-primary-soft);
  color: var(--asmaa-primary);
  border: 1px solid var(--asmaa-primary-soft-border);
}

.unified-badge .badge-icon {
  width: 14px;
  height: 14px;
  color: var(--asmaa-primary);
}

.unified-badge:hover {
  transform: translateY(-2px);
  background: var(--asmaa-primary-soft);
  box-shadow: var(--shadow-sm);
  border-color: var(--asmaa-primary);
}

.status-badge.status-active {
  background: var(--asmaa-primary-soft);
  color: var(--asmaa-primary);
  border-color: var(--asmaa-primary-soft-border);
}

.status-badge.status-inactive {
  background: var(--asmaa-secondary-soft);
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
  gap: var(--spacing-sm);
  color: var(--asmaa-primary);
  font-weight: 700;
  font-size: var(--font-size-sm);
  padding: var(--spacing-xs) var(--spacing-md);
  border-radius: var(--radius-lg);
  background: var(--asmaa-primary-soft);
  transition: all 0.3s;
}

.unified-amount:hover {
  background: var(--asmaa-primary-soft);
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
  gap: var(--spacing-sm);
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: var(--radius-lg);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-900) 100%);
  color: white;
  box-shadow: var(--shadow-sm);
}

.action-btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: var(--radius-full);
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

.action-btn:hover {
  background: linear-gradient(135deg, var(--asmaa-primary-dark) 0%, var(--asmaa-primary) 100%);
  transform: translateY(-2px) scale(1.05);
  box-shadow: var(--shadow-md);
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
    font-size: var(--font-size-xs);
  }

  .table-header-row th,
  .customer-row td {
    padding: var(--spacing-md) var(--spacing-sm);
  }

  .customer-id-badge {
    width: 40px;
    height: 40px;
    font-size: var(--font-size-xs);
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
    gap: var(--spacing-xs);
  }

  .action-btn {
    width: 100%;
    height: 32px;
  }
}

/* Customer Details Modal */
.customer-details-view {
  padding: var(--spacing-sm) 0;
}

.customer-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-lg);
  background: var(--asmaa-primary-soft);
  border-radius: var(--radius-xl);
  margin-bottom: var(--spacing-lg);
  border: 1px solid var(--asmaa-primary-soft-border);
}

.customer-avatar {
  width: 80px;
  height: 80px;
  border-radius: var(--radius-full);
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-800) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: var(--font-size-2xl);
  box-shadow: var(--shadow-md);
}

.customer-avatar CIcon {
  width: 40px;
  height: 40px;
}

.customer-header-info {
  flex: 1;
}

.customer-name-large {
  margin: 0 0 var(--spacing-sm) 0;
  font-size: var(--font-size-2xl);
  font-weight: 700;
  color: var(--text-primary);
}

.status-badge-large {
  display: inline-flex;
  align-items: center;
  padding: var(--spacing-sm) var(--spacing-base);
  border-radius: var(--radius-lg);
  font-weight: 600;
}

.customer-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-base);
  margin-bottom: var(--spacing-lg);
}

.stat-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-base);
  padding: var(--spacing-base);
  background: var(--asmaa-primary-soft);
  border-radius: var(--radius-lg);
  border: 1px solid var(--asmaa-primary-soft-border);
  transition: all 0.3s;
}

.stat-item:hover {
  background: var(--asmaa-primary-soft);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
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
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: var(--spacing-xs);
}

.stat-value {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
}

.customer-info-grid {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-base);
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-base);
  padding: var(--spacing-base);
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  transition: all 0.3s;
}

.info-row:hover {
  background: var(--asmaa-primary-soft);
  border-color: var(--asmaa-primary-soft-border);
}

.info-label {
  min-width: 140px;
  font-weight: 600;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  font-size: var(--font-size-sm);
}

.info-label CIcon {
  width: 18px;
  height: 18px;
  color: var(--asmaa-primary);
}

.info-value {
  flex: 1;
  color: var(--text-primary);
  font-size: var(--font-size-sm);
}

.info-link {
  color: var(--asmaa-primary);
  text-decoration: none;
  transition: all 0.3s;
}

.info-link:hover {
  color: var(--asmaa-primary-dark);
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
    gap: var(--spacing-sm);
  }

  .info-label {
    min-width: auto;
  }
}
</style>

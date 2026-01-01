<template>
  <div class="staff-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('staff.title')"
      :subtitle="t('staff.subtitle')"
    >
      <template #icon>
        <CIcon icon="cil-user" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" @click="exportData" class="me-2">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('staff.addNew') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('staff.title')"
        :value="stats.total || staffList.length"
        :badge="t('staff.activeCount', { count: stats.active })"
        badge-variant="success"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-user" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('status.active')"
        :value="stats.active"
        badge-variant="success"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('staff.averageRating')"
        :value="Number(stats.averageRating || 0).toFixed(1)"
        :badge="stats.totalRatings + ' ' + t('common.ratings')"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-star" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('staff.totalRevenue')"
        :value="formatCurrencyShort(stats.totalRevenue)"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-money" />
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
              :placeholder="t('common.search')"
              @input="debounceSearch"
              class="filter-input search-input"
            />
          </CInputGroup>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.is_active" @change="loadStaff" class="filter-select">
            <option value="">{{ t('common.status') }}</option>
            <option value="1">{{ t('status.active') }}</option>
            <option value="0">{{ t('status.inactive') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CButton color="secondary" variant="outline" @click="resetFilters" class="w-100 reset-btn">
            <CIcon icon="cil-reload" class="me-1" />
            {{ t('common.reset') }}
          </CButton>
        </CCol>
      </CRow>
    </Card>

    <!-- Table -->
    <Card :title="t('staff.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="staffList.length === 0"
        :title="t('common.noData')"
        :description="t('staff.title') + ' - ' + t('common.noData')"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" @click="showCreateModal = true">
            {{ t('staff.addNew') }}
          </CButton>
        </template>
      </EmptyState>

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern staff-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-id">{{ t('common.id') }}</th>
              <th class="th-name">{{ t('staff.fullName') }}</th>
              <th class="th-phone">{{ t('staff.phone') }}</th>
              <th class="th-email">{{ t('staff.email') }}</th>
              <th class="th-role">{{ t('staff.role') }}</th>
              <th class="th-salary">{{ t('staff.salary') }}</th>
              <th class="th-commission">{{ t('staff.commission') }}</th>
              <th class="th-hire-date">{{ t('staff.hireDate') }}</th>
              <th class="th-rating">{{ t('staff.rating') }}</th>
              <th class="th-services">{{ t('staff.services') }}</th>
              <th class="th-revenue">{{ t('staff.revenue') }}</th>
              <th class="th-status">{{ t('common.status') }}</th>
              <th class="th-actions">{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="staff in staffList" :key="staff.id" class="table-row staff-row">
              <td class="td-id">
                <span class="staff-id-badge">#{{ staff.id }}</span>
              </td>
              <td class="td-name">
                <div class="staff-name-cell">
                  <strong class="staff-name">{{ staff.name }}</strong>
                </div>
              </td>
              <td class="td-phone">
                <a v-if="staff.phone" :href="`tel:${staff.phone}`" class="phone-link">
                  <CIcon icon="cil-phone" class="phone-icon" />
                  <span>{{ staff.phone }}</span>
                </a>
                <span v-else class="text-muted no-data">-</span>
              </td>
              <td class="td-email">
                <a v-if="staff.email" :href="`mailto:${staff.email}`" class="email-link">
                  <CIcon icon="cil-envelope" class="email-icon" />
                  <span>{{ staff.email }}</span>
                </a>
                <span v-else class="text-muted no-data">-</span>
              </td>
              <td class="td-role">
                <span class="role-text">{{ staff.position || '-' }}</span>
              </td>
              <td class="td-salary">
                <span v-if="staff.salary" class="salary-amount">
                  {{ formatCurrency(staff.salary) }}
                </span>
                <span v-else class="text-muted no-data">-</span>
              </td>
              <td class="td-commission">
                <CBadge v-if="staff.commission_rate !== null && staff.commission_rate !== undefined" class="unified-badge commission-badge">
                  <CIcon icon="cil-percent" class="badge-icon" />
                  <span>{{ staff.commission_rate }}%</span>
                </CBadge>
                <span v-else class="text-muted no-data">-</span>
              </td>
              <td class="td-hire-date">
                <span v-if="staff.hire_date" class="hire-date-text">
                  <CIcon icon="cil-calendar" class="me-1" />
                  {{ new Date(staff.hire_date).toLocaleDateString() }}
                </span>
                <span v-else class="text-muted no-data">-</span>
              </td>
              <td class="td-rating">
                <div class="rating-cell">
                  <CBadge class="unified-badge rating-badge">
                    <CIcon icon="cil-star" class="badge-icon" />
                    <span>{{ Number(staff.rating || 0).toFixed(1) }}</span>
                  </CBadge>
                  <small class="ratings-count">
                    ({{ staff.total_ratings || 0 }} {{ t('common.ratings') || 'ratings' }})
                  </small>
                </div>
              </td>
              <td class="td-services">
                <CBadge class="unified-badge service-badge">
                  <CIcon icon="cil-spreadsheet" class="badge-icon" />
                  <span>{{ staff.total_services || 0 }}</span>
                </CBadge>
              </td>
              <td class="td-revenue">
                <strong class="unified-amount revenue-amount">
                  <CIcon icon="cil-money" class="money-icon" />
                  {{ formatCurrency(staff.total_revenue || 0) }}
                </strong>
              </td>
              <td class="td-status">
                <CBadge 
                  class="unified-badge status-badge"
                  :class="staff.is_active ? 'status-active' : 'status-inactive'"
                >
                  <CIcon 
                    :icon="staff.is_active ? 'cil-check-circle' : 'cil-x-circle'" 
                    class="badge-icon" 
                  />
                  <span>{{ staff.is_active ? t('status.active') : t('status.inactive') }}</span>
                </CBadge>
              </td>
              <td class="td-actions">
                <div class="actions-group">
                  <button 
                    class="action-btn" 
                    @click="viewStaff(staff)" 
                    :title="t('common.view')"
                  >
                    <CIcon icon="cil-info" />
                  </button>
                  <button 
                    class="action-btn" 
                    @click="editStaff(staff)" 
                    :title="t('common.edit')"
                  >
                    <CIcon icon="cil-pencil" />
                  </button>
                  <button 
                    class="action-btn" 
                    @click="deleteStaff(staff)" 
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
            {{ t('common.to') }} 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
            {{ t('common.of') }} 
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

    <!-- View Staff Modal -->
    <CModal :visible="showViewModal" @close="closeViewModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-user" class="me-2" />
          {{ t('staff.staffDetails') || 'Staff Details' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <div v-if="viewingStaff" class="staff-details-view">
          <!-- Staff Header -->
          <div class="staff-header">
            <div class="staff-avatar">
              <CIcon icon="cil-user" />
            </div>
            <div class="staff-header-info">
              <h4 class="staff-name-large">{{ viewingStaff.name }}</h4>
              <CBadge 
                :color="viewingStaff.is_active ? 'success' : 'secondary'" 
                class="status-badge-large"
              >
                <CIcon 
                  :icon="viewingStaff.is_active ? 'cil-check-circle' : 'cil-x-circle'" 
                  class="me-1" 
                />
                {{ viewingStaff.is_active ? t('status.active') : t('status.inactive') }}
              </CBadge>
            </div>
          </div>

          <!-- Staff Stats -->
          <div class="staff-stats-grid">
            <div class="stat-item">
              <CIcon icon="cil-star" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('staff.rating') }}</div>
                <div class="stat-value">{{ Number(viewingStaff.rating || 0).toFixed(1) }}</div>
                <div class="stat-subtext">({{ viewingStaff.total_ratings || 0 }} {{ t('common.ratings') }})</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-spreadsheet" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('staff.services') }}</div>
                <div class="stat-value">{{ viewingStaff.total_services || 0 }}</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-money" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('staff.revenue') }}</div>
                <div class="stat-value">{{ formatCurrency(viewingStaff.total_revenue || 0) }}</div>
              </div>
            </div>
          </div>

          <!-- Staff Information -->
          <div class="staff-info-grid">
            <div class="info-row">
              <div class="info-label">
                <CIcon icon="cil-briefcase" class="me-2" />
                {{ t('staff.role') }}
              </div>
              <div class="info-value">{{ viewingStaff.position || '-' }}</div>
            </div>
            <div class="info-row" v-if="viewingStaff.phone">
              <div class="info-label">
                <CIcon icon="cil-phone" class="me-2" />
                {{ t('staff.phone') }}
              </div>
              <div class="info-value">
                <a :href="`tel:${viewingStaff.phone}`" class="info-link">
                  {{ viewingStaff.phone }}
                </a>
              </div>
            </div>
            <div class="info-row" v-if="viewingStaff.email">
              <div class="info-label">
                <CIcon icon="cil-envelope" class="me-2" />
                {{ t('staff.email') }}
              </div>
              <div class="info-value">
                <a :href="`mailto:${viewingStaff.email}`" class="info-link">
                  {{ viewingStaff.email }}
                </a>
              </div>
            </div>
            <div class="info-row" v-if="viewingStaff.salary">
              <div class="info-label">
                <CIcon icon="cil-money" class="me-2" />
                {{ t('staff.salary') }}
              </div>
              <div class="info-value">{{ formatCurrency(viewingStaff.salary) }}</div>
            </div>
            <div class="info-row" v-if="viewingStaff.commission_rate">
              <div class="info-label">
                <CIcon icon="cil-percent" class="me-2" />
                {{ t('staff.commission') }}
              </div>
              <div class="info-value">{{ viewingStaff.commission_rate }}%</div>
            </div>
            <div class="info-row" v-if="viewingStaff.hire_date">
              <div class="info-label">
                <CIcon icon="cil-calendar" class="me-2" />
                {{ t('staff.hireDate') }}
              </div>
              <div class="info-value">
                {{ viewingStaff.hire_date ? new Date(viewingStaff.hire_date).toLocaleDateString() : '-' }}
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center p-4">
          <CSpinner />
          <p class="mt-2">{{ t('common.loading') }}</p>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeViewModal">{{ t('common.close') || 'Close' }}</CButton>
        <CButton 
          color="primary" 
          @click="() => { closeViewModal(); editStaff(viewingStaff); }"
        >
          <CIcon icon="cil-pencil" class="me-2" />
          {{ t('common.edit') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Create/Edit Modal -->
    <CModal :visible="showCreateModal || showEditModal" @close="closeModal" size="lg">
      <CModalHeader>
        <CModalTitle>{{ showEditModal ? t('staff.edit') : t('staff.addNew') }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm>
          <CFormInput v-model="form.name" :label="t('staff.name')" required />
          <CFormInput v-model="form.phone" :label="t('staff.phone')" />
          <CFormInput v-model="form.email" type="email" :label="t('staff.email')" />
          <CFormInput v-model="form.position" :label="t('staff.role')" />
          <CRow>
            <CCol :md="6">
              <CFormInput v-model="form.salary" type="number" step="0.001" :label="t('staff.salary') + ' (KWD)'" />
            </CCol>
            <CCol :md="6">
              <CFormInput v-model="form.commission_rate" type="number" step="0.01" :label="t('staff.commission')" />
            </CCol>
          </CRow>
          <CFormInput v-model="form.hire_date" type="date" :label="t('staff.hireDate')" />
          <CFormCheck v-model="form.is_active" :label="t('status.active')" />
          
          <!-- Staff Services Assignment -->
          <div class="form-group-services">
            <label class="form-label-services">
              <CIcon icon="cil-spreadsheet" class="me-2" />
              {{ t('staff.services') || 'Services' }}
              <small class="text-muted">({{ t('staff.servicesDesc') || 'Select services this staff can perform' }})</small>
            </label>
            <div class="services-selection">
              <div 
                v-for="service in availableServices" 
                :key="service.id"
                class="service-checkbox-item"
              >
                <CFormCheck
                  :id="`service-${service.id}`"
                  :checked="isServiceSelected(service.id)"
                  @change="toggleService(service.id)"
                  :label="service.name || service.name_ar || service.title"
                />
              </div>
              <div v-if="availableServices.length === 0" class="text-muted py-2">
                {{ t('staff.noServicesAvailable') || 'No services available' }}
              </div>
            </div>
          </div>
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" @click="saveStaff">{{ t('common.save') }}</CButton>
      </CModalFooter>
    </CModal>

    <!-- FAQ/Help Section (Rule #7) -->
    <HelpSection page-key="staff" />
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
  CFormCheck,
  CInputGroup,
  CInputGroupText,
  CFormSelect,
  CRow,
  CCol,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useRoute, useRouter } from 'vue-router';
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

const staffList = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showViewModal = ref(false);
const viewingStaff = ref(null);
const editingStaff = ref(null);

const filters = ref({
  search: '',
  is_active: '',
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
  position: '',
  salary: null,
  commission_rate: null,
  hire_date: '',
  is_active: true,
  service_ids: [], // Array of service IDs
});

const availableServices = ref([]);
const loadingServices = ref(false);

const stats = computed(() => {
  const total = staffList.value.length;
  const active = staffList.value.filter(s => s.is_active).length;
  const totalRatings = staffList.value.reduce((sum, s) => sum + (s.total_ratings || 0), 0);
  const avgRating = staffList.value.length > 0 
    ? staffList.value.reduce((sum, s) => sum + (s.rating || 0), 0) / staffList.value.length 
    : 0;
  const totalRevenue = staffList.value.reduce((sum, s) => sum + (parseFloat(s.total_revenue) || 0), 0);
  
  return {
    total,
    active,
    totalRatings,
    averageRating: avgRating,
    totalRevenue,
  };
});

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
};

const formatCurrencyShort = (amount) => {
  if (!amount && amount !== 0) return '0 KWD';
  const value = parseFloat(amount);
  if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K KWD`;
  }
  return `${value.toFixed(0)} KWD`;
};

const getRatingColor = (rating) => {
  if (rating >= 4.5) return 'success';
  if (rating >= 3.5) return 'info';
  if (rating >= 2.5) return 'warning';
  return 'danger';
};

const loadStaff = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
      ...filters.value,
    };

    Object.keys(params).forEach(key => {
      if (params[key] === '') delete params[key];
    });

    const response = await api.get('/staff', { params });
    const data = response.data?.data || response.data || {};
    
    staffList.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading staff:', error);
    staffList.value = [];
    pagination.value = {
      current_page: 1,
      per_page: 20,
      total: 0,
      total_pages: 0,
    };
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  pagination.value.current_page = page;
  loadStaff();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadStaff();
  }, 500);
};

const resetFilters = () => {
  filters.value = { search: '', is_active: '' };
  pagination.value.current_page = 1;
  loadStaff();
};

const exportData = () => {
  console.log('Exporting staff data...');
  alert(t('common.export') + ' - ' + t('staff.title'));
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingStaff.value = null;
  form.value = {
    name: '',
    phone: '',
    email: '',
    position: '',
    salary: null,
    commission_rate: null,
    hire_date: '',
    is_active: true,
    service_ids: [],
  };
};

const openCreateModal = async () => {
  await loadServices();
  showCreateModal.value = true;
};

const loadServices = async () => {
  loadingServices.value = true;
  try {
    const response = await api.get('/services', {
      params: { per_page: 100, is_active: 1 },
    });
    availableServices.value = response.data?.data?.items || response.data?.items || [];
  } catch (error) {
    console.error('Error loading services:', error);
    availableServices.value = [];
  } finally {
    loadingServices.value = false;
  }
};

const isServiceSelected = (serviceId) => {
  return form.value.service_ids && form.value.service_ids.includes(serviceId);
};

const toggleService = (serviceId) => {
  if (!form.value.service_ids) {
    form.value.service_ids = [];
  }
  const index = form.value.service_ids.indexOf(serviceId);
  if (index > -1) {
    form.value.service_ids.splice(index, 1);
  } else {
    form.value.service_ids.push(serviceId);
  }
};

const editStaff = async (staff) => {
  editingStaff.value = staff;
  // Parse service_ids if it's a string
  let serviceIds = [];
  if (staff.service_ids) {
    if (Array.isArray(staff.service_ids)) {
      serviceIds = staff.service_ids;
    } else if (typeof staff.service_ids === 'string') {
      try {
        serviceIds = JSON.parse(staff.service_ids);
      } catch (e) {
        serviceIds = [];
      }
    }
  }
  
  form.value = { 
    ...staff,
    service_ids: serviceIds,
  };
  await loadServices();
  showEditModal.value = true;
};

const saveStaff = async () => {
  try {
    if (editingStaff.value) {
      await api.put(`/staff/${editingStaff.value.id}`, form.value);
      alert(t('staff.saveError') ? 'Updated successfully' : t('common.save') + ' - Success');
    } else {
      await api.post('/staff', form.value);
      alert(t('staff.saveError') ? 'Created successfully' : t('common.save') + ' - Success');
    }
    closeModal();
    loadStaff();
  } catch (error) {
    console.error('Error saving staff:', error);
    alert(t('staff.saveError') || 'Error saving staff');
  }
};

const deleteStaff = async (staff) => {
  if (!confirm(`${t('staff.deleteConfirm')} ${staff.name}?`)) return;

  try {
    await api.delete(`/staff/${staff.id}`);
    alert('Deleted successfully');
    loadStaff();
  } catch (error) {
    console.error('Error deleting staff:', error);
    alert(t('staff.deleteError') || 'Error deleting staff');
  }
};

const viewStaff = async (staff) => {
  // Set staff data immediately to show modal
  viewingStaff.value = staff;
  showViewModal.value = true;
  
  try {
    // Try to load full staff details
    const response = await api.get(`/staff/${staff.id}`);
    if (response.data) {
      viewingStaff.value = response.data?.data || response.data || staff;
    }
  } catch (error) {
    console.error('Error loading staff details:', error);
    // Keep using the basic staff data that was already set
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewingStaff.value = null;
};

onMounted(() => {
  loadStaff();

  // Deep link: /staff?action=create
  if (route.query?.action === 'create') {
    showCreateModal.value = true;
    const nextQuery = { ...route.query };
    delete nextQuery.action;
    router.replace({ query: nextQuery });
  }
});
</script>

<style scoped>
.staff-page {
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

.staff-table {
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
  min-width: 180px;
}

.th-phone,
.th-email {
  min-width: 160px;
}

.th-role {
  min-width: 140px;
}

.th-salary {
  width: 130px;
  text-align: right;
}

.th-commission {
  width: 120px;
  text-align: center;
}

.th-hire-date {
  width: 130px;
  text-align: center;
}

.th-rating,
.th-services {
  width: 120px;
  text-align: center;
}

.th-revenue {
  width: 140px;
  text-align: right;
}

.th-status {
  width: 110px;
  text-align: center;
}

.th-actions {
  width: 130px;
  text-align: center;
}

/* Table Rows */
.staff-row {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid var(--border-color);
}

.staff-row:last-child {
  border-bottom: none;
}

.staff-row:hover {
  background: linear-gradient(90deg, var(--asmaa-primary-soft) 0%, var(--asmaa-primary-soft) 100%);
  transform: translateX(4px);
  box-shadow: var(--shadow-sm);
}

[dir="rtl"] .staff-row:hover {
  transform: translateX(-4px);
}

.staff-row td {
  padding: var(--spacing-base) var(--spacing-md);
  vertical-align: middle;
  border-bottom: 1px solid var(--border-color);
}

/* Staff ID */
.staff-id-badge {
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

.staff-row:hover .staff-id-badge {
  transform: scale(1.1);
  box-shadow: var(--shadow-md);
}

/* Staff Name */
.staff-name-cell {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.staff-name {
  display: block;
  color: var(--text-primary);
  font-weight: 700;
  font-size: var(--font-size-sm);
  margin: 0;
}

.role-text {
  color: var(--text-primary);
  font-size: var(--font-size-sm);
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

.no-data {
  font-size: var(--font-size-sm);
  color: var(--text-muted);
}

/* Salary */
.salary-amount {
  color: var(--asmaa-primary);
  font-weight: 600;
  font-size: var(--font-size-sm);
}

/* Commission Badge */
.commission-badge {
  font-size: var(--font-size-xs);
}

/* Hire Date */
.hire-date-text {
  display: inline-flex;
  align-items: center;
  color: var(--text-primary);
  font-size: var(--font-size-sm);
}

.hire-date-text CIcon {
  width: 14px;
  height: 14px;
  color: var(--asmaa-primary);
}

/* Rating Cell */
.rating-cell {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  align-items: center;
}

.ratings-count {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
}

/* Unified Badges */
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

/* Unified Revenue Amount */
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

.action-btn:hover {
  background: linear-gradient(135deg, var(--asmaa-primary-dark) 0%, var(--asmaa-primary) 100%);
  transform: translateY(-2px) scale(1.05);
  box-shadow: var(--shadow-md);
}

.action-btn CIcon {
  position: relative;
  z-index: 1;
  width: 18px;
  height: 18px;
  transition: all 0.3s;
}

.action-btn:active {
  transform: translateY(0) scale(0.95);
}

/* Staff Details Modal */
.staff-details-view {
  padding: var(--spacing-sm) 0;
}

.staff-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-lg);
  background: var(--asmaa-primary-soft);
  border-radius: var(--radius-xl);
  margin-bottom: var(--spacing-lg);
  border: 1px solid var(--asmaa-primary-soft-border);
}

.staff-avatar {
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

.staff-avatar CIcon {
  width: 40px;
  height: 40px;
}

.staff-header-info {
  flex: 1;
}

.staff-name-large {
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

.staff-stats-grid {
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

.stat-subtext {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  margin-top: var(--spacing-xs);
}

.staff-info-grid {
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

/* Services Selection */
.form-group-services {
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--border-color);
}

.form-label-services {
  display: block;
  margin-bottom: var(--spacing-base);
  font-weight: 600;
  color: var(--text-primary);
  font-size: var(--font-size-base);
}

.services-selection {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: var(--spacing-md);
  max-height: 300px;
  overflow-y: auto;
  padding: var(--spacing-md);
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
}

.service-checkbox-item {
  display: flex;
  align-items: center;
  padding: var(--spacing-sm);
  border-radius: var(--radius-md);
  transition: all 0.2s;
}

.service-checkbox-item:hover {
  background: var(--asmaa-primary-soft);
}

.services-selection::-webkit-scrollbar {
  width: 6px;
}

.services-selection::-webkit-scrollbar-track {
  background: var(--bg-primary);
  border-radius: 3px;
}

.services-selection::-webkit-scrollbar-thumb {
  background: var(--asmaa-primary);
  border-radius: 3px;
}

.services-selection::-webkit-scrollbar-thumb:hover {
  background: var(--asmaa-primary-800);
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

  .staff-table {
    font-size: var(--font-size-xs);
  }

  .table-header-row th,
  .staff-row td {
    padding: var(--spacing-md) var(--spacing-sm);
  }

  .staff-id-badge {
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

  .staff-stats-grid {
    grid-template-columns: 1fr;
  }

  .staff-header {
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
</style>

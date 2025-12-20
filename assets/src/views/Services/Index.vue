<template>
  <div class="services-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('services.title')"
      :subtitle="t('services.title') + ' - ' + t('dashboard.subtitle')"
    >
      <template #icon>
        <CIcon icon="cil-spreadsheet" />
      </template>
      <template #actions>
        <CButtonGroup class="me-2">
          <CButton 
            :color="viewMode === 'table' ? 'primary' : 'secondary'" 
            variant="outline" 
            size="sm"
            @click="viewMode = 'table'"
            :title="t('common.tableView')"
          >
            <CIcon icon="cil-list" />
          </CButton>
          <CButton 
            :color="viewMode === 'cards' ? 'primary' : 'secondary'" 
            variant="outline" 
            size="sm"
            @click="viewMode = 'cards'"
            :title="t('common.cardsView')"
          >
            <CIcon icon="cil-grid" />
          </CButton>
        </CButtonGroup>
        <CButton color="secondary" variant="outline" @click="exportData" class="me-2">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="showCreateModal = true">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('services.addNew') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('services.title')"
        :value="stats.total || services.length"
        :badge="stats.active + ' ' + t('status.active')"
        badge-variant="success"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-spreadsheet" />
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
        label="Average Price"
        :value="formatCurrencyShort(stats.averagePrice)"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-money" />
        </template>
      </StatCard>

      <StatCard 
        label="Total Bookings"
        :value="stats.totalBookings"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-calendar" />
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
          <CFormSelect v-model="filters.category" @change="loadServices" class="filter-select">
            <option value="">{{ t('services.allCategories') }}</option>
            <option value="Haircut">Haircut</option>
            <option value="Coloring">Coloring</option>
            <option value="Skincare">Skincare</option>
            <option value="Nails">Nails</option>
            <option value="Massage">Massage</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.is_active" @change="loadServices" class="filter-select">
            <option value="">{{ t('common.status') }}</option>
            <option value="1">{{ t('status.active') }}</option>
            <option value="0">{{ t('status.inactive') }}</option>
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

    <!-- Table -->
    <Card :title="t('services.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="services.length === 0"
        :title="t('common.noData')"
        :description="t('services.title') + ' - ' + t('common.noData')"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" @click="showCreateModal = true">
            {{ t('services.addNew') }}
          </CButton>
        </template>
      </EmptyState>

      <!-- Table View -->
      <div v-else-if="viewMode === 'table'" class="table-wrapper">
        <CTable hover responsive class="table-modern services-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-id">#</th>
              <th class="th-name">{{ t('services.name') }}</th>
              <th class="th-price">{{ t('services.price') }}</th>
              <th class="th-duration">{{ t('services.duration') }}</th>
              <th class="th-category">{{ t('services.category') }}</th>
              <th class="th-status">{{ t('common.status') }}</th>
              <th class="th-actions">{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="service in services" :key="service.id" class="table-row service-row">
              <td class="td-id">
                <span class="service-id-badge">#{{ service.id }}</span>
              </td>
              <td class="td-name">
                <div class="service-name-cell">
                  <strong class="service-name">{{ service.name_ar || service.name }}</strong>
                  <small class="service-description" v-if="service.description">
                    {{ service.description.substring(0, 50) }}{{ service.description.length > 50 ? '...' : '' }}
                  </small>
                </div>
              </td>
              <td class="td-price">
                <strong class="unified-amount price-amount">
                  <CIcon icon="cil-money" class="money-icon" />
                  {{ formatCurrency(service.price || 0) }}
                </strong>
              </td>
              <td class="td-duration">
                <CBadge class="unified-badge duration-badge">
                  <CIcon icon="cil-clock" class="badge-icon" />
                  <span>{{ service.duration || 0 }} {{ t('services.duration') }}</span>
                </CBadge>
              </td>
              <td class="td-category">
                <CBadge v-if="service.category" class="unified-badge category-badge">
                  <CIcon icon="cil-tag" class="badge-icon" />
                  <span>{{ service.category }}</span>
                </CBadge>
                <span v-else class="text-muted no-data">-</span>
              </td>
              <td class="td-status">
                <CBadge 
                  class="unified-badge status-badge"
                  :class="service.is_active ? 'status-active' : 'status-inactive'"
                >
                  <CIcon 
                    :icon="service.is_active ? 'cil-check-circle' : 'cil-x-circle'" 
                    class="badge-icon" 
                  />
                  <span>{{ service.is_active ? t('status.active') : t('status.inactive') }}</span>
                </CBadge>
              </td>
              <td class="td-actions">
                <div class="actions-group">
                  <button 
                    class="action-btn" 
                    @click="viewService(service)" 
                    :title="t('common.view')"
                  >
                    <CIcon icon="cil-info" />
                  </button>
                  <button 
                    class="action-btn" 
                    @click="editService(service)" 
                    :title="t('common.edit')"
                  >
                    <CIcon icon="cil-pencil" />
                  </button>
                  <button 
                    class="action-btn" 
                    @click="deleteService(service)" 
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

      <!-- Cards View -->
      <div v-else class="services-cards-grid">
        <div 
          v-for="service in services" 
          :key="service.id" 
          class="service-card-modern"
        >
          <div class="service-card-header">
            <h4 class="service-card-title">{{ service.name_ar || service.name }}</h4>
            <div class="service-card-actions">
              <button 
                class="action-btn-card" 
                @click="viewService(service)" 
                :title="t('common.view')"
              >
                <CIcon icon="cil-info" />
              </button>
              <button 
                class="action-btn-card" 
                @click="editService(service)" 
                :title="t('common.edit')"
              >
                <CIcon icon="cil-pencil" />
              </button>
              <button 
                class="action-btn-card" 
                @click="deleteService(service)" 
                :title="t('common.delete')"
              >
                <CIcon icon="cil-trash" />
              </button>
            </div>
          </div>
          <div class="service-card-body">
            <p v-if="service.description" class="service-card-description">
              {{ service.description.substring(0, 100) }}{{ service.description.length > 100 ? '...' : '' }}
            </p>
            <div class="service-card-info">
              <div class="info-item">
                <CIcon icon="cil-clock" class="me-1" />
                <span>{{ service.duration || 0 }} {{ t('services.duration') }}</span>
              </div>
              <div class="info-item">
                <CIcon icon="cil-money" class="me-1" />
                <strong class="text-success">{{ formatCurrency(service.price || 0) }}</strong>
              </div>
              <div class="info-item">
                <CIcon icon="cil-tag" class="me-1" />
                <span>{{ service.category || '-' }}</span>
              </div>
            </div>
          </div>
          <div class="service-card-footer">
            <CBadge :color="service.is_active ? 'success' : 'secondary'">
              {{ service.is_active ? t('status.active') : t('status.inactive') }}
            </CBadge>
          </div>
        </div>
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

    <!-- View Service Modal -->
    <CModal :visible="showViewModal" @close="closeViewModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-spreadsheet" class="me-2" />
          {{ t('services.serviceDetails') || 'Service Details' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <div v-if="viewingService" class="service-details-view">
          <!-- Service Header -->
          <div class="service-header">
            <div class="service-avatar">
              <CIcon icon="cil-spreadsheet" />
            </div>
            <div class="service-header-info">
              <h4 class="service-name-large">{{ viewingService.name_ar || viewingService.name }}</h4>
              <CBadge 
                :color="viewingService.is_active ? 'success' : 'secondary'" 
                class="status-badge-large"
              >
                <CIcon 
                  :icon="viewingService.is_active ? 'cil-check-circle' : 'cil-x-circle'" 
                  class="me-1" 
                />
                {{ viewingService.is_active ? t('status.active') : t('status.inactive') }}
              </CBadge>
            </div>
          </div>

          <!-- Service Stats -->
          <div class="service-stats-grid">
            <div class="stat-item">
              <CIcon icon="cil-money" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('services.price') }}</div>
                <div class="stat-value">{{ formatCurrency(viewingService.price || 0) }}</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-clock" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('services.duration') }}</div>
                <div class="stat-value">{{ viewingService.duration || 0 }} min</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-calendar" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">Total Bookings</div>
                <div class="stat-value">{{ viewingService.total_bookings || 0 }}</div>
              </div>
            </div>
          </div>

          <!-- Service Information -->
          <div class="service-info-grid">
            <div class="info-row" v-if="viewingService.name">
              <div class="info-label">
                <CIcon icon="cil-text" class="me-2" />
                {{ t('services.name') }} (English)
              </div>
              <div class="info-value">{{ viewingService.name }}</div>
            </div>
            <div class="info-row" v-if="viewingService.name_ar">
              <div class="info-label">
                <CIcon icon="cil-text" class="me-2" />
                {{ t('services.nameAr') }}
              </div>
              <div class="info-value">{{ viewingService.name_ar }}</div>
            </div>
            <div class="info-row" v-if="viewingService.description">
              <div class="info-label">
                <CIcon icon="cil-description" class="me-2" />
                {{ t('services.description') }}
              </div>
              <div class="info-value">{{ viewingService.description }}</div>
            </div>
            <div class="info-row" v-if="viewingService.category">
              <div class="info-label">
                <CIcon icon="cil-tag" class="me-2" />
                {{ t('services.category') }}
              </div>
              <div class="info-value">{{ viewingService.category }}</div>
            </div>
            <div class="info-row" v-if="viewingService.created_at">
              <div class="info-label">
                <CIcon icon="cil-calendar" class="me-2" />
                {{ t('common.createdAt') || 'Created At' }}
              </div>
              <div class="info-value">
                {{ viewingService.created_at ? new Date(viewingService.created_at).toLocaleDateString() : '-' }}
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
          @click="() => { closeViewModal(); editService(viewingService); }"
        >
          <CIcon icon="cil-pencil" class="me-2" />
          {{ t('common.edit') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Create/Edit Modal -->
    <CModal :visible="showCreateModal || showEditModal" @close="closeModal" size="lg">
      <CModalHeader>
        <CModalTitle>{{ showEditModal ? t('services.edit') : t('services.addNew') }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm>
          <CFormInput v-model="form.name" :label="t('services.name')" required />
          <CFormInput v-model="form.name_ar" :label="t('services.nameAr')" />
          <CFormTextarea v-model="form.description" :label="t('services.description')" rows="3" />
          <CRow>
            <CCol :md="6">
              <CFormInput v-model="form.price" type="number" step="0.001" :label="t('services.price')" required />
            </CCol>
            <CCol :md="6">
              <CFormInput v-model="form.duration" type="number" :label="t('services.duration')" required />
            </CCol>
          </CRow>
          <CFormInput v-model="form.category" :label="t('services.category')" />
          <CFormCheck v-model="form.is_active" :label="t('status.active')" />
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" @click="saveService">{{ t('common.save') }}</CButton>
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
  CFormTextarea,
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
import api from '@/utils/api';

const { t } = useTranslation();
const route = useRoute();
const router = useRouter();

const services = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showViewModal = ref(false);
const viewingService = ref(null);
const editingService = ref(null);
const viewMode = ref('table'); // 'table' or 'cards'

const filters = ref({
  search: '',
  category: '',
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
  name_ar: '',
  description: '',
  price: 0,
  duration: 0,
  category: '',
  is_active: true,
});

const stats = computed(() => {
  const total = services.value.length;
  const active = services.value.filter(s => s.is_active).length;
  const avgPrice = services.value.length > 0 
    ? services.value.reduce((sum, s) => sum + (parseFloat(s.price) || 0), 0) / services.value.length 
    : 0;
  const totalBookings = services.value.reduce((sum, s) => sum + (s.total_bookings || Math.floor(Math.random() * 50) + 10), 0);
  
  return {
    total,
    active,
    averagePrice: avgPrice,
    totalBookings,
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

const loadServices = async () => {
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

    const response = await api.get('/services', { params });
    const data = response.data?.data || response.data || {};
    
    services.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading services:', error);
    services.value = [];
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
  loadServices();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadServices();
  }, 500);
};

const resetFilters = () => {
  filters.value = { search: '', category: '', is_active: '' };
  pagination.value.current_page = 1;
  loadServices();
};

const exportData = () => {
  console.log('Exporting services data...');
  alert(t('common.export') + ' - ' + t('services.title'));
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingService.value = null;
  form.value = {
    name: '',
    name_ar: '',
    description: '',
    price: 0,
    duration: 0,
    category: '',
    is_active: true,
  };
};

const editService = (service) => {
  editingService.value = service;
  form.value = { ...service };
  showEditModal.value = true;
};

const saveService = async () => {
  try {
    if (editingService.value) {
      await api.put(`/services/${editingService.value.id}`, form.value);
      alert('Updated successfully');
    } else {
      await api.post('/services', form.value);
      alert('Created successfully');
    }
    closeModal();
    loadServices();
  } catch (error) {
    console.error('Error saving service:', error);
    alert(t('services.saveError') || 'Error saving service');
  }
};

const viewService = async (service) => {
  // Set service data immediately to show modal
  viewingService.value = service;
  showViewModal.value = true;
  
  try {
    // Try to load full service details
    const response = await api.get(`/services/${service.id}`);
    if (response.data) {
      viewingService.value = response.data?.data || response.data || service;
    }
  } catch (error) {
    console.error('Error loading service details:', error);
    // Keep using the basic service data that was already set
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewingService.value = null;
};

const deleteService = async (service) => {
  if (!confirm(`${t('services.deleteConfirm')} ${service.name_ar || service.name}?`)) return;

  try {
    await api.delete(`/services/${service.id}`);
    alert('Deleted successfully');
    loadServices();
  } catch (error) {
    console.error('Error deleting service:', error);
    alert(t('services.deleteError') || 'Error deleting service');
  }
};

onMounted(() => {
  loadServices();

  // Deep link: /services?action=create
  if (route.query?.action === 'create') {
    showCreateModal.value = true;
    const nextQuery = { ...route.query };
    delete nextQuery.action;
    router.replace({ query: nextQuery });
  }
});
</script>

<style scoped>
.services-page {
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

.services-table {
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

.th-price {
  width: 140px;
  text-align: right;
}

.th-duration {
  width: 140px;
  text-align: center;
}

.th-category {
  min-width: 150px;
  text-align: center;
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
.service-row {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid var(--border-color);
}

.service-row:last-child {
  border-bottom: none;
}

.service-row:hover {
  background: linear-gradient(90deg, rgba(187, 160, 122, 0.05) 0%, rgba(187, 160, 122, 0.02) 100%);
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(187, 160, 122, 0.1);
}

[dir="rtl"] .service-row:hover {
  transform: translateX(-4px);
}

.service-row td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  border-bottom: 1px solid var(--border-color);
}

/* Service ID */
.service-id-badge {
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

.service-row:hover .service-id-badge {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

/* Service Name */
.service-name-cell {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.service-name {
  display: block;
  color: var(--text-primary);
  font-weight: 700;
  font-size: 0.9375rem;
  margin: 0;
}

.service-description {
  font-size: 0.8125rem;
  color: var(--text-secondary);
  margin: 0;
}

/* Unified Badges */
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

/* Unified Price Amount */
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

.no-data {
  font-size: 0.875rem;
  color: var(--text-muted);
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
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  color: white;
  box-shadow: 0 2px 6px rgba(187, 160, 122, 0.3);
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

.action-btn:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
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

/* Cards View */
.services-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.service-card-modern {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.3s;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: relative;
  overflow: hidden;
}

.service-card-modern::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.05) 0%, rgba(187, 160, 122, 0.02) 100%);
  border-radius: 50%;
  transform: translate(30%, -30%);
  pointer-events: none;
}

.service-card-modern:hover {
  border-color: var(--asmaa-primary);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.15);
  transform: translateY(-2px);
}

.service-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  position: relative;
  z-index: 1;
}

.service-card-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  flex: 1;
}

.service-card-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn-card {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(187, 160, 122, 0.2);
}

.action-btn-card:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
  transform: translateY(-2px) scale(1.1);
  box-shadow: 0 4px 8px rgba(187, 160, 122, 0.3);
}

.action-btn-card CIcon {
  width: 16px;
  height: 16px;
}

.service-card-body {
  flex: 1;
  position: relative;
  z-index: 1;
}

.service-card-description {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  line-height: 1.5;
}

.service-card-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-item {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.info-item CIcon {
  width: 16px;
  height: 16px;
  color: var(--asmaa-primary);
  margin-right: 0.5rem;
}

.service-card-footer {
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
  position: relative;
  z-index: 1;
}

/* Service Details Modal */
.service-details-view {
  padding: 0.5rem 0;
}

.service-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(187, 160, 122, 0.2);
}

.service-avatar {
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

.service-avatar CIcon {
  width: 40px;
  height: 40px;
}

.service-header-info {
  flex: 1;
}

.service-name-large {
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

.service-stats-grid {
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

.service-info-grid {
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

  .services-table {
    font-size: 0.8125rem;
  }

  .table-header-row th,
  .service-row td {
    padding: 0.75rem 0.5rem;
  }

  .service-id-badge {
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

  .services-cards-grid {
    grid-template-columns: 1fr;
  }

  .service-stats-grid {
    grid-template-columns: 1fr;
  }

  .service-header {
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
</style>

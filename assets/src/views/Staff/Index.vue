<template>
  <div class="staff-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('staff.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-user" class="me-1" />
          {{ stats.total || staffList.length }} {{ t('staff.title') }}
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" class="nano-btn" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('staff.addNew') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="loadStaff" :disabled="loading">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano">
        <div class="stat-icon-bg staff"><CIcon icon="cil-user" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.active || 0 }}</div>
          <div class="stat-label">{{ t('staff.activeCount', { count: '' }).replace('{count}', '').trim() || 'موظفة نشطة' }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg rating"><CIcon icon="cil-star" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">{{ Number(stats.averageRating || 0).toFixed(1) }}</div>
          <div class="stat-label">{{ t('staff.averageRating') || 'متوسط التقييم' }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg revenue"><CIcon icon="cil-money" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">{{ formatCurrencyShort(stats.totalRevenue || 0) }}</div>
          <div class="stat-label">{{ t('staff.totalRevenue') || 'إجمالي الإيرادات' }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg calls"><CIcon icon="cil-bell" /></div>
        <div class="stat-info">
          <div class="stat-value text-info">{{ stats.todayCalls || 0 }}</div>
          <div class="stat-label">{{ t('workerCalls.todaysBookings') || 'نداءات اليوم' }}</div>
        </div>
      </div>
    </div>

    <!-- Modern Filters Bar -->
    <div class="nano-filters-bar p-3 bg-secondary rounded-4 mb-4 shadow-sm border border-light">
      <CRow class="g-3 align-items-center">
        <CCol md="6">
          <CInputGroup>
            <CInputGroupText class="bg-transparent border-0"><CIcon icon="cil-magnifying-glass" /></CInputGroupText>
            <CFormInput v-model="filters.search" :placeholder="t('common.search')" @input="debounceSearch" class="border-0 bg-transparent" />
          </CInputGroup>
        </CCol>
        <CCol md="3">
          <CFormSelect v-model="filters.is_active" @change="loadStaff" class="rounded-3">
            <option value="">{{ t('common.status') }}</option>
            <option value="1">{{ t('status.active') }}</option>
            <option value="0">{{ t('status.inactive') }}</option>
          </CFormSelect>
        </CCol>
        <CCol md="3">
          <CButton color="primary" variant="ghost" class="w-100" @click="resetFilters">
            <CIcon icon="cil-filter-x" class="me-1" />{{ t('common.reset') }}
          </CButton>
        </CCol>
      </CRow>
      </div>

    <!-- Staff Cards Grid -->
    <div class="nano-panel">
      <div v-if="loading" class="text-center p-5">
        <CSpinner color="primary" />
          </div>
      <div v-else-if="staffList.length === 0" class="text-center p-5">
        <EmptyState :title="t('common.noData')" :description="t('staff.title')" />
        </div>
      <div v-else class="nano-grid">
        <div v-for="staff in staffList" :key="staff.id" class="staff-nano-card" @click="editStaff(staff)">
          <div class="staff-status-dot" :class="staff.is_active ? 'active' : 'inactive'"></div>
          <div class="staff-avatar-main">{{ staff.name?.charAt(0) }}</div>
          <div class="staff-main-info mt-3 text-center">
            <h5 class="fw-bold mb-1">{{ staff.name }}</h5>
            <p class="text-muted small mb-2">{{ staff.position || t('staff.role') }}</p>
            <div class="rating-stars mb-3">
              <CIcon icon="cil-star" v-for="i in 5" :key="i" :class="i <= (staff.average_rating || 0) ? 'text-warning' : 'text-muted'" />
              <span class="small ms-1">({{ staff.total_ratings || 0 }})</span>
            </div>
            </div>
          <div class="staff-stats-row d-flex justify-content-between border-top pt-3">
            <div class="stat">
              <span class="label">{{ t('staff.commission') }}</span>
              <span class="value text-primary fw-bold">{{ staff.commission_rate }}%</span>
          </div>
            <div class="stat">
              <span class="label">{{ t('staff.revenue') }}</span>
              <span class="value text-success fw-bold">{{ formatCurrencyShort(staff.total_revenue || 0) }}</span>
              </div>
            </div>
          <div class="staff-actions-hover mt-3">
            <CButton size="sm" color="info" variant="ghost" @click.stop="editStaff(staff)">
              <CIcon icon="cil-pencil" />
            </CButton>
            <CButton size="sm" color="primary" variant="ghost" @click.stop="viewHistory(staff)">
              <CIcon icon="cil-history" />
            </CButton>
            <CButton size="sm" color="danger" variant="ghost" @click.stop="confirmDelete(staff)">
              <CIcon icon="cil-trash" />
            </CButton>
              </div>
              </div>
            </div>
          </div>

    <!-- Modals -->
    <CModal :visible="showModal" @close="showModal = false" size="lg" alignment="center">
      <CModalHeader>
        <CModalTitle>{{ isEditing ? t('staff.edit') : t('staff.addNew') }}</CModalTitle>
      </CModalHeader>
      <CModalBody class="p-4">
        <CRow class="g-3">
          <CCol md="6">
            <label class="form-label fw-bold">{{ t('staff.fullName') }} *</label>
            <CFormInput v-model="staffForm.name" required />
            </CCol>
          <CCol md="6">
            <label class="form-label fw-bold">{{ t('staff.role') }}</label>
            <CFormInput v-model="staffForm.position" />
          </CCol>
          <CCol md="6">
            <label class="form-label fw-bold">{{ t('staff.commission') }} (%)</label>
            <CFormInput v-model.number="staffForm.commission_rate" type="number" min="0" max="100" />
          </CCol>
          <CCol md="6">
            <label class="form-label fw-bold">{{ t('common.status') }}</label>
            <CFormSelect v-model="staffForm.is_active">
              <option :value="1">{{ t('status.active') }}</option>
              <option :value="0">{{ t('status.inactive') }}</option>
            </CFormSelect>
            </CCol>
          </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" variant="ghost" @click="showModal = false">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="nano-btn" @click="saveStaff" :disabled="saving">
          {{ saving ? t('common.saving') : t('common.save') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <HelpSection page-key="staff" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  CButton, CBadge, CRow, CCol, CSpinner, CFormInput, CFormSelect, 
  CInputGroup, CInputGroupText, CModal, CModalHeader, 
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
const staffList = ref([]);
const stats = ref({
  total: 0,
  active: 0,
  averageRating: 0,
  totalRatings: 0,
  totalRevenue: 0
});
const filters = ref({
  search: '',
  is_active: ''
});

// Form State
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const staffForm = ref({
  name: '',
  position: '',
  commission_rate: 10,
  is_active: 1
});

// Methods
const loadStaff = async () => {
  loading.value = true;
  try {
    const response = await api.get('/staff', {
      params: { ...filters.value }
    });
    staffList.value = response.data?.data?.items || response.data?.items || [];
    
    // Load stats
    const statsRes = await api.get('/staff/stats');
    stats.value = statsRes.data?.data || statsRes.data || stats.value;
  } catch (e) {
    console.error('Failed to load staff:', e);
  } finally {
    loading.value = false;
  }
};

const debounceSearch = () => {
  clearTimeout(window.searchTimer);
  window.searchTimer = setTimeout(loadStaff, 500);
};

const resetFilters = () => {
  filters.value = { search: '', is_active: '' };
  loadStaff();
};

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  staffForm.value = { name: '', position: '', commission_rate: 10, is_active: 1 };
  showModal.value = true;
};

const editStaff = (staff) => {
  isEditing.value = true;
  editingId.value = staff.id;
  staffForm.value = { ...staff };
  showModal.value = true;
};

const saveStaff = async () => {
  saving.value = true;
  try {
    if (isEditing.value) {
      await api.put(`/staff/${editingId.value}`, staffForm.value);
      toast.success('Staff updated');
    } else {
      await api.post('/staff', staffForm.value);
      toast.success('Staff added');
    }
    showModal.value = false;
    loadStaff();
  } catch (e) {
    toast.error('Failed to save staff');
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async (staff) => {
  if (confirm(`Delete staff member ${staff.name}?`)) {
  try {
    await api.delete(`/staff/${staff.id}`);
      toast.success('Staff deleted');
    loadStaff();
    } catch (e) {
      toast.error('Failed to delete staff');
    }
  }
};

const viewHistory = (staff) => {
  router.push({ path: '/reports', query: { staff_id: staff.id, type: 'commissions' } });
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
  loadStaff();
});
</script>

<style scoped>
.staff-page {
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
.stat-icon-bg.staff { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); }
.stat-icon-bg.rating { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-icon-bg.revenue { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.calls { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }

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

.staff-nano-card {
  background: var(--bg-tertiary);
  border-radius: 24px;
  padding: 1.5rem;
  position: relative;
  border: 1px solid var(--border-color);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  overflow: hidden;
}
.staff-nano-card:hover {
  transform: translateY(-8px);
  border-color: var(--asmaa-primary);
  box-shadow: var(--shadow-md);
}

.staff-status-dot {
  position: absolute;
  top: 15px;
  right: 15px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
}
.staff-status-dot.active { background: #10b981; box-shadow: 0 0 8px #10b981; }
.staff-status-dot.inactive { background: #ef4444; }

.staff-avatar-main {
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

.staff-stats-row .stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.staff-stats-row .stat .label { font-size: 0.625rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; }
.staff-stats-row .stat .value { font-size: 0.875rem; }

.staff-actions-hover {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.3s;
}
.staff-nano-card:hover .staff-actions-hover {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 768px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>

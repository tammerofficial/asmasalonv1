<template>
  <div class="staff-page p-4 nano-banana-theme">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-4 shadow-sm border border-light">
      <div class="header-left d-flex align-items-center gap-3">
        <div class="avatar-circle-nano">
          <CIcon icon="cil-people" />
        </div>
        <div>
          <h2 class="mb-0 fw-bold text-navy">{{ t('staff.title') }}</h2>
          <CBadge color="primary" shape="rounded-pill" class="px-3 py-1 fw-bold">
            {{ stats.total || staffList.length }} {{ t('staff.title') }}
          </CBadge>
        </div>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" class="nano-btn" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('staff.addNew') }}
        </CButton>
        <CButton color="secondary" variant="ghost" class="nano-btn-icon" @click="loadStaff" :disabled="loading">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano">
        <div class="stat-icon-bg staff"><CIcon icon="cil-user" /></div>
        <div class="stat-info">
          <div class="stat-value text-navy">{{ stats.active || 0 }}</div>
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
    <div class="nano-filters-bar p-3 bg-white rounded-4 mb-4 shadow-sm border border-light">
      <CRow class="g-3 align-items-center">
        <CCol md="6">
          <CInputGroup class="nano-input-group">
            <CInputGroupText class="bg-transparent border-0"><CIcon icon="cil-magnifying-glass" /></CInputGroupText>
            <CFormInput v-model="filters.search" :placeholder="t('common.search')" @input="debounceSearch" class="border-0 bg-transparent" />
          </CInputGroup>
        </CCol>
        <CCol md="3">
          <CFormSelect v-model="filters.is_active" @change="loadStaff" class="rounded-3 nano-select">
            <option value="">{{ t('common.status') }}</option>
            <option value="1">{{ t('status.active') }}</option>
            <option value="0">{{ t('status.inactive') }}</option>
          </CFormSelect>
        </CCol>
        <CCol md="3">
          <CButton color="secondary" variant="ghost" class="w-100 rounded-pill" @click="resetFilters">
            <CIcon icon="cil-filter-x" class="me-1" />{{ t('common.reset') }}
          </CButton>
        </CCol>
      </CRow>
    </div>

    <!-- Staff Cards Grid -->
    <div class="nano-panel p-4 bg-white rounded-5 shadow-luxury border border-light">
      <div v-if="loading" class="text-center p-5">
        <CSpinner color="primary" />
      </div>
      <div v-else-if="staffList.length === 0" class="text-center p-5">
        <EmptyState :title="t('common.noData')" :description="t('staff.title')" />
      </div>
      <div v-else class="nano-grid">
        <div v-for="staff in staffList" :key="staff.id" class="staff-nano-card" @click="editStaff(staff)">
          <div class="staff-status-dot" :class="staff.is_active ? 'active' : 'inactive'"></div>
          <div class="staff-avatar-main">
            <span v-if="!staff.photo">{{ staff.name?.charAt(0) }}</span>
            <img v-else :src="staff.photo" class="staff-img" />
          </div>
          <div class="staff-main-info mt-3 text-center">
            <h5 class="fw-bold mb-1 text-navy">{{ staff.name }}</h5>
            <p class="text-muted small mb-2">{{ staff.position || t('staff.role') }}</p>
            <div class="rating-stars mb-3">
              <CIcon icon="cil-star" v-for="i in 5" :key="i" :class="i <= (staff.rating || 0) ? 'text-warning' : 'text-gray-300'" />
              <span class="small ms-1 text-muted">({{ staff.total_ratings || 0 }})</span>
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
            <CButton size="sm" color="info" variant="ghost" class="action-btn" @click.stop="editStaff(staff)">
              <CIcon icon="cil-pencil" />
            </CButton>
            <CButton size="sm" color="primary" variant="ghost" class="action-btn" @click.stop="viewHistory(staff)">
              <CIcon icon="cil-history" />
            </CButton>
            <CButton size="sm" color="danger" variant="ghost" class="action-btn" @click.stop="confirmDelete(staff)">
              <CIcon icon="cil-trash" />
            </CButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <CModal :visible="showModal" @close="showModal = false" size="lg" alignment="center" class="nano-modal">
      <CModalHeader class="border-0 pb-0">
        <CModalTitle class="fw-bold text-navy">{{ isEditing ? t('staff.edit') : t('staff.addNew') }}</CModalTitle>
      </CModalHeader>
      <CModalBody class="p-4">
        <CRow class="g-3">
          <CCol md="6">
            <label class="form-label fw-bold small text-muted">{{ t('staff.fullName') }} *</label>
            <CFormInput v-model="staffForm.name" required class="rounded-3" />
          </CCol>
          <CCol md="6">
            <label class="form-label fw-bold small text-muted">{{ t('staff.role') }}</label>
            <CFormInput v-model="staffForm.position" class="rounded-3" />
          </CCol>
          <CCol md="6">
            <label class="form-label fw-bold small text-muted">{{ t('staff.commission') }} (%)</label>
            <CFormInput v-model.number="staffForm.commission_rate" type="number" min="0" max="100" class="rounded-3" />
          </CCol>
          <CCol md="6">
            <label class="form-label fw-bold small text-muted">{{ t('common.status') }}</label>
            <CFormSelect v-model="staffForm.is_active" class="rounded-3">
              <option :value="1">{{ t('status.active') }}</option>
              <option :value="0">{{ t('status.inactive') }}</option>
            </CFormSelect>
          </CCol>
          <CCol md="12">
            <label class="form-label fw-bold small text-muted">{{ t('common.notes') }}</label>
            <CFormTextarea v-model="staffForm.notes" rows="3" class="rounded-3" />
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter class="border-0 pt-0">
        <CButton color="light" class="rounded-pill px-4" @click="showModal = false">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="nano-btn px-4" @click="saveStaff" :disabled="saving">
          {{ saving ? t('common.saving') : t('common.save') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <HelpSection page-key="staff" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {
  CButton, CBadge, CRow, CCol, CSpinner, CFormInput, CFormSelect, 
  CInputGroup, CInputGroupText, CModal, CModalHeader, 
  CModalTitle, CModalBody, CModalFooter, CFormTextarea
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
  totalRevenue: 0,
  todayCalls: 0
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
  is_active: 1,
  notes: ''
});

// Methods
const loadStaff = async () => {
  loading.value = true;
  try {
    const response = await api.get('/staff', {
      params: { ...filters.value },
      noCache: true
    });
    staffList.value = response.data?.data?.items || response.data?.items || [];
    
    // Load stats
    const statsRes = await api.get('/staff/stats', { noCache: true });
    stats.value = statsRes.data?.data || statsRes.data || stats.value;
  } catch (e) {
    console.error('Failed to load staff:', e);
    toast.error(t('common.errorLoading'));
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
  staffForm.value = { name: '', position: '', commission_rate: 10, is_active: 1, notes: '' };
  showModal.value = true;
};

const editStaff = (staff) => {
  isEditing.value = true;
  editingId.value = staff.id;
  staffForm.value = { ...staff };
  showModal.value = true;
};

const saveStaff = async () => {
  if (!staffForm.value.name) {
    toast.error(t('common.requiredFields') || 'الاسم مطلوب');
    return;
  }
  saving.value = true;
  try {
    if (isEditing.value) {
      await api.put(`/staff/${editingId.value}`, staffForm.value);
      toast.success(t('staff.updated') || 'تم تحديث البيانات');
    } else {
      await api.post('/staff', staffForm.value);
      toast.success(t('staff.added') || 'تم إضافة الموظفة');
    }
    showModal.value = false;
    loadStaff();
  } catch (e) {
    toast.error(t('common.errorLoading'));
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async (staff) => {
  if (confirm(t('common.confirmDelete') || `هل تريد حذف ${staff.name}؟`)) {
    try {
      await api.delete(`/staff/${staff.id}`);
      toast.success(t('staff.deleted') || 'تم الحذف');
      loadStaff();
    } catch (e) {
      toast.error(t('common.errorLoading'));
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
  font-family: 'Manrope', 'Cairo', sans-serif;
  background: var(--color-gray-50);
  min-height: 100vh;
}

.text-navy { color: var(--color-navy); }

.avatar-circle-nano {
  width: 50px;
  height: 50px;
  background: var(--color-primary-soft);
  color: var(--color-primary);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  box-shadow: var(--shadow-sm);
}

.nano-btn {
  border-radius: var(--radius-pill);
  padding: 0.6rem 1.5rem;
  font-weight: 700;
  transition: var(--transition-smooth);
  box-shadow: var(--shadow-md);
  border: none;
}
.nano-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.nano-btn-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: var(--color-white);
  border: 1px solid var(--color-gray-100);
}

.nano-stats-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.stat-card-nano {
  background: var(--color-white);
  border-radius: 24px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: var(--shadow-luxury);
  border: 1px solid var(--color-gray-100);
  transition: var(--transition-smooth);
}
.stat-card-nano:hover {
  transform: translateY(-5px);
  border-color: var(--color-primary-light);
}

.stat-icon-bg {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}
.stat-icon-bg.staff { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); }
.stat-icon-bg.rating { background: linear-gradient(135deg, var(--color-warning) 0%, #d97706 100%); }
.stat-icon-bg.revenue { background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%); }
.stat-icon-bg.calls { background: linear-gradient(135deg, var(--color-info) 0%, #0284c7 100%); }

.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.8rem; color: var(--color-gray-500); font-weight: 600; margin-top: 4px; }

.nano-panel {
  background: var(--color-white);
  border-radius: 30px;
  border: 1px solid var(--color-gray-100);
}

.nano-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.5rem;
}

.staff-nano-card {
  background: var(--color-gray-50);
  border-radius: 24px;
  padding: 1.5rem;
  position: relative;
  border: 1px solid var(--color-gray-100);
  transition: var(--transition-smooth);
  cursor: pointer;
}
.staff-nano-card:hover {
  transform: translateY(-8px);
  border-color: var(--color-primary-light);
  background: white;
  box-shadow: var(--shadow-lg);
}

.staff-status-dot {
  position: absolute;
  top: 15px;
  right: 15px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
}
.staff-status-dot.active { background: var(--color-success); box-shadow: 0 0 8px var(--color-success); }
.staff-status-dot.inactive { background: var(--color-danger); }

.staff-avatar-main {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
  color: white;
  font-size: 2rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  box-shadow: var(--shadow-md);
  overflow: hidden;
}
.staff-img { width: 100%; height: 100%; object-fit: cover; }

.rating-stars {
  display: flex;
  justify-content: center;
  gap: 2px;
}

.staff-stats-row .stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.staff-stats-row .stat .label { font-size: 0.65rem; text-transform: uppercase; color: var(--color-gray-500); font-weight: 700; }
.staff-stats-row .stat .value { font-size: 0.9rem; }

.staff-actions-hover {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  opacity: 0;
  transform: translateY(10px);
  transition: var(--transition-smooth);
}
.staff-nano-card:hover .staff-actions-hover {
  opacity: 1;
  transform: translateY(0);
}

.action-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: white;
  box-shadow: var(--shadow-sm);
}

.nano-input-group {
  background: var(--color-gray-50);
  border-radius: 12px;
  padding: 0.25rem;
}

.nano-modal .modal-content {
  border-radius: 24px;
  border: none;
  box-shadow: var(--shadow-lg);
}

.spinning { animation: spin 1s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>

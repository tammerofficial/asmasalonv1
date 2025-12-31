<template>
  <div class="commissions-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('commissions.title')"
      :subtitle="t('commissions.subtitle') || (t('commissions.title') + ' - ' + t('dashboard.subtitle'))"
    >
      <template #icon>
        <CIcon icon="cil-dollar" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" class="me-2 apple-wallet-btn" @click="openAppleWalletModal">
          <CIcon icon="cil-wallet" class="me-2" />
          {{ t('commissions.appleWallet') || 'Apple Wallet' }}
        </CButton>
        <CButton color="secondary" variant="outline" class="me-2 settings-btn" @click="goToSettings">
          <CIcon icon="cil-settings" class="me-2" />
          {{ t('nav.programsSettings') }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="approveSelected">
          <CIcon icon="cil-check" class="me-2" />
          {{ t('commissions.approveSelected') || 'Approve Selected' }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('commissions.title')"
        :value="stats.total || commissions.length"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = ''; pagination.current_page = 1; loadCommissions(); }"
      >
        <template #icon>
          <CIcon icon="cil-dollar" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('commissions.pending')"
        :value="stats.pending"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = 'pending'; pagination.current_page = 1; loadCommissions(); }"
      >
        <template #icon>
          <CIcon icon="cil-clock" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('commissions.paid')"
        :value="stats.paid"
        badge-variant="success"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = 'paid'; pagination.current_page = 1; loadCommissions(); }"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('commissions.totalAmount') || 'Total Amount'"
        :value="formatCurrencyShort(stats.totalAmount)"
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
        <CCol :md="3">
          <CFormSelect v-model="filters.status" :label="t('common.status')" @change="loadCommissions" class="filter-select">
            <option value="">{{ t('common.status') }}</option>
            <option value="pending">{{ t('commissions.pending') }}</option>
            <option value="approved">{{ t('commissions.approved') || 'Approved' }}</option>
            <option value="paid">{{ t('commissions.paid') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.staff_id" :label="t('commissions.staff')" @change="loadCommissions" class="filter-select">
            <option value="">{{ t('commissions.staff') }}</option>
            <option v-for="s in staffOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormInput
            v-model="filters.start_date"
            type="date"
            :label="t('reports.fromDate')"
            @change="loadCommissions"
            class="filter-input"
          />
        </CCol>
        <CCol :md="3">
          <CFormInput
            v-model="filters.end_date"
            type="date"
            :label="t('reports.toDate')"
            @change="loadCommissions"
            class="filter-input"
          />
        </CCol>
        <CCol :md="12">
          <CInputGroup class="search-input-group">
            <CInputGroupText class="search-icon-wrapper">
              <CIcon icon="cil-magnifying-glass" />
            </CInputGroupText>
            <CFormInput
              v-model="filters.search"
              :placeholder="t('commissions.searchStaff') || 'Search staff...'"
              @input="debounceSearch"
              class="filter-input search-input"
            />
          </CInputGroup>
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
    <Card :title="t('commissions.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="commissions.length === 0"
        :title="t('common.noData')"
        :description="t('commissions.title') + ' - ' + t('common.noData')"
        icon-color="gray"
      />

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern commissions-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-check">
                <CFormCheck v-model="selectAll" @change="toggleSelectAll" />
              </th>
              <th class="th-staff">{{ t('commissions.staff') }}</th>
              <th class="th-base">{{ t('commissions.baseAmount') || 'Base Amount' }}</th>
              <th class="th-rate">{{ t('commissions.percentage') }}</th>
              <th class="th-bonus">{{ t('commissions.ratingBonus') || 'Rating Bonus' }}</th>
              <th class="th-final">{{ t('commissions.amount') }}</th>
              <th class="th-status">{{ t('common.status') }}</th>
              <th class="th-date">{{ t('commissions.date') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="commission in commissions" :key="commission.id" class="table-row commission-row">
              <td class="td-check">
                <CFormCheck :checked="selectedIds.includes(commission.id)" @change="toggleSelect(commission.id)" />
              </td>
              <td class="td-staff">
                <strong class="staff-name">{{ commission.staff_name || '—' }}</strong>
              </td>
              <td class="td-base">
                <strong class="unified-amount">{{ formatCurrency(commission.base_amount || 0) }}</strong>
              </td>
              <td class="td-rate">
                <CBadge class="unified-badge badge-rate">
                  <CIcon icon="cil-percent" class="badge-icon" />
                  <span>{{ commission.commission_rate || 0 }}%</span>
                </CBadge>
              </td>
              <td class="td-bonus">
                <CBadge class="unified-badge badge-bonus">
                  <CIcon icon="cil-star" class="badge-icon" />
                  <span>{{ formatCurrency(commission.rating_bonus || 0) }}</span>
                </CBadge>
              </td>
              <td class="td-final">
                <strong class="unified-amount">{{ formatCurrency(commission.final_amount || 0) }}</strong>
              </td>
              <td class="td-status">
                <CBadge class="unified-badge" :class="statusClass(commission.status)">
                  <CIcon :icon="getStatusIcon(commission.status)" class="badge-icon" />
                  <span>{{ getStatusText(commission.status) }}</span>
                </CBadge>
              </td>
              <td class="td-date">
                <span class="date-cell">{{ formatDate(commission.created_at) }}</span>
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
    
    <!-- Apple Wallet Modal -->
    <CModal :visible="showAppleWalletModal" @close="closeAppleWalletModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-wallet" class="me-2" />
          {{ t('commissions.createAppleWallet') || 'Create Apple Wallet Pass' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CFormSelect v-model="appleWalletForm.staff_id" :label="t('commissions.selectStaff') || 'Select Staff'" class="mb-3">
          <option value="">{{ t('commissions.selectStaff') || 'Select Staff' }}</option>
          <option v-for="s in staffOptions" :key="s.id" :value="s.id">
            {{ s.name }}
          </option>
        </CFormSelect>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeAppleWalletModal">
          {{ t('common.cancel') }}
        </CButton>
        <CButton color="primary" @click="createAppleWalletPass" :disabled="creatingPass || !appleWalletForm.staff_id">
          <CSpinner v-if="creatingPass" size="sm" class="me-2" />
          <CIcon v-else icon="cil-wallet" class="me-2" />
          {{ creatingPass ? t('common.creating') : t('common.create') }}
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  CButton,
  CTable,
  CBadge,
  CPagination,
  CFormSelect,
  CFormInput,
  CFormCheck,
  CInputGroup,
  CInputGroupText,
  CRow,
  CCol,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CSpinner,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import { useRouter } from 'vue-router';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();

const commissions = ref([]);
const loading = ref(false);
const selectedIds = ref([]);
const selectAll = ref(false);
const staff = ref([]);
const showAppleWalletModal = ref(false);
const creatingPass = ref(false);
const appleWalletForm = ref({ staff_id: '' });

const filters = ref({
  status: '',
  staff_id: '',
  search: '',
  start_date: '',
  end_date: '',
});

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});

const stats = computed(() => {
  const total = commissions.value.length;
  const pending = commissions.value.filter(c => c.status === 'pending').length;
  const paid = commissions.value.filter(c => c.status === 'paid').length;
  const totalAmount = commissions.value.reduce((sum, c) => sum + (parseFloat(c.final_amount) || 0), 0);
  
  return {
    total,
    pending,
    paid,
    totalAmount,
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

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    approved: 'info',
    paid: 'success',
  };
  return colors[status] || 'secondary';
};

const getStatusText = (status) => {
  const texts = {
    pending: t('commissions.pending'),
    approved: 'Approved',
    paid: t('commissions.paid'),
  };
  return texts[status] || status;
};

const getStatusIcon = (status) => {
  const icons = {
    pending: 'cil-clock',
    approved: 'cil-check-circle',
    paid: 'cil-check',
  };
  return icons[status] || 'cil-info';
};

const loadCommissions = async () => {
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

    const response = await api.get('/commissions', { params, noCache: true });
    const data = response.data?.data || response.data || {};
    
    commissions.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading commissions:', error);
    toast.error(t('common.errorLoading'));
    commissions.value = [];
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

const loadStaff = async () => {
  try {
    const response = await api.get('/staff', { params: { per_page: 200 }, noCache: true });
    const data = response.data?.data || response.data || {};
    staff.value = data.items || data || [];
  } catch (error) {
    staff.value = [];
  }
};

const staffOptions = computed(() => staff.value || []);

const changePage = (page) => {
  pagination.value.current_page = page;
  loadCommissions();
};

const resetFilters = () => {
  filters.value = { status: '', staff_id: '', search: '', start_date: '', end_date: '' };
  pagination.value.current_page = 1;
  loadCommissions();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadCommissions();
  }, 500);
};

const toggleSelect = (id) => {
  const index = selectedIds.value.indexOf(id);
  if (index > -1) {
    selectedIds.value.splice(index, 1);
  } else {
    selectedIds.value.push(id);
  }
  selectAll.value = selectedIds.value.length === commissions.value.length;
};

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedIds.value = commissions.value.map(c => c.id);
  } else {
    selectedIds.value = [];
  }
};

const approveSelected = async () => {
  if (selectedIds.value.length === 0) {
    toast.error(t('commissions.selectToApprove') || 'Select commissions to approve');
    return;
  }

  try {
    await api.post('/commissions/approve', { ids: selectedIds.value });
    selectedIds.value = [];
    selectAll.value = false;
    toast.success(t('commissions.approvedSuccess') || 'Approved successfully');
    loadCommissions();
  } catch (error) {
    console.error('Error approving commissions:', error);
    toast.error(t('commissions.approveError') || t('common.errorLoading'));
  }
};

const goToSettings = () => {
  router.push('/programs/settings');
};

const openAppleWalletModal = () => {
  showAppleWalletModal.value = true;
  appleWalletForm.value = { staff_id: '' };
  if (staff.value.length === 0) {
    loadStaff();
  }
};

const closeAppleWalletModal = () => {
  showAppleWalletModal.value = false;
  creatingPass.value = false;
  appleWalletForm.value = { staff_id: '' };
};

const createAppleWalletPass = async () => {
  if (!appleWalletForm.value.staff_id) {
    toast.error(t('commissions.selectStaff') || 'Select staff');
    return;
  }

  creatingPass.value = true;
  try {
    const response = await api.post(`/commissions/apple-wallet/${appleWalletForm.value.staff_id}`);
    const data = response.data?.data || response.data || {};
    
    if (data.pass_url) {
      window.open(data.pass_url, '_blank');
      toast.success(t('commissions.passCreated') || 'تم إنشاء البطاقة بنجاح');
    } else {
      toast.error(t('commissions.passError') || 'حدث خطأ في إنشاء البطاقة');
    }
    
    closeAppleWalletModal();
  } catch (error) {
    console.error('Error creating Apple Wallet pass:', error);
    toast.error(error.response?.data?.message || t('commissions.passError') || 'حدث خطأ في إنشاء البطاقة');
  } finally {
    creatingPass.value = false;
  }
};

const statusClass = (status) => {
  const st = String(status || '');
  if (st === 'paid') return 'status-paid';
  if (st === 'approved') return 'status-approved';
  if (st === 'pending') return 'status-pending';
  return 'status-other';
};

onMounted(() => {
  loadCommissions();
  loadStaff();
});
</script>

<style scoped>
.commissions-page{display:flex;flex-direction:column;gap:1.5rem;}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;}

.filters-card{border:1px solid var(--border-color);box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.search-input-group{position:relative;}
.search-icon-wrapper{background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.8) 100%);color:#fff;border-color:var(--asmaa-primary);}
.filter-input,.filter-select{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);border:1px solid var(--border-color);}
.filter-input:focus,.filter-select:focus{border-color:var(--asmaa-primary);box-shadow:0 0 0 3px rgba(187,160,122,0.15);outline:none;}
.search-input:focus{border-left:none;}
.reset-btn{transition:all 0.3s;}
.reset-btn:hover{background:var(--asmaa-primary);color:#fff;border-color:var(--asmaa-primary);transform:translateY(-1px);}

.btn-primary-custom{background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.9) 100%);border:none;box-shadow:0 4px 12px rgba(187,160,122,0.3);transition:all 0.3s;}
.btn-primary-custom:hover{background:linear-gradient(135deg, rgba(187,160,122,0.95) 0%, var(--asmaa-primary) 100%);box-shadow:0 6px 16px rgba(187,160,122,0.4);transform:translateY(-2px);}

.table-wrapper{overflow-x:auto;border-radius:12px;border:1px solid var(--border-color);background:var(--bg-primary);}
.commissions-table{margin:0;border-collapse:separate;border-spacing:0;}
.table-header-row{background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%);border-bottom:2px solid var(--asmaa-primary);}
.table-header-row th{padding:1rem 1.25rem;font-weight:700;color:var(--text-primary);text-transform:uppercase;font-size:0.75rem;letter-spacing:0.5px;border-bottom:none;white-space:nowrap;}
.th-check{width:72px;text-align:center;}
.th-staff{min-width:200px;}
.th-base,.th-final{min-width:160px;}
.th-rate{min-width:140px;text-align:center;}
.th-bonus{min-width:190px;text-align:center;}
.th-status{min-width:170px;text-align:center;}
.th-date{min-width:160px;}

.commission-row{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);border-bottom:1px solid var(--border-color);}
.commission-row:hover{background:linear-gradient(90deg, rgba(187,160,122,0.05) 0%, rgba(187,160,122,0.02) 100%);transform:translateX(4px);box-shadow:0 2px 8px rgba(187,160,122,0.1);}
[dir=\"rtl\"] .commission-row:hover{transform:translateX(-4px);}
.commission-row td{padding:1rem 1.25rem;vertical-align:middle;border-bottom:1px solid var(--border-color);}
.td-check{text-align:center;}
.staff-name{font-weight:800;color:var(--text-primary);}

.unified-amount{display:inline-flex;align-items:center;gap:0.5rem;color:var(--asmaa-primary);font-weight:800;font-size:0.9375rem;padding:0.375rem 0.75rem;border-radius:8px;background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%);transition:all 0.3s;}

.unified-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 0.75rem;border-radius:8px;font-size:0.8125rem;font-weight:600;transition:all 0.3s;background:linear-gradient(135deg, rgba(187,160,122,0.15) 0%, rgba(187,160,122,0.1) 100%);color:var(--asmaa-primary);border:1px solid rgba(187,160,122,0.3);justify-content:center;}
.badge-icon{width:14px;height:14px;color:currentColor;}
.badge-rate{background:linear-gradient(135deg, var(--asmaa-primary-soft) 0%, hsla(35, 30%, 61%, 0.10) 100%);color:var(--asmaa-primary);border-color:var(--asmaa-primary-soft-border);}
.badge-bonus{background:linear-gradient(135deg, var(--asmaa-warning-soft) 0%, hsla(38, 92%, 50%, 0.10) 100%);color:var(--asmaa-warning);border-color:var(--asmaa-warning-soft-border);}

.status-pending{background:linear-gradient(135deg, var(--asmaa-warning-soft) 0%, hsla(38, 92%, 50%, 0.10) 100%);color:var(--asmaa-warning);border-color:var(--asmaa-warning-soft-border);}
.status-approved{background:linear-gradient(135deg, var(--asmaa-primary-soft) 0%, hsla(35, 30%, 61%, 0.10) 100%);color:var(--asmaa-primary);border-color:var(--asmaa-primary-soft-border);}
.status-paid{background:linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);color:var(--asmaa-success);border-color:var(--asmaa-success-soft-border);}
.status-other{background:linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);color:var(--asmaa-secondary);border-color:var(--asmaa-secondary-soft-border);}

.date-cell{color:var(--text-secondary);font-size:0.875rem;}

@media (max-width:768px){.stats-grid{grid-template-columns:1fr;}}
</style>

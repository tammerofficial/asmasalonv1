<template>
  <div class="loyalty-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('loyalty.title')"
      :subtitle="t('loyalty.subtitle') || (t('loyalty.title') + ' - ' + t('dashboard.subtitle'))"
    >
      <template #icon>
        <CIcon icon="cil-gift" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" class="me-2 settings-btn" @click="goToSettings">
          <CIcon icon="cil-settings" class="me-2" />
          {{ t('nav.programsSettings') }}
        </CButton>
        <CButton 
          color="success" 
          variant="outline" 
          class="me-2 apple-wallet-btn" 
          @click="openAppleWalletModal"
          v-if="selectedCustomerId"
        >
          <CIcon icon="cil-wallet" class="me-2" />
          {{ t('loyalty.addToAppleWallet') || 'أضف إلى Apple Wallet' }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="openRedeemModal">
          <CIcon icon="cil-gift" class="me-2" />
          {{ t('loyalty.redeem') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('loyalty.totalIssued')"
        :value="stats.total_points_issued || 0"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-star" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('loyalty.totalRedeemed')"
        :value="stats.total_points_redeemed || 0"
        badge-variant="warning"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-gift" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('loyalty.points')"
        :value="stats.active_points || 0"
        badge-variant="success"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('loyalty.activeCustomers')"
        :value="stats.active_customers || 0"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-user" />
        </template>
      </StatCard>
    </div>

    <!-- Filters -->
    <Card :title="t('common.filter')" icon="cil-filter" class="filters-card">
      <CRow class="g-3">
        <CCol :md="4">
          <CFormSelect v-model="filters.type" :label="t('common.status')" @change="loadTransactions">
            <option value="">{{ t('common.all') || 'All' }}</option>
            <option value="earned">{{ t('loyalty.earned') }}</option>
            <option value="redeemed">{{ t('loyalty.redeemed') }}</option>
            <option value="adjustment">{{ t('loyalty.adjustment') }}</option>
            <option value="expired">{{ t('loyalty.expired') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="6">
          <CInputGroup class="search-input-group">
            <CInputGroupText class="search-icon-wrapper">
              <CIcon icon="cil-magnifying-glass" />
            </CInputGroupText>
            <CFormInput
              v-model="filters.customer_search"
              :placeholder="t('loyalty.searchCustomer')"
              @input="debounceSearch"
              class="filter-input search-input"
            />
          </CInputGroup>
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
    <Card :title="t('loyalty.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="transactions.length === 0"
        :title="t('common.noData')"
        :description="t('loyalty.noTransactions') || (t('loyalty.title') + ' - ' + t('common.noData'))"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" class="btn-primary-custom" @click="openRedeemModal">
            {{ t('loyalty.redeem') }}
          </CButton>
        </template>
      </EmptyState>

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern loyalty-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-id">#</th>
              <th class="th-date">{{ t('common.date') }}</th>
              <th class="th-customer">{{ t('loyalty.customer') }}</th>
              <th class="th-type">{{ t('loyalty.type') || 'Type' }}</th>
              <th class="th-points">{{ t('loyalty.points') }}</th>
              <th class="th-desc">{{ t('loyalty.description') || 'Description' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transaction in transactions" :key="transaction.id" class="table-row loyalty-row">
              <td class="td-id">
                <span class="tx-id-badge">#{{ transaction.id }}</span>
              </td>
              <td class="td-date">{{ formatDate(transaction.created_at) }}</td>
              <td class="td-customer">
                <div class="customer-cell">
                  <strong class="customer-name">{{ transaction.customer_name || '—' }}</strong>
                  <a v-if="transaction.customer_phone" class="customer-phone" :href="`tel:${transaction.customer_phone}`">
                    {{ transaction.customer_phone }}
                  </a>
                </div>
              </td>
              <td class="td-type">
                <CBadge class="unified-badge" :class="typeClass(transaction.type)">
                  <CIcon :icon="getTypeIcon(transaction.type)" class="badge-icon" />
                  <span>{{ getTypeText(transaction.type) }}</span>
                </CBadge>
              </td>
              <td class="td-points">
                <strong class="points-amount" :class="Number(transaction.points) >= 0 ? 'points-plus' : 'points-minus'">
                  {{ Number(transaction.points) >= 0 ? '+' : '' }}{{ transaction.points }}
                </strong>
              </td>
              <td class="td-desc">
                <span class="transaction-description">{{ transaction.description || '—' }}</span>
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
          {{ t('loyalty.addToAppleWallet') || 'أضف إلى Apple Wallet' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="12">
            <CFormSelect 
              v-model="appleWalletForm.customer_id" 
              :label="t('loyalty.selectCustomer') || 'Select Customer'" 
              class="filter-select"
            >
              <option value="">{{ t('common.select') }}</option>
              <option v-for="c in customerOptions" :key="c.id" :value="String(c.id)">
                {{ c.name }} ({{ c.phone || c.email }})
              </option>
            </CFormSelect>
          </CCol>
          <CCol :md="12" v-if="appleWalletForm.customer_id">
            <div class="alert alert-info">
              <CIcon icon="cil-info" class="me-2" />
              {{ t('loyalty.appleWalletInfo') || 'سيتم إنشاء بطاقة Apple Wallet تحتوي على نقاط الولاء والعضوية و QR Code لسكان النقاط' }}
            </div>
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeAppleWalletModal">{{ t('common.cancel') }}</CButton>
        <CButton 
          color="success" 
          class="btn-primary-custom" 
          :disabled="creatingPass || !appleWalletForm.customer_id" 
          @click="createAppleWalletPass"
        >
          <CIcon icon="cil-wallet" class="me-2" />
          {{ creatingPass ? (t('common.loading') || 'Loading...') : (t('loyalty.createPass') || 'إنشاء البطاقة') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Redeem Modal -->
    <CModal :visible="showRedeemModal" @close="closeRedeemModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-gift" class="me-2" />
          {{ t('loyalty.redeem') }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6">
            <CFormSelect v-model="redeemForm.customer_id" :label="t('loyalty.customer')" class="filter-select">
              <option value="">{{ t('common.select') }}</option>
              <option v-for="c in customerOptions" :key="c.id" :value="String(c.id)">
                {{ c.name }} ({{ c.phone }})
              </option>
            </CFormSelect>
          </CCol>
          <CCol :md="6">
            <CFormInput v-model.number="redeemForm.points" type="number" min="1" step="1" :label="t('loyalty.points')" class="filter-input" />
          </CCol>
          <CCol :md="12">
            <CFormInput v-model="redeemForm.description" :label="t('loyalty.description') || 'Description'" class="filter-input" />
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeRedeemModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="btn-primary-custom" :disabled="redeeming" @click="redeemPoints">
          <CIcon icon="cil-check" class="me-2" />
          {{ redeeming ? (t('loyalty.processing') || t('common.loading')) : t('loyalty.redeem') }}
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
  CInputGroup,
  CInputGroupText,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CRow,
  CCol,
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

const transactions = ref([]);
const stats = ref({});
const loading = ref(false);
const showRedeemModal = ref(false);
const redeeming = ref(false);
const customers = ref([]);
const showAppleWalletModal = ref(false);
const creatingPass = ref(false);
const selectedCustomerId = ref(null);

const appleWalletForm = ref({
  customer_id: '',
});

const redeemForm = ref({
  customer_id: '',
  points: 1,
  description: '',
});

const filters = ref({
  type: '',
  customer_search: '',
});

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getTypeColor = (type) => {
  const colors = {
    earned: 'success',
    redeemed: 'warning',
    adjustment: 'info',
    expired: 'secondary',
  };
  return colors[type] || 'secondary';
};

const getTypeText = (type) => {
  const texts = {
    earned: t('loyalty.earned'),
    redeemed: t('loyalty.redeemed'),
    adjustment: t('loyalty.adjustment'),
    expired: t('loyalty.expired'),
  };
  return texts[type] || type;
};

const getTypeIcon = (type) => {
  const icons = {
    earned: 'cil-plus',
    redeemed: 'cil-minus',
    adjustment: 'cil-pen',
    expired: 'cil-x-circle',
  };
  return icons[type] || 'cil-info';
};

const loadTransactions = async () => {
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

    const response = await api.get('/loyalty/transactions', { params, noCache: true });
    const data = response.data?.data || response.data || {};
    
    transactions.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading transactions:', error);
    toast.error(t('common.errorLoading'));
    transactions.value = [];
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

const loadStats = async () => {
  try {
    const response = await api.get('/loyalty/stats', { noCache: true });
    stats.value = response.data?.data || {};
  } catch (error) {
    console.error('Error loading stats:', error);
    stats.value = {};
  }
};

const loadCustomers = async () => {
  try {
    const response = await api.get('/customers', { params: { per_page: 200 }, noCache: true });
    const data = response.data?.data || response.data || {};
    customers.value = data.items || data || [];
  } catch (error) {
    customers.value = [];
  }
};

const customerOptions = computed(() => customers.value || []);

const changePage = (page) => {
  pagination.value.current_page = page;
  loadTransactions();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadTransactions();
  }, 500);
};

const resetFilters = () => {
  filters.value = { type: '', customer_search: '' };
  pagination.value.current_page = 1;
  loadTransactions();
};

const openRedeemModal = () => {
  showRedeemModal.value = true;
  redeemForm.value = { customer_id: '', points: 1, description: '' };
};

const closeRedeemModal = () => {
  showRedeemModal.value = false;
  redeeming.value = false;
};

const redeemPoints = async () => {
  if (!redeemForm.value.customer_id) {
    toast.error(t('loyalty.selectCustomer') || 'Select customer');
    return;
  }
  if (Number(redeemForm.value.points || 0) <= 0) {
    toast.error(t('loyalty.invalidPoints') || 'Invalid points');
    return;
  }

  redeeming.value = true;
  try {
    await api.post('/loyalty/redeem', {
      customer_id: Number(redeemForm.value.customer_id),
      points: Number(redeemForm.value.points || 0),
      reference_type: 'manual',
      reference_id: null,
      description: redeemForm.value.description || '',
    });
    toast.success(t('loyalty.redeemedSuccess') || 'Redeemed successfully');
    closeRedeemModal();
    await Promise.all([loadStats(), loadTransactions()]);
  } catch (error) {
    console.error('Redeem points error:', error);
    toast.error(t('loyalty.redeemError') || t('common.errorLoading'));
  } finally {
    redeeming.value = false;
  }
};

const goToSettings = () => {
  router.push('/programs/settings');
};

const openAppleWalletModal = () => {
  showAppleWalletModal.value = true;
  appleWalletForm.value = { customer_id: selectedCustomerId.value || '' };
  if (customers.value.length === 0) {
    loadCustomers();
  }
};

const closeAppleWalletModal = () => {
  showAppleWalletModal.value = false;
  creatingPass.value = false;
  appleWalletForm.value = { customer_id: '' };
};

const createAppleWalletPass = async () => {
  if (!appleWalletForm.value.customer_id) {
    toast.error(t('loyalty.selectCustomer') || 'Select customer');
    return;
  }

  creatingPass.value = true;
  try {
    const response = await api.post(`/apple-wallet/create/${appleWalletForm.value.customer_id}/loyalty`);
    const data = response.data?.data || response.data || {};
    
    if (data.pass_url) {
      // Open pass URL in new window (will trigger Apple Wallet on iOS)
      window.open(data.pass_url, '_blank');
      toast.success(t('loyalty.passCreated') || 'تم إنشاء البطاقة بنجاح');
    } else {
      toast.error(t('loyalty.passError') || 'حدث خطأ في إنشاء البطاقة');
    }
    
    closeAppleWalletModal();
  } catch (error) {
    console.error('Error creating Apple Wallet pass:', error);
    toast.error(error.response?.data?.message || t('loyalty.passError') || 'حدث خطأ في إنشاء البطاقة');
  } finally {
    creatingPass.value = false;
  }
};

const typeClass = (type) => {
  const tpe = String(type || '');
  if (tpe === 'earned') return 'badge-earned';
  if (tpe === 'redeemed') return 'badge-redeemed';
  if (tpe === 'adjustment') return 'badge-adjustment';
  return 'badge-expired';
};

onMounted(() => {
  loadTransactions();
  loadStats();
  loadCustomers();
});
</script>

<style scoped>
.loyalty-page{display:flex;flex-direction:column;gap:1.5rem;}
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
.loyalty-table{margin:0;border-collapse:separate;border-spacing:0;}
.table-header-row{background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%);border-bottom:2px solid var(--asmaa-primary);}
.table-header-row th{padding:1rem 1.25rem;font-weight:700;color:var(--text-primary);text-transform:uppercase;font-size:0.75rem;letter-spacing:0.5px;border-bottom:none;white-space:nowrap;}
.th-id{width:90px;text-align:center;}
.th-date{min-width:160px;}
.th-customer{min-width:260px;}
.th-type{min-width:180px;text-align:center;}
.th-points{min-width:120px;text-align:center;}
.th-desc{min-width:280px;}

.loyalty-row{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);border-bottom:1px solid var(--border-color);}
.loyalty-row:hover{background:linear-gradient(90deg, rgba(187,160,122,0.05) 0%, rgba(187,160,122,0.02) 100%);transform:translateX(4px);box-shadow:0 2px 8px rgba(187,160,122,0.1);}
[dir=\"rtl\"] .loyalty-row:hover{transform:translateX(-4px);}
.loyalty-row td{padding:1rem 1.25rem;vertical-align:middle;border-bottom:1px solid var(--border-color);}

.tx-id-badge{display:inline-flex;align-items:center;justify-content:center;width:56px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.8) 100%);color:#fff;font-weight:800;font-size:0.875rem;box-shadow:0 2px 8px rgba(187,160,122,0.3);}

.customer-cell{display:flex;flex-direction:column;gap:0.25rem;min-width:0;}
.customer-name{font-weight:800;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;}
.customer-phone{font-size:0.8125rem;color:var(--asmaa-primary);text-decoration:none;}
.customer-phone:hover{text-decoration:underline;}

.unified-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 0.75rem;border-radius:8px;font-size:0.8125rem;font-weight:600;transition:all 0.3s;background:linear-gradient(135deg, rgba(187,160,122,0.15) 0%, rgba(187,160,122,0.1) 100%);color:var(--asmaa-primary);border:1px solid rgba(187,160,122,0.3);justify-content:center;}
.badge-icon{width:14px;height:14px;color:currentColor;}

.badge-earned{background:linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);color:var(--asmaa-success);border-color:var(--asmaa-success-soft-border);}
.badge-redeemed{background:linear-gradient(135deg, var(--asmaa-danger-soft) 0%, hsla(0, 84%, 60%, 0.10) 100%);color:var(--asmaa-danger);border-color:var(--asmaa-danger-soft-border);}
.badge-adjustment{background:linear-gradient(135deg, var(--asmaa-primary-soft) 0%, hsla(35, 30%, 61%, 0.10) 100%);color:var(--asmaa-primary);border-color:var(--asmaa-primary-soft-border);}
.badge-expired{background:linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);color:var(--asmaa-secondary);border-color:var(--asmaa-secondary-soft-border);}

.points-amount{font-weight:900;font-size:1rem;}
.points-plus{color:var(--asmaa-success);}
.points-minus{color:var(--asmaa-danger);}

.transaction-description{color:var(--text-secondary);font-size:0.875rem;}

@media (max-width:768px){.stats-grid{grid-template-columns:1fr;}}
</style>

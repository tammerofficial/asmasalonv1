<template>
  <div class="loyalty-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('loyalty.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-gift" class="me-1" />
          Loyalty Rewards
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="success" class="nano-btn-success" @click="openAppleWalletModal" v-if="selectedCustomerId">
          <CIcon icon="cil-wallet" class="me-2" />{{ t('loyalty.addToAppleWallet') }}
        </CButton>
        <CButton color="primary" class="nano-btn" @click="openRedeemModal">
          <CIcon icon="cil-gift" class="me-2" />{{ t('loyalty.redeem') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="goToSettings">
          <CIcon icon="cil-settings" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano">
        <div class="stat-icon-bg points-issued"><CIcon icon="cil-star" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.total_points_issued || 0 }}</div>
          <div class="stat-label">Points Issued</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg points-redeemed"><CIcon icon="cil-gift" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">{{ stats.total_points_redeemed || 0 }}</div>
          <div class="stat-label">Points Redeemed</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg points-active"><CIcon icon="cil-check-circle" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">{{ stats.active_points || 0 }}</div>
          <div class="stat-label">Active Balance</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg members-active"><CIcon icon="cil-user" /></div>
        <div class="stat-info">
          <div class="stat-value text-info">{{ stats.active_customers || 0 }}</div>
          <div class="stat-label">Loyalty Members</div>
        </div>
      </div>
    </div>

    <!-- Modern Filters Bar -->
    <div class="nano-filters-bar p-3 bg-secondary rounded-4 mb-4 shadow-sm border border-light">
      <CRow class="g-3 align-items-center">
        <CCol md="4">
          <CInputGroup>
            <CInputGroupText class="bg-transparent border-0"><CIcon icon="cil-magnifying-glass" /></CInputGroupText>
            <CFormInput v-model="filters.customer_search" :placeholder="t('loyalty.searchCustomer')" @input="debounceSearch" class="border-0 bg-transparent" />
          </CInputGroup>
        </CCol>
        <CCol md="3">
          <CFormSelect v-model="filters.type" @change="loadTransactions" class="rounded-3">
            <option value="">{{ t('common.all') }} Transactions</option>
            <option value="earned">{{ t('loyalty.earned') }}</option>
            <option value="redeemed">{{ t('loyalty.redeemed') }}</option>
            <option value="adjustment">{{ t('loyalty.adjustment') }}</option>
          </CFormSelect>
        </CCol>
        <CCol md="3">
          <CButton color="primary" variant="ghost" class="w-100" @click="resetFilters">
            <CIcon icon="cil-filter-x" class="me-1" />{{ t('common.reset') }}
          </CButton>
        </CCol>
      </CRow>
    </div>

    <!-- Transactions Panel -->
    <div class="nano-panel">
      <div v-if="loading" class="text-center p-5">
        <CSpinner color="primary" />
      </div>
      <div v-else-if="transactions.length === 0" class="text-center p-5 text-muted opacity-50">
        <CIcon icon="cil-gift" size="xl" class="mb-3" />
        <p>No loyalty transactions found</p>
      </div>
      <div v-else>
        <div class="nano-table-container">
          <table class="nano-table w-100">
            <thead>
              <tr>
                <th class="text-start">Customer</th>
                <th>Type</th>
                <th class="text-center">Points</th>
                <th>Order #</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in transactions" :key="tx.id" class="nano-table-row">
                <td class="text-start">
                  <div class="fw-bold">{{ tx.customer_name || 'Anonymous' }}</div>
                  <div class="small text-muted">{{ tx.customer_phone }}</div>
                </td>
                <td>
                  <CBadge :color="getTypeColor(tx.type)" shape="rounded-pill" class="px-3">
                    {{ tx.type?.toUpperCase() }}
                  </CBadge>
                </td>
                <td class="text-center fw-bold fs-5" :class="tx.points > 0 ? 'text-success' : 'text-danger'">
                  {{ tx.points > 0 ? '+' : '' }}{{ tx.points }}
                </td>
                <td><CBadge color="secondary" variant="outline">#{{ tx.order_id || '—' }}</CBadge></td>
                <td>{{ new Date(tx.created_at).toLocaleString() }}</td>
                <td>
                  <CButton size="sm" color="primary" variant="ghost" @click="viewCustomer(tx.customer_id)">
                    <CIcon icon="cil-external-link" />
                  </CButton>
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

    <!-- Modals -->
    <CModal :visible="showRedeemModal" @close="showRedeemModal = false" alignment="center">
      <CModalHeader>
        <CModalTitle><CIcon icon="cil-gift" class="me-2" />{{ t('loyalty.redeem') }}</CModalTitle>
      </CModalHeader>
      <CModalBody class="p-4">
        <!-- Redeem Form -->
        <div class="mb-3">
          <label class="form-label fw-bold">Select Customer</label>
          <CFormSelect v-model="redeemForm.customer_id">
            <option :value="null">Choose a member...</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} ({{ c.points }} pts)</option>
          </CFormSelect>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Points to Redeem</label>
          <CFormInput type="number" v-model.number="redeemForm.points" />
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" variant="ghost" @click="showRedeemModal = false">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="nano-btn" @click="processRedeem" :disabled="!redeemForm.customer_id || !redeemForm.points">
          Process Redemption
        </CButton>
      </CModalFooter>
    </CModal>

    <HelpSection page-key="loyalty" />
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
const transactions = ref([]);
const customers = ref([]);
const stats = ref({
  total_points_issued: 0,
  total_points_redeemed: 0,
  active_points: 0,
  active_customers: 0
});
const pagination = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
  total_pages: 0
});
const filters = ref({
  type: '',
  customer_search: ''
});

// Redeem Form
const showRedeemModal = ref(false);
const redeemForm = ref({
  customer_id: null,
  points: 0
});

// Methods
const loadTransactions = async () => {
  loading.value = true;
  try {
    const res = await api.get('/loyalty/transactions', {
      params: {
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
        ...filters.value
      }
    });
    const data = res.data?.data || res.data;
    transactions.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
    
    // Load stats
    const statsRes = await api.get('/loyalty/stats');
    stats.value = statsRes.data?.data || statsRes.data || stats.value;
  } catch (e) {
    console.error('Failed to load loyalty data:', e);
  } finally {
    loading.value = false;
  }
};

const loadCustomers = async () => {
  const res = await api.get('/customers', { params: { per_page: 1000 } });
  customers.value = res.data?.data?.items || res.data?.items || [];
};

const debounceSearch = () => {
  clearTimeout(window.searchTimer);
  window.searchTimer = setTimeout(loadTransactions, 500);
};

const resetFilters = () => {
  filters.value = { type: '', customer_search: '' };
  loadTransactions();
};

const changePage = (page) => {
  pagination.value.current_page = page;
  loadTransactions();
};

const getTypeColor = (type) => {
  switch (type) {
    case 'earned': return 'success';
    case 'redeemed': return 'warning';
    case 'adjustment': return 'info';
    case 'expired': return 'danger';
    default: return 'secondary';
  }
};

const openRedeemModal = () => {
  redeemForm.value = { customer_id: null, points: 0 };
  showRedeemModal.value = true;
};

const processRedeem = async () => {
  try {
    await api.post('/loyalty/redeem', redeemForm.value);
    toast.success('Points redeemed successfully');
    showRedeemModal.value = false;
    loadTransactions();
  } catch (e) {
    toast.error('Failed to redeem points');
  }
};

const viewCustomer = (id) => router.push(`/customers/${id}`);
const goToSettings = () => router.push('/settings/loyalty');
const openAppleWalletModal = () => { /* Apple Wallet integration logic */ };

onMounted(() => {
  loadTransactions();
  loadCustomers();
});
</script>

<style scoped>
.loyalty-page {
  font-family: 'Cairo', sans-serif;
  background: var(--bg-primary);
  min-height: 100vh;
}

.nano-btn {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}
.nano-btn-success {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
.stat-icon-bg.points-issued { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
.stat-icon-bg.points-redeemed { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-icon-bg.points-active { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.members-active { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }

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

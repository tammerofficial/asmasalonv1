<template>
  <div class="payments-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('payments.title')"
      :subtitle="t('payments.subtitle') || (t('payments.title') + ' - ' + t('dashboard.subtitle'))"
    >
      <template #icon>
        <CIcon icon="cil-money" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" @click="exportData" class="me-2 export-btn">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('payments.addNew') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('payments.title')"
        :value="stats.total || payments.length"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = ''; pagination.current_page = 1; loadPayments(); }"
      >
        <template #icon>
          <CIcon icon="cil-money" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('payments.completed')"
        :value="stats.completed"
        badge-variant="success"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = 'completed'; pagination.current_page = 1; loadPayments(); }"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('payments.pending')"
        :value="stats.pending"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = 'pending'; pagination.current_page = 1; loadPayments(); }"
      >
        <template #icon>
          <CIcon icon="cil-clock" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('payments.totalAmount')"
        :value="formatCurrencyShort(stats.totalAmount)"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-dollar" />
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
          <CFormSelect v-model="filters.status" @change="loadPayments" class="filter-select">
            <option value="">{{ t('common.status') }}</option>
            <option value="completed">{{ t('payments.completed') }}</option>
            <option value="pending">{{ t('payments.pending') }}</option>
            <option value="failed">{{ t('payments.failed') }}</option>
            <option value="refunded">{{ t('payments.refunded') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.payment_method" @change="loadPayments" class="filter-select">
            <option value="">{{ t('payments.method') }}</option>
            <option value="cash">{{ t('payments.cash') }}</option>
            <option value="card">{{ t('payments.card') }}</option>
            <option value="knet">KNET</option>
            <option value="online">Online</option>
          </CFormSelect>
        </CCol>
        <CCol :md="2">
          <CFormInput
            v-model="filters.date_from"
            type="date"
            :label="t('reports.fromDate')"
            @change="loadPayments"
            class="filter-input"
          />
        </CCol>
        <CCol :md="2">
          <CFormInput
            v-model="filters.date_to"
            type="date"
            :label="t('reports.toDate')"
            @change="loadPayments"
            class="filter-input"
          />
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
    <Card :title="t('payments.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="payments.length === 0"
        :title="t('common.noData')"
        :description="t('payments.title') + ' - ' + t('common.noData')"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
            {{ t('payments.addNew') }}
          </CButton>
        </template>
      </EmptyState>

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern payments-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-id">#</th>
              <th class="th-number">{{ t('payments.paymentNumber') }}</th>
              <th class="th-customer">{{ t('payments.customer') }}</th>
              <th class="th-amount">{{ t('payments.amount') }}</th>
              <th class="th-method">{{ t('payments.method') }}</th>
              <th class="th-status">{{ t('common.status') }}</th>
              <th class="th-date">{{ t('payments.date') }}</th>
              <th class="th-actions">{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in payments" :key="payment.id" class="table-row payment-row">
              <td class="td-id">
                <span class="payment-id-badge">#{{ payment.id }}</span>
              </td>
              <td class="td-number">
                <strong class="payment-number">#{{ payment.payment_number || payment.id }}</strong>
              </td>
              <td class="td-customer">
                <div class="payment-customer-cell">
                  <strong class="customer-name">{{ payment.customer_name || 'N/A' }}</strong>
                  <a v-if="payment.customer_phone" :href="`tel:${payment.customer_phone}`" class="phone-link">
                    <CIcon icon="cil-phone" class="phone-icon" />
                    <span>{{ payment.customer_phone }}</span>
                  </a>
                  <small class="text-muted d-block" v-if="payment.invoice_number">
                    <CIcon icon="cil-file" class="me-1" />
                    {{ t('payments.invoice') }}: {{ payment.invoice_number }}
                  </small>
                </div>
              </td>
              <td class="td-amount">
                <strong class="unified-amount">
                  <CIcon icon="cil-money" class="money-icon" />
                  {{ formatCurrency(payment.amount || 0) }}
                </strong>
              </td>
              <td class="td-method">
                <CBadge class="unified-badge">
                  <CIcon :icon="getMethodIcon(payment.payment_method)" class="badge-icon" />
                  <span>{{ getPaymentMethodText(payment.payment_method) }}</span>
                </CBadge>
              </td>
              <td class="td-status">
                <CBadge class="unified-badge status-badge" :class="`status-${payment.status || 'pending'}`">
                  <CIcon :icon="getStatusIcon(payment.status)" class="badge-icon" />
                  <span>{{ getStatusText(payment.status) }}</span>
                </CBadge>
              </td>
              <td class="td-date">
                <div class="date-cell">
                  <CIcon icon="cil-calendar" class="date-icon" />
                  <span>{{ formatDate(payment.payment_date) }}</span>
                </div>
              </td>
              <td class="td-actions">
                <div class="actions-group">
                  <button class="action-btn" @click="viewPayment(payment)" :title="t('common.view')">
                    <CIcon icon="cil-info" />
                  </button>
                  <button v-if="payment.status === 'pending'" class="action-btn" @click="approvePayment(payment)" :title="t('payments.completed')">
                    <CIcon icon="cil-check" />
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

    <!-- View Payment Modal -->
    <CModal :visible="showViewModal" @close="closeViewModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-money" class="me-2" />
          {{ t('payments.paymentNumber') }} {{ viewingPayment?.payment_number || viewingPayment?.id || '' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <LoadingSpinner v-if="loadingDetails" :text="t('common.loading')" />
        <div v-else-if="viewingPayment" class="payment-details">
          <div class="details-grid">
            <div class="detail-row">
              <span class="k">{{ t('payments.customer') }}</span>
              <span class="v">{{ viewingPayment.customer_name || 'N/A' }}</span>
            </div>
            <div class="detail-row" v-if="viewingPayment.customer_phone">
              <span class="k">Phone</span>
              <span class="v">{{ viewingPayment.customer_phone }}</span>
            </div>
            <div class="detail-row">
              <span class="k">{{ t('payments.amount') }}</span>
              <span class="v">{{ formatCurrency(viewingPayment.amount || 0) }}</span>
            </div>
            <div class="detail-row">
              <span class="k">{{ t('payments.method') }}</span>
              <span class="v">{{ getPaymentMethodText(viewingPayment.payment_method) }}</span>
            </div>
            <div class="detail-row">
              <span class="k">{{ t('common.status') }}</span>
              <span class="v">{{ getStatusText(viewingPayment.status) }}</span>
            </div>
            <div class="detail-row">
              <span class="k">{{ t('payments.date') }}</span>
              <span class="v">{{ formatDate(viewingPayment.payment_date) }}</span>
            </div>
            <div class="detail-row" v-if="viewingPayment.invoice_number">
              <span class="k">{{ t('payments.invoice') }}</span>
              <span class="v">{{ viewingPayment.invoice_number }}</span>
            </div>
            <div class="detail-row" v-if="viewingPayment.reference_number">
              <span class="k">Reference</span>
              <span class="v">{{ viewingPayment.reference_number }}</span>
            </div>
            <div class="detail-row" v-if="viewingPayment.notes">
              <span class="k">Notes</span>
              <span class="v">{{ viewingPayment.notes }}</span>
            </div>
          </div>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeViewModal">{{ t('common.close') }}</CButton>
      </CModalFooter>
    </CModal>

    <!-- Create Payment Modal -->
    <CModal :visible="showCreateModal" @close="closeCreateModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('payments.addNew') }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6">
            <CFormSelect v-model="createForm.customer_id" :label="t('payments.customer')" class="filter-select">
              <option value="">{{ t('common.select') }}</option>
              <option v-for="c in customerOptions" :key="c.id" :value="c.id">{{ c.name }} - {{ c.phone }}</option>
            </CFormSelect>
          </CCol>
          <CCol :md="6">
            <CFormInput v-model.number="createForm.amount" type="number" step="0.001" min="0" :label="t('payments.amount')" class="filter-input" />
          </CCol>
          <CCol :md="6">
            <CFormSelect v-model="createForm.payment_method" :label="t('payments.method')" class="filter-select">
              <option value="cash">{{ t('payments.cash') }}</option>
              <option value="card">{{ t('payments.card') }}</option>
              <option value="knet">KNET</option>
              <option value="online">Online</option>
            </CFormSelect>
          </CCol>
          <CCol :md="6">
            <CFormSelect v-model="createForm.status" :label="t('common.status')" class="filter-select">
              <option value="completed">{{ t('payments.completed') }}</option>
              <option value="pending">{{ t('payments.pending') }}</option>
              <option value="failed">{{ t('payments.failed') }}</option>
            </CFormSelect>
          </CCol>
          <CCol :md="6">
            <CFormInput v-model="createForm.reference_number" label="Reference" class="filter-input" />
          </CCol>
          <CCol :md="6">
            <CFormInput v-model="createForm.payment_date" type="datetime-local" :label="t('payments.date')" class="filter-input" />
          </CCol>
          <CCol :md="12">
            <CFormInput v-model="createForm.notes" label="Notes" class="filter-input" />
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeCreateModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="btn-primary-custom" :disabled="creating" @click="createPayment">
          <CIcon icon="cil-save" class="me-2" />
          {{ t('common.save') }}
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
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api from '@/utils/api';
import { useToast } from '@/composables/useToast';

const { t } = useTranslation();
const toast = useToast();

const payments = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showViewModal = ref(false);
const viewingPayment = ref(null);
const loadingDetails = ref(false);
const creating = ref(false);
const customerOptions = ref([]);

const filters = ref({
  search: '',
  status: '',
  payment_method: '',
  date_from: '',
  date_to: '',
});

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});

const stats = computed(() => {
  const total = payments.value.length;
  const completed = payments.value.filter(p => p.status === 'completed').length;
  const pending = payments.value.filter(p => p.status === 'pending').length;
  const totalAmount = payments.value.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
  
  return {
    total,
    completed,
    pending,
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
    completed: 'success',
    pending: 'warning',
    failed: 'danger',
    refunded: 'secondary',
  };
  return colors[status] || 'secondary';
};

const getStatusText = (status) => {
  const texts = {
    completed: t('payments.completed'),
    pending: t('payments.pending'),
    failed: t('payments.failed'),
    refunded: t('payments.refunded'),
  };
  return texts[status] || status;
};

const getMethodColor = (method) => {
  const colors = {
    cash: 'success',
    card: 'info',
    knet: 'warning',
    online: 'primary',
  };
  return colors[method] || 'secondary';
};

const getPaymentMethodText = (method) => {
  const texts = {
    cash: t('payments.cash'),
    card: t('payments.card'),
    knet: 'KNET',
    online: 'Online',
  };
  return texts[method] || method;
};

const getMethodIcon = (method) => {
  const icons = {
    cash: 'cil-money',
    card: 'cil-credit-card',
    knet: 'cil-bank',
    online: 'cil-globe-alt',
  };
  return icons[method] || 'cil-info';
};

const getStatusIcon = (status) => {
  const icons = {
    completed: 'cil-check-circle',
    pending: 'cil-clock',
    failed: 'cil-x-circle',
    refunded: 'cil-reload',
  };
  return icons[status] || 'cil-info';
};

const loadPayments = async () => {
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

    const response = await api.get('/payments', { params, noCache: true });
    const data = response.data?.data || response.data || {};
    
    payments.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading payments:', error);
    toast.error(t('common.errorLoading'));
    payments.value = [];
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
  loadPayments();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadPayments();
  }, 500);
};

const resetFilters = () => {
  filters.value = { search: '', status: '', payment_method: '', date_from: '', date_to: '' };
  pagination.value.current_page = 1;
  loadPayments();
};

const exportData = () => {
  console.log('Exporting payments data...');
  alert(t('common.export') + ' - ' + t('payments.title'));
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewingPayment.value = null;
  loadingDetails.value = false;
};

const viewPayment = async (payment) => {
  showViewModal.value = true;
  loadingDetails.value = true;
  viewingPayment.value = payment;
  try {
    const res = await api.get(`/payments/${payment.id}`, { noCache: true });
    const data = res.data?.data || res.data || {};
    viewingPayment.value = { ...payment, ...data };
  } catch (e) {
    console.error('Error loading payment:', e);
    toast.error(t('common.errorLoading'));
  } finally {
    loadingDetails.value = false;
  }
};

const approvePayment = async (payment) => {
  if (!confirm(`Approve payment ${payment.payment_number}?`)) return;
  try {
    await api.put(`/payments/${payment.id}`, { status: 'completed' });
    toast.success(t('payments.completed'));
    loadPayments();
  } catch (error) {
    console.error('Error approving payment:', error);
    toast.error(t('common.errorLoading'));
  }
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  creating.value = false;
};

const openCreateModal = async () => {
  showCreateModal.value = true;
  await loadCustomerOptions();
};

const loadCustomerOptions = async () => {
  if (customerOptions.value.length > 0) return;
  try {
    const res = await api.get('/customers', { params: { per_page: 100 }, noCache: true });
    const data = res.data?.data || res.data || {};
    customerOptions.value = data.items || [];
  } catch (e) {
    console.error('Error loading customers:', e);
  }
};

const createForm = ref({
  customer_id: '',
  amount: 0,
  payment_method: 'cash',
  status: 'completed',
  reference_number: '',
  notes: '',
  payment_date: '',
});

const createPayment = async () => {
  if (!createForm.value.customer_id) {
    toast.error(`${t('payments.customer')} is required`);
    return;
  }
  if (!createForm.value.amount || Number(createForm.value.amount) <= 0) {
    toast.error('Amount must be greater than 0');
    return;
  }
  creating.value = true;
  try {
    const payload = {
      customer_id: Number(createForm.value.customer_id),
      amount: Number(createForm.value.amount),
      payment_method: createForm.value.payment_method,
      status: createForm.value.status,
      reference_number: createForm.value.reference_number || '',
      notes: createForm.value.notes || '',
      payment_date: createForm.value.payment_date ? new Date(createForm.value.payment_date).toISOString().slice(0, 19).replace('T', ' ') : undefined,
    };
    await api.post('/payments', payload);
    toast.success(t('common.save'));
    closeCreateModal();
    createForm.value = {
      customer_id: '',
      amount: 0,
      payment_method: 'cash',
      status: 'completed',
      reference_number: '',
      notes: '',
      payment_date: '',
    };
    loadPayments();
  } catch (e) {
    console.error('Create payment error:', e);
    toast.error(t('common.errorLoading'));
  } finally {
    creating.value = false;
  }
};

onMounted(() => {
  loadPayments();
});
</script>

<style scoped>
.payments-page { display:flex; flex-direction:column; gap:1.5rem; }
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:1.5rem; }

.filters-card { border:1px solid var(--border-color); box-shadow:0 2px 8px rgba(0,0,0,0.04); }
.search-input-group{ position:relative; }
.search-icon-wrapper{ background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.8) 100%); color:#fff; border-color:var(--asmaa-primary); }
.filter-input,.filter-select{ transition:all 0.3s cubic-bezier(0.4,0,0.2,1); border:1px solid var(--border-color); }
.filter-input:focus,.filter-select:focus{ border-color:var(--asmaa-primary); box-shadow:0 0 0 3px rgba(187,160,122,0.15); outline:none; }
.search-input:focus{ border-left:none; }
.reset-btn{ transition:all 0.3s; }
.reset-btn:hover{ background:var(--asmaa-primary); color:#fff; border-color:var(--asmaa-primary); transform:translateY(-1px); }

.btn-primary-custom{ background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.9) 100%); border:none; box-shadow:0 4px 12px rgba(187,160,122,0.3); transition:all 0.3s; }
.btn-primary-custom:hover{ background:linear-gradient(135deg, rgba(187,160,122,0.95) 0%, var(--asmaa-primary) 100%); box-shadow:0 6px 16px rgba(187,160,122,0.4); transform:translateY(-2px); }

.table-wrapper{ overflow-x:auto; border-radius:12px; border:1px solid var(--border-color); background:var(--bg-primary); }
.payments-table{ margin:0; border-collapse:separate; border-spacing:0; }
.table-header-row{ background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%); border-bottom:2px solid var(--asmaa-primary); }
.table-header-row th{ padding:1rem 1.25rem; font-weight:700; color:var(--text-primary); text-transform:uppercase; font-size:0.75rem; letter-spacing:0.5px; border-bottom:none; white-space:nowrap; }
.th-id{ width:80px; text-align:center; }
.th-number{ min-width:200px; }
.th-customer{ min-width:240px; }
.th-amount{ min-width:160px; }
.th-method{ min-width:160px; text-align:center; }
.th-status{ min-width:160px; text-align:center; }
.th-date{ min-width:160px; }
.th-actions{ width:160px; text-align:center; }

.payment-row{ transition:all 0.3s cubic-bezier(0.4,0,0.2,1); border-bottom:1px solid var(--border-color); }
.payment-row:hover{ background:linear-gradient(90deg, rgba(187,160,122,0.05) 0%, rgba(187,160,122,0.02) 100%); transform:translateX(4px); box-shadow:0 2px 8px rgba(187,160,122,0.1); }
[dir="rtl"] .payment-row:hover{ transform:translateX(-4px); }
.payment-row td{ padding:1rem 1.25rem; vertical-align:middle; border-bottom:1px solid var(--border-color); }

.payment-id-badge{ display:inline-flex; align-items:center; justify-content:center; width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.8) 100%); color:#fff; font-weight:800; font-size:0.875rem; box-shadow:0 2px 8px rgba(187,160,122,0.3); transition:all 0.3s; }
.payment-row:hover .payment-id-badge{ transform:scale(1.1); box-shadow:0 4px 12px rgba(187,160,122,0.4); }
.payment-number{ color:var(--asmaa-primary); font-weight:800; }

.payment-customer-cell{ display:flex; flex-direction:column; gap:0.375rem; }
.customer-name{ color:var(--text-primary); font-weight:700; }
.phone-link{ display:inline-flex; align-items:center; gap:0.5rem; color:var(--text-primary); text-decoration:none; padding:0.5rem 0.75rem; border-radius:8px; transition:all 0.3s; font-size:0.875rem; }
.phone-icon{ width:16px; height:16px; color:var(--asmaa-primary); transition:all 0.3s; }
.phone-link:hover{ background:rgba(187,160,122,0.1); color:var(--asmaa-primary); transform:translateX(2px); }
[dir="rtl"] .phone-link:hover{ transform:translateX(-2px); }
.phone-link:hover .phone-icon{ transform:scale(1.2); }

.unified-badge{ display:inline-flex; align-items:center; gap:0.375rem; padding:0.5rem 0.75rem; border-radius:8px; font-size:0.8125rem; font-weight:600; transition:all 0.3s; background:linear-gradient(135deg, rgba(187,160,122,0.15) 0%, rgba(187,160,122,0.1) 100%); color:var(--asmaa-primary); border:1px solid rgba(187,160,122,0.3); }
.badge-icon{ width:14px; height:14px; color:var(--asmaa-primary); }
.unified-badge:hover{ transform:translateY(-2px); background:linear-gradient(135deg, rgba(187,160,122,0.25) 0%, rgba(187,160,122,0.15) 100%); box-shadow:0 4px 8px rgba(187,160,122,0.2); border-color:var(--asmaa-primary); }
.status-badge.status-completed{ background:linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%); color:var(--asmaa-success); border-color:var(--asmaa-success-soft-border); }
.status-badge.status-pending{ background:linear-gradient(135deg, var(--asmaa-warning-soft) 0%, hsla(38, 92%, 50%, 0.10) 100%); color:var(--asmaa-warning); border-color:var(--asmaa-warning-soft-border); }
.status-badge.status-failed{ background:linear-gradient(135deg, var(--asmaa-danger-soft) 0%, hsla(0, 84%, 60%, 0.10) 100%); color:var(--asmaa-danger); border-color:var(--asmaa-danger-soft-border); }
.status-badge.status-refunded{ background:linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%); color:var(--asmaa-secondary); border-color:var(--asmaa-secondary-soft-border); }
.status-badge.status-completed .badge-icon{ color:var(--asmaa-success); }
.status-badge.status-pending .badge-icon{ color:var(--asmaa-warning); }
.status-badge.status-failed .badge-icon{ color:var(--asmaa-danger); }
.status-badge.status-refunded .badge-icon{ color:var(--asmaa-secondary); }

.unified-amount{ display:inline-flex; align-items:center; gap:0.5rem; color:var(--asmaa-primary); font-weight:800; font-size:0.9375rem; padding:0.375rem 0.75rem; border-radius:8px; background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%); transition:all 0.3s; }
.money-icon{ width:16px; height:16px; color:var(--asmaa-primary); }
.date-cell{ display:inline-flex; align-items:center; gap:0.5rem; color:var(--text-secondary); font-size:0.875rem; }
.date-icon{ width:16px; height:16px; color:var(--asmaa-primary); }
.actions-group{ display:flex; align-items:center; justify-content:center; gap:0.5rem; }
.action-btn{ width:36px; height:36px; border:none; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.9) 100%); color:#fff; box-shadow:0 2px 6px rgba(187,160,122,0.3); }
.action-btn:hover{ background:linear-gradient(135deg, rgba(187,160,122,0.95) 0%, var(--asmaa-primary) 100%); transform:translateY(-2px) scale(1.05); box-shadow:0 4px 12px rgba(187,160,122,0.4); }
.action-btn:active{ transform:translateY(0) scale(0.95); }

.payment-details .details-grid{ display:flex; flex-direction:column; gap:10px; }
.detail-row{ display:flex; justify-content:space-between; gap:12px; padding:12px 14px; border:1px solid var(--border-color); border-radius:12px; background:var(--bg-secondary); }
.detail-row .k{ color:var(--text-secondary); font-weight:800; }
.detail-row .v{ color:var(--text-primary); font-weight:800; }

@media (max-width:768px){
  .stats-grid{ grid-template-columns:1fr; }
  .actions-group{ flex-direction:column; gap:0.25rem; }
  .action-btn{ width:100%; height:32px; }
}
</style>

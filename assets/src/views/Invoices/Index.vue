<template>
  <div class="invoices-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('invoices.title')"
      :subtitle="t('invoices.subtitle') || (t('invoices.title') + ' - ' + t('dashboard.subtitle'))"
    >
      <template #icon>
        <CIcon icon="cil-file" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" @click="exportData" class="me-2 export-btn">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('invoices.title') }} {{ t('common.new') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('invoices.title')"
        :value="stats.total || invoices.length"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = ''; pagination.current_page = 1; loadInvoices(); }"
      >
        <template #icon>
          <CIcon icon="cil-file" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('invoices.paid')"
        :value="stats.paid"
        :badge="t('invoices.paid')"
        badge-variant="success"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = 'paid'; pagination.current_page = 1; loadInvoices(); }"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('invoices.unpaid')"
        :value="stats.unpaid"
        :badge="t('invoices.unpaid')"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = 'sent'; pagination.current_page = 1; loadInvoices(); }"
      >
        <template #icon>
          <CIcon icon="cil-warning" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('invoices.totalAmount')"
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
          <CFormSelect v-model="filters.status" @change="loadInvoices" class="filter-select">
            <option value="">{{ t('common.status') }}</option>
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="paid">{{ t('invoices.paid') }}</option>
            <option value="partial">Partially Paid</option>
            <option value="overdue">Overdue</option>
          </CFormSelect>
        </CCol>
        <CCol :md="2">
          <CFormInput
            v-model="filters.date_from"
            type="date"
            :label="t('reports.fromDate')"
            @change="loadInvoices"
            class="filter-input"
          />
        </CCol>
        <CCol :md="2">
          <CFormInput
            v-model="filters.date_to"
            type="date"
            :label="t('reports.toDate')"
            @change="loadInvoices"
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
    <Card :title="t('invoices.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="invoices.length === 0"
        :title="t('invoices.noInvoices') || t('common.noData')"
        :description="t('invoices.noInvoicesFound') || (t('invoices.title') + ' - ' + t('common.noData'))"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
            {{ t('invoices.title') }} {{ t('common.new') }}
          </CButton>
        </template>
      </EmptyState>

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern invoices-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-id">#</th>
              <th class="th-invoice">{{ t('invoices.invoiceNumber') }}</th>
              <th class="th-customer">{{ t('invoices.customer') }}</th>
              <th class="th-amount">{{ t('invoices.amount') }}</th>
              <th class="th-paid">Paid</th>
              <th class="th-due">Due</th>
              <th class="th-status">{{ t('common.status') }}</th>
              <th class="th-date">{{ t('invoices.date') }}</th>
              <th class="th-actions">{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="invoice in invoices"
              :key="invoice.id"
              class="table-row invoice-row"
              :class="{ 'highlight-row': Number(invoice.id) === Number(highlightInvoiceId) }"
              :data-invoice-id="invoice.id"
            >
              <td class="td-id">
                <span class="invoice-id-badge">#{{ invoice.id }}</span>
              </td>
              <td class="td-invoice">
                <strong class="invoice-number">#{{ invoice.invoice_number || invoice.id }}</strong>
              </td>
              <td class="td-customer">
                <div class="invoice-customer-cell">
                  <strong class="customer-name">{{ invoice.customer_name || 'N/A' }}</strong>
                  <a
                    v-if="invoice.customer_phone"
                    :href="`tel:${invoice.customer_phone}`"
                    class="phone-link"
                  >
                    <CIcon icon="cil-phone" class="phone-icon" />
                    <span>{{ invoice.customer_phone }}</span>
                  </a>
                </div>
              </td>
              <td class="td-amount">
                <strong class="unified-amount">
                  <CIcon icon="cil-money" class="money-icon" />
                  {{ formatCurrency(invoice.total || 0) }}
                </strong>
              </td>
              <td class="td-paid">
                <CBadge class="unified-badge">
                  <CIcon icon="cil-check-circle" class="badge-icon" />
                  <span>{{ formatCurrency(invoice.paid_amount || 0) }}</span>
                </CBadge>
              </td>
              <td class="td-due">
                <CBadge class="unified-badge" :class="Number(invoice.due_amount || 0) > 0 ? 'due-badge' : 'paid-badge'">
                  <CIcon :icon="Number(invoice.due_amount || 0) > 0 ? 'cil-warning' : 'cil-check-circle'" class="badge-icon" />
                  <span>{{ formatCurrency(invoice.due_amount || 0) }}</span>
                </CBadge>
              </td>
              <td class="td-status">
                <CBadge class="unified-badge status-badge" :class="`status-${invoice.status || 'draft'}`">
                  <CIcon :icon="getStatusIcon(invoice.status)" class="badge-icon" />
                  <span>{{ getStatusText(invoice.status) }}</span>
                </CBadge>
              </td>
              <td class="td-date">
                <div class="date-cell">
                  <CIcon icon="cil-calendar" class="date-icon" />
                  <span>{{ formatDate(invoice.issue_date) }}</span>
                </div>
              </td>
              <td class="td-actions">
                <div class="actions-group">
                  <button class="action-btn" @click="viewInvoice(invoice)" :title="t('common.view')">
                    <CIcon icon="cil-info" />
                  </button>
                  <button class="action-btn" @click="printInvoice(invoice)" :title="t('invoices.print')">
                    <CIcon icon="cil-print" />
                  </button>
                  <button
                    v-if="invoice.status !== 'paid'"
                    class="action-btn"
                    @click="openEditModal(invoice)"
                    :title="t('common.edit')"
                  >
                    <CIcon icon="cil-pencil" />
                  </button>
                  <button
                    v-if="Number(invoice.due_amount || 0) > 0"
                    class="action-btn"
                    @click="markAsPaid(invoice)"
                    :title="t('invoices.markPaid')"
                  >
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

    <!-- View Invoice Modal -->
    <CModal :visible="showViewModal" @close="closeViewModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-file" class="me-2" />
          {{ t('invoices.title') }} - {{ viewingInvoice?.invoice_number || viewingInvoice?.id || '' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <LoadingSpinner v-if="loadingInvoiceDetails" :text="t('common.loading')" />
        <div v-else-if="viewingInvoice" class="invoice-details-view">
          <div class="invoice-details-header">
            <div class="invoice-details-avatar">
              <CIcon icon="cil-user" />
            </div>
            <div class="invoice-details-info">
              <h4 class="invoice-customer-name">{{ viewingInvoice.customer_name || 'N/A' }}</h4>
              <div class="invoice-meta">
                <span class="meta-item">
                  <CIcon icon="cil-calendar" class="me-1" />
                  {{ formatDate(viewingInvoice.issue_date) }}
                </span>
                <span v-if="viewingInvoice.customer_phone" class="meta-item">
                  <CIcon icon="cil-phone" class="me-1" />
                  {{ viewingInvoice.customer_phone }}
                </span>
              </div>
            </div>
            <div class="invoice-details-actions">
              <CButton color="primary" class="btn-primary-custom" @click="printInvoice(viewingInvoice)">
                <CIcon icon="cil-print" class="me-2" />
                {{ t('invoices.print') }}
              </CButton>
            </div>
          </div>

          <div class="invoice-details-stats">
            <div class="stat-item">
              <CIcon icon="cil-money" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('invoices.amount') }}</div>
                <div class="stat-value">{{ formatCurrency(viewingInvoice.total || 0) }}</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-check-circle" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">Paid</div>
                <div class="stat-value">{{ formatCurrency(viewingInvoice.paid_amount || 0) }}</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-warning" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">Due</div>
                <div class="stat-value">{{ formatCurrency(viewingInvoice.due_amount || 0) }}</div>
              </div>
            </div>
          </div>

          <div class="invoice-items-card">
            <h6 class="items-title">Items</h6>
            <div v-if="(viewingInvoice.items || []).length === 0" class="text-muted">
              -
            </div>
            <CTable v-else responsive class="table-modern items-table">
              <thead>
                <tr class="table-header-row">
                  <th>Description</th>
                  <th style="width: 90px;">Qty</th>
                  <th style="width: 140px;">Unit</th>
                  <th style="width: 140px;">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="it in viewingInvoice.items" :key="it.id">
                  <td>{{ it.description }}</td>
                  <td>{{ it.quantity }}</td>
                  <td>{{ formatCurrency(it.unit_price) }}</td>
                  <td>{{ formatCurrency(it.total) }}</td>
                </tr>
              </tbody>
            </CTable>
          </div>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeViewModal">{{ t('common.close') }}</CButton>
      </CModalFooter>
    </CModal>

    <!-- Edit Invoice Modal -->
    <CModal :visible="showEditModal" @close="closeEditModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-pencil" class="me-2" />
          {{ t('common.edit') }} - {{ editingInvoice?.invoice_number || editingInvoice?.id || '' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6">
            <CFormSelect v-model="editForm.status" :label="t('common.status')" class="filter-select">
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="paid">{{ t('invoices.paid') }}</option>
              <option value="partial">Partially Paid</option>
              <option value="overdue">Overdue</option>
            </CFormSelect>
          </CCol>
          <CCol :md="6">
            <CFormInput v-model="editForm.paid_amount" type="number" step="0.001" label="Paid Amount" class="filter-input" />
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeEditModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="btn-primary-custom" :disabled="savingEdit" @click="saveInvoiceEdits">
          <CIcon icon="cil-save" class="me-2" />
          {{ t('common.save') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Create Invoice Modal (basic) -->
    <CModal :visible="showCreateModal" @close="closeCreateModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('invoices.title') }} {{ t('common.new') }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6">
            <CFormSelect v-model="createForm.customer_id" :label="t('invoices.customer')" class="filter-select">
              <option value="">{{ t('common.select') }}</option>
              <option v-for="c in customerOptions" :key="c.id" :value="c.id">
                {{ c.name }} - {{ c.phone }}
              </option>
            </CFormSelect>
          </CCol>
          <CCol :md="3">
            <CFormInput v-model="createForm.issue_date" type="date" :label="t('invoices.date')" class="filter-input" />
          </CCol>
          <CCol :md="3">
            <CFormSelect v-model="createForm.status" :label="t('common.status')" class="filter-select">
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
            </CFormSelect>
          </CCol>
        </CRow>

        <div class="create-items">
          <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
            <h6 class="m-0">Items</h6>
            <CButton color="primary" variant="outline" @click="addCreateItem">
              <CIcon icon="cil-plus" class="me-1" />
              Add Item
            </CButton>
          </div>

          <div v-for="(it, idx) in createForm.items" :key="idx" class="create-item-row">
            <CRow class="g-2 align-items-end">
              <CCol :md="6">
                <CFormInput v-model="it.description" label="Description" class="filter-input" />
              </CCol>
              <CCol :md="2">
                <CFormInput v-model.number="it.quantity" type="number" min="1" step="1" label="Qty" class="filter-input" />
              </CCol>
              <CCol :md="3">
                <CFormInput v-model.number="it.unit_price" type="number" min="0" step="0.001" label="Unit Price" class="filter-input" />
              </CCol>
              <CCol :md="1" class="d-flex">
                <CButton color="danger" variant="outline" class="w-100" @click="removeCreateItem(idx)">
                  <CIcon icon="cil-trash" />
                </CButton>
              </CCol>
            </CRow>
          </div>

          <div class="totals-box">
            <div class="totals-row">
              <span class="text-muted">Subtotal</span>
              <strong>{{ formatCurrency(createTotals.subtotal) }}</strong>
            </div>
            <div class="totals-row">
              <span class="text-muted">Total</span>
              <strong>{{ formatCurrency(createTotals.total) }}</strong>
            </div>
          </div>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeCreateModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="btn-primary-custom" :disabled="creatingInvoice" @click="createInvoice">
          <CIcon icon="cil-save" class="me-2" />
          {{ t('common.save') }}
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick, watch } from 'vue';
import { useRoute } from 'vue-router';
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
const route = useRoute();
const toast = useToast();

const invoices = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const highlightInvoiceId = ref(null);
const showViewModal = ref(false);
const viewingInvoice = ref(null);
const loadingInvoiceDetails = ref(false);

const showEditModal = ref(false);
const editingInvoice = ref(null);
const savingEdit = ref(false);

const creatingInvoice = ref(false);
const customerOptions = ref([]);

const filters = ref({
  search: '',
  status: '',
  date_from: '',
  date_to: '',
  customer_id: '',
});

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});

const stats = computed(() => {
  const total = invoices.value.length;
  const paid = invoices.value.filter(i => i.status === 'paid').length;
  const unpaid = invoices.value.filter(i => i.status !== 'paid' && i.status !== 'draft').length;
  const totalAmount = invoices.value.reduce((sum, i) => sum + (parseFloat(i.total) || 0), 0);
  
  return {
    total,
    paid,
    unpaid,
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
    draft: 'secondary',
    sent: 'info',
    paid: 'success',
    partial: 'warning',
    overdue: 'danger',
    cancelled: 'secondary',
  };
  return colors[status] || 'secondary';
};

const getStatusText = (status) => {
  const texts = {
    draft: 'Draft',
    sent: 'Sent',
    paid: t('invoices.paid'),
    partial: 'Partially Paid',
    overdue: 'Overdue',
    cancelled: 'Cancelled',
  };
  return texts[status] || status;
};

const getStatusIcon = (status) => {
  const icons = {
    draft: 'cil-file',
    sent: 'cil-paper-plane',
    paid: 'cil-check-circle',
    partial: 'cil-clock',
    overdue: 'cil-warning',
    cancelled: 'cil-x-circle',
  };
  return icons[status] || 'cil-info';
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadInvoices();
  }, 500);
};

const loadInvoices = async () => {
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

    const response = await api.get('/invoices', { params, noCache: true });
    const data = response.data?.data || response.data || {};
    
    invoices.value = data.items || [];
    pagination.value = data.pagination || pagination.value;

    // If we navigated from POS with invoice_id, highlight and scroll to it
    if (route.query.invoice_id) {
      highlightInvoiceId.value = Number(route.query.invoice_id);
      await nextTick();
      const el = document.querySelector(`[data-invoice-id="${highlightInvoiceId.value}"]`);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }
  } catch (error) {
    console.error('Error loading invoices:', error);
    toast.error(t('common.errorLoading'));
    invoices.value = [];
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
  loadInvoices();
};

const resetFilters = () => {
  filters.value = { search: '', status: '', date_from: '', date_to: '', customer_id: '' };
  pagination.value.current_page = 1;
  loadInvoices();
};

const exportData = () => {
  console.log('Exporting invoices data...');
  alert(t('common.export') + ' - ' + t('invoices.title'));
};

// ✅ Backend now creates Payment automatically - no need for frontend helper
const markAsPaid = async (invoice) => {
  if (!confirm(`${t('invoices.markPaid')}: ${invoice.invoice_number || invoice.id}?`)) return;
  try {
    await api.put(`/invoices/${invoice.id}`, {
      paid_amount: Number(invoice.total || 0),
    });
    
    toast.success(t('invoices.paid'));
    clearCache('/invoices');
    clearCache('/payments');
    loadInvoices();
  } catch (error) {
    console.error('Error marking invoice as paid:', error);
    toast.error(t('common.errorLoading'));
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewingInvoice.value = null;
  loadingInvoiceDetails.value = false;
};

const viewInvoice = async (invoice) => {
  showViewModal.value = true;
  loadingInvoiceDetails.value = true;
  viewingInvoice.value = {
    ...invoice,
    items: [],
  };
  try {
    const res = await api.get(`/invoices/${invoice.id}`, { noCache: true });
    const inv = res.data?.data || res.data || {};
    // keep name/phone from list join as fallback
    viewingInvoice.value = {
      ...invoice,
      ...inv,
      customer_name: inv.customer_name || invoice.customer_name,
      customer_phone: inv.customer_phone || invoice.customer_phone,
      items: Array.isArray(inv.items) ? inv.items : [],
    };
  } catch (e) {
    console.error('Error loading invoice details:', e);
    toast.error(t('common.errorLoading'));
  } finally {
    loadingInvoiceDetails.value = false;
  }
};

const editForm = ref({
  status: 'sent',
  paid_amount: 0,
});

const openEditModal = (invoice) => {
  editingInvoice.value = invoice;
  editForm.value = {
    status: invoice.status || 'sent',
    paid_amount: Number(invoice.paid_amount || 0),
  };
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editingInvoice.value = null;
  savingEdit.value = false;
};

const saveInvoiceEdits = async () => {
  if (!editingInvoice.value) return;
  savingEdit.value = true;
  try {
    await api.put(`/invoices/${editingInvoice.value.id}`, {
      status: editForm.value.status,
      paid_amount: Number(editForm.value.paid_amount || 0),
    });
    
    // ✅ Backend creates Payment automatically when status changes to 'paid'
    toast.success(t('common.save'));
    clearCache('/invoices');
    clearCache('/payments');
    closeEditModal();
    loadInvoices();
  } catch (e) {
    console.error('Save invoice error:', e);
    toast.error(t('common.errorLoading'));
  } finally {
    savingEdit.value = false;
  }
};

const printInvoice = async (invoice) => {
  try {
    const res = await api.get(`/invoices/${invoice.id}`, { noCache: true });
    const inv = res.data?.data || res.data || {};

    const items = Array.isArray(inv.items) ? inv.items : [];
    const customerName = invoice.customer_name || 'Customer';
    const invoiceNo = inv.invoice_number || invoice.invoice_number || invoice.id;
    const issueDate = inv.issue_date || invoice.issue_date || '';

    // eslint-disable-next-line no-undef
    const cfg = (typeof AsmaaSalonConfig !== 'undefined' && AsmaaSalonConfig) ? AsmaaSalonConfig : {};
    const logoUrl = cfg.logoUrl || 'https://asmaaljarallah.com/wp-content/uploads/2025/03/logoDark.png';
    const salonName = 'Asmaaljarallah';
    const isRtl = (document?.documentElement?.getAttribute('dir') || 'ltr') === 'rtl';

    const labels = isRtl
      ? {
          invoice: 'فاتورة',
          invoiceNo: 'رقم الفاتورة',
          date: 'التاريخ',
          customer: 'العميلة',
          description: 'الوصف',
          qty: 'الكمية',
          unit: 'السعر',
          total: 'الإجمالي',
          subtotal: 'المجموع',
          discount: 'الخصم',
          grandTotal: 'الإجمالي النهائي',
          thanks: 'شكراً لزيارتكم 💛',
        }
      : {
          invoice: 'Invoice',
          invoiceNo: 'Invoice No',
          date: 'Date',
          customer: 'Customer',
          description: 'Description',
          qty: 'Qty',
          unit: 'Unit',
          total: 'Total',
          subtotal: 'Subtotal',
          discount: 'Discount',
          grandTotal: 'Total',
          thanks: 'Thank you for your visit',
        };

    const html = `
      <html>
        <head>
          <meta charset="utf-8" />
          <title>${labels.invoice} ${invoiceNo}</title>
          <style>
            @page { size: A4; margin: 14mm; }
            html, body { height: auto; }
            body {
              font-family: Arial, sans-serif;
              padding: 0;
              margin: 0;
              color: #111;
              ${isRtl ? 'direction: rtl;' : 'direction: ltr;'}
            }
            .paper {
              padding: 14mm;
            }
            .top {
              display: flex;
              align-items: center;
              justify-content: space-between;
              gap: 16px;
              padding-bottom: 12px;
              border-bottom: 2px solid #BBA07A;
            }
            .brand {
              display: flex;
              align-items: center;
              gap: 12px;
            }
            .logo {
              width: 64px;
              height: 64px;
              border-radius: 14px;
              object-fit: contain;
              background: #fff;
              border: 1px solid #eee;
              padding: 6px;
            }
            .brand h1 {
              margin: 0;
              font-size: 18px;
              font-weight: 800;
              letter-spacing: 0.2px;
            }
            .brand .sub {
              margin-top: 2px;
              font-size: 12px;
              color: #666;
            }
            .meta-box {
              min-width: 240px;
              border: 1px solid #eee;
              border-radius: 12px;
              padding: 10px 12px;
              background: #fcfbfa;
            }
            .meta-row {
              display: flex;
              justify-content: space-between;
              gap: 12px;
              font-size: 12px;
              padding: 4px 0;
            }
            .meta-row .k { color: #666; font-weight: 700; }
            .meta-row .v { color: #111; font-weight: 800; }
            .divider {
              height: 10px;
            }
            table {
              width: 100%;
              border-collapse: separate;
              border-spacing: 0;
              margin-top: 14px;
              border: 1px solid #eee;
              border-radius: 12px;
              overflow: hidden;
            }
            thead th {
              background: linear-gradient(135deg, rgba(187,160,122,0.18) 0%, rgba(187,160,122,0.08) 100%);
              color: #111;
              font-size: 12px;
              text-align: ${isRtl ? 'right' : 'left'};
              padding: 10px 12px;
              border-bottom: 1px solid #eee;
              font-weight: 800;
            }
            tbody td {
              padding: 10px 12px;
              font-size: 12px;
              border-bottom: 1px solid #f1f1f1;
              vertical-align: top;
            }
            tbody tr:last-child td { border-bottom: none; }
            .num { text-align: ${isRtl ? 'left' : 'right'}; white-space: nowrap; }
            .desc { color: #111; font-weight: 700; }
            .totals {
              margin-top: 14px;
              display: flex;
              justify-content: ${isRtl ? 'flex-start' : 'flex-end'};
            }
            .totals .box {
              width: 320px;
              border: 1px solid #eee;
              border-radius: 12px;
              padding: 10px 12px;
              background: #fcfbfa;
            }
            .totals .row {
              display: flex;
              justify-content: space-between;
              gap: 12px;
              padding: 6px 0;
              font-size: 12px;
            }
            .totals .row .k { color: #666; font-weight: 800; }
            .totals .row .v { font-weight: 900; }
            .totals .grand {
              margin-top: 6px;
              padding-top: 10px;
              border-top: 1px dashed rgba(187,160,122,0.6);
              font-size: 14px;
            }
            .footer {
              margin-top: 18px;
              display: flex;
              justify-content: center;
              color: #666;
              font-size: 12px;
              padding-top: 10px;
              border-top: 1px solid #eee;
            }
          </style>
        </head>
        <body>
          <div class="paper">
            <div class="top">
              <div class="brand">
                ${logoUrl ? `<img class="logo" src="${logoUrl}" alt="Logo" />` : ''}
                <div>
                  <h1>${salonName}</h1>
                  <div class="sub">${labels.invoice}</div>
                </div>
              </div>
              <div class="meta-box">
                <div class="meta-row"><span class="k">${labels.invoiceNo}</span><span class="v">${invoiceNo}</span></div>
                <div class="meta-row"><span class="k">${labels.date}</span><span class="v">${issueDate}</span></div>
                <div class="meta-row"><span class="k">${labels.customer}</span><span class="v">${customerName}</span></div>
              </div>
            </div>

          <table>
            <thead>
              <tr>
                <th>${labels.description}</th>
                <th style="width:80px;" class="num">${labels.qty}</th>
                <th style="width:120px;" class="num">${labels.unit}</th>
                <th style="width:120px;" class="num">${labels.total}</th>
              </tr>
            </thead>
            <tbody>
              ${items.map(i => `
                <tr>
                  <td class="desc">${(i.description ?? '').toString()}</td>
                  <td class="num">${i.quantity ?? 1}</td>
                  <td class="num">${Number(i.unit_price ?? 0).toFixed(3)} KWD</td>
                  <td class="num">${Number(i.total ?? 0).toFixed(3)} KWD</td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div class="totals">
            <div class="box">
              <div class="row"><span class="k">${labels.subtotal}</span><span class="v">${Number(inv.subtotal ?? invoice.subtotal ?? 0).toFixed(3)} KWD</span></div>
              <div class="row"><span class="k">${labels.discount}</span><span class="v">${Number(inv.discount ?? invoice.discount ?? 0).toFixed(3)} KWD</span></div>
              <div class="row grand"><span class="k">${labels.grandTotal}</span><span class="v">${Number(inv.total ?? invoice.total ?? 0).toFixed(3)} KWD</span></div>
            </div>
          </div>

          <div class="footer">${labels.thanks}</div>
          </div>
        </body>
      </html>
    `;

    const w = window.open('', '_blank');
    if (!w) {
      toast.error('Popup blocked');
      return;
    }
    w.document.open();
    w.document.write(html);
    w.document.close();
    setTimeout(() => {
      try {
        w.focus();
        w.print();
      } catch (_) {}
    }, 250);
  } catch (e) {
    console.error('Print invoice error:', e);
    toast.error(t('common.errorLoading'));
  }
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  creatingInvoice.value = false;
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
    console.error('Error loading customers for invoice:', e);
  }
};

const createForm = ref({
  customer_id: '',
  issue_date: new Date().toISOString().slice(0, 10),
  status: 'draft',
  items: [
    { description: '', quantity: 1, unit_price: 0 },
  ],
});

const createTotals = computed(() => {
  const subtotal = (createForm.value.items || []).reduce((sum, it) => {
    const qty = Number(it.quantity || 0);
    const unit = Number(it.unit_price || 0);
    return sum + qty * unit;
  }, 0);
  return {
    subtotal,
    total: subtotal,
  };
});

const addCreateItem = () => {
  createForm.value.items.push({ description: '', quantity: 1, unit_price: 0 });
};

const removeCreateItem = (idx) => {
  if (createForm.value.items.length <= 1) return;
  createForm.value.items.splice(idx, 1);
};

const createInvoice = async () => {
  if (!createForm.value.customer_id) {
    toast.error(`${t('invoices.customer')} is required`);
    return;
  }
  creatingInvoice.value = true;
  try {
    const subtotal = createTotals.value.subtotal;
    if (subtotal <= 0) {
      toast.error('Total must be greater than 0');
      creatingInvoice.value = false;
      return;
    }
    const items = createForm.value.items.map((it) => {
      const qty = Number(it.quantity || 0);
      const unit = Number(it.unit_price || 0);
      return {
        description: (it.description || '').toString(),
        quantity: qty,
        unit_price: unit,
        total: qty * unit,
      };
    }).filter((it) => it.description || it.total > 0);

    const payload = {
      customer_id: Number(createForm.value.customer_id),
      issue_date: createForm.value.issue_date,
      status: createForm.value.status,
      subtotal,
      discount: 0,
      tax: 0,
      total: subtotal,
      items,
    };

    const response = await api.post('/invoices', payload);
    
    // ✅ Backend creates Payment automatically if status is 'paid'
    toast.success(t('common.save'));
    clearCache('/invoices');
    clearCache('/payments');
    closeCreateModal();
    createForm.value = {
      customer_id: '',
      issue_date: new Date().toISOString().slice(0, 10),
      status: 'draft',
      items: [{ description: '', quantity: 1, unit_price: 0 }],
    };
    loadInvoices();
  } catch (e) {
    console.error('Create invoice error:', e);
    toast.error(t('common.errorLoading'));
  } finally {
    creatingInvoice.value = false;
  }
};

onMounted(() => {
  if (route.query.customer_id) {
    filters.value.customer_id = String(route.query.customer_id);
  }
  loadInvoices();
});

watch(
  () => route.query.customer_id,
  (val) => {
    if (val) {
      filters.value.customer_id = String(val);
      pagination.value.current_page = 1;
      loadInvoices();
    }
  }
);
</script>

<style scoped>
.invoices-page {
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

/* Primary button (same as Customers) */
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

/* Table wrapper */
.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
}

.invoices-table {
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
.th-invoice {
  min-width: 160px;
}
.th-customer {
  min-width: 240px;
}
.th-amount,
.th-paid,
.th-due {
  min-width: 170px;
}
.th-status {
  min-width: 160px;
  text-align: center;
}
.th-date {
  min-width: 160px;
}
.th-actions {
  width: 200px;
  text-align: center;
}

.invoice-row {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid var(--border-color);
}

.invoice-row:last-child {
  border-bottom: none;
}

.invoice-row:hover {
  background: linear-gradient(90deg, rgba(187, 160, 122, 0.05) 0%, rgba(187, 160, 122, 0.02) 100%);
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(187, 160, 122, 0.1);
}

[dir="rtl"] .invoice-row:hover {
  transform: translateX(-4px);
}

.invoice-row td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  border-bottom: 1px solid var(--border-color);
}

.invoice-id-badge {
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

.invoice-row:hover .invoice-id-badge {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

.invoice-number {
  color: var(--asmaa-primary);
  font-weight: 800;
}

.invoice-customer-cell {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.customer-name {
  color: var(--text-primary);
  font-weight: 700;
}

.phone-link {
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

.phone-icon {
  width: 16px;
  height: 16px;
  color: var(--asmaa-primary);
  transition: all 0.3s;
}

.phone-link:hover {
  background: rgba(187, 160, 122, 0.1);
  color: var(--asmaa-primary);
  transform: translateX(2px);
}

[dir="rtl"] .phone-link:hover {
  transform: translateX(-2px);
}

.phone-link:hover .phone-icon {
  transform: scale(1.2);
}

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

.due-badge {
  background: linear-gradient(135deg, var(--asmaa-danger-soft) 0%, hsla(0, 84%, 60%, 0.10) 100%);
  color: var(--asmaa-danger);
  border-color: var(--asmaa-danger-soft-border);
}

.due-badge .badge-icon {
  color: var(--asmaa-danger);
}

.paid-badge {
  background: linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);
  color: var(--asmaa-success);
  border-color: var(--asmaa-success-soft-border);
}

.paid-badge .badge-icon {
  color: var(--asmaa-success);
}

.status-badge.status-paid {
  background: linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);
  color: var(--asmaa-success);
  border-color: var(--asmaa-success-soft-border);
}

.status-badge.status-overdue {
  background: linear-gradient(135deg, var(--asmaa-danger-soft) 0%, hsla(0, 84%, 60%, 0.10) 100%);
  color: var(--asmaa-danger);
  border-color: var(--asmaa-danger-soft-border);
}

.status-badge.status-partial {
  background: linear-gradient(135deg, var(--asmaa-warning-soft) 0%, hsla(38, 92%, 50%, 0.10) 100%);
  color: var(--asmaa-warning);
  border-color: var(--asmaa-warning-soft-border);
}

.status-badge.status-sent {
  background: linear-gradient(135deg, var(--asmaa-primary-soft) 0%, hsla(35, 30%, 61%, 0.10) 100%);
  color: var(--asmaa-primary);
  border-color: var(--asmaa-primary-soft-border);
}

.status-badge.status-draft {
  background: linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);
  color: var(--asmaa-secondary);
  border-color: var(--asmaa-secondary-soft-border);
}

.unified-amount {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--asmaa-primary);
  font-weight: 800;
  font-size: 0.9375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  transition: all 0.3s;
}

.money-icon {
  width: 16px;
  height: 16px;
  color: var(--asmaa-primary);
}

.date-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.date-icon {
  width: 16px;
  height: 16px;
  color: var(--asmaa-primary);
}

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

.highlight-row {
  outline: 2px solid var(--asmaa-primary);
  outline-offset: -2px;
  background: rgba(187, 160, 122, 0.08);
}

/* View modal (same spirit as Customers) */
.invoice-details-view {
  padding: 0.5rem 0;
}

.invoice-details-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(187, 160, 122, 0.2);
}

.invoice-details-avatar {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.8) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
  flex-shrink: 0;
}

.invoice-details-avatar :deep(svg) {
  width: 36px;
  height: 36px;
}

.invoice-details-info {
  flex: 1;
}

.invoice-customer-name {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--text-primary);
}

.invoice-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.invoice-details-actions {
  flex-shrink: 0;
}

.invoice-details-stats {
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
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(187, 160, 122, 0.15);
}

.stat-icon {
  width: 28px;
  height: 28px;
  color: var(--asmaa-primary);
  flex-shrink: 0;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1rem;
  font-weight: 800;
  color: var(--text-primary);
}

.invoice-items-card {
  padding: 1rem;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  background: var(--bg-primary);
}

.items-title {
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.75rem;
}

.create-item-row {
  padding: 0.75rem;
  border: 1px dashed rgba(187, 160, 122, 0.35);
  border-radius: 12px;
  margin-bottom: 0.75rem;
  background: rgba(187, 160, 122, 0.03);
}

.totals-box {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid rgba(187, 160, 122, 0.2);
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.08) 0%, rgba(187, 160, 122, 0.03) 100%);
}

.totals-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.375rem 0;
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .invoice-details-stats {
    grid-template-columns: 1fr;
  }

  .invoice-details-header {
    flex-direction: column;
    text-align: center;
  }

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

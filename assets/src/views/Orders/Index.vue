<template>
  <div class="orders-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('orders.title')"
      :subtitle="t('orders.subtitle') || (t('orders.title') + ' - ' + t('dashboard.subtitle'))"
    >
      <template #icon>
        <CIcon icon="cil-cart" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" @click="exportData" class="me-2 export-btn">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('orders.addNew') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('orders.title')"
        :value="stats.total"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="() => { resetFilters(); }"
      >
        <template #icon>
          <CIcon icon="cil-cart" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('orders.pending')"
        :value="stats.pending"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = 'pending'; pagination.current_page = 1; loadOrders(); }"
      >
        <template #icon>
          <CIcon icon="cil-clock" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('orders.completed')"
        :value="stats.completed"
        badge-variant="success"
        color="gold"
        :clickable="true"
        @click="() => { filters.status = 'completed'; pagination.current_page = 1; loadOrders(); }"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('orders.totalRevenue') || 'Total Revenue'"
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
              :placeholder="t('orders.searchPlaceholder') || (t('common.search') + '...')"
              @input="debounceSearch"
              class="filter-input search-input"
            />
          </CInputGroup>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.status" @change="loadOrders" class="filter-select">
            <option value="">{{ t('common.status') }}</option>
            <option value="pending">{{ t('orders.pending') }}</option>
            <option value="completed">{{ t('orders.completed') }}</option>
            <option value="cancelled">{{ t('orders.cancelled') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.payment_status" @change="loadOrders" class="filter-select">
            <option value="">{{ t('orders.paymentStatus') || 'Payment Status' }}</option>
            <option value="unpaid">{{ t('invoices.unpaid') }}</option>
            <option value="partial">{{ t('orders.partiallyPaid') || 'Partially Paid' }}</option>
            <option value="paid">{{ t('invoices.paid') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.payment_method" @change="loadOrders" class="filter-select">
            <option value="">{{ t('orders.paymentMethod') || 'Payment Method' }}</option>
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
            @change="loadOrders"
            class="filter-input"
          />
        </CCol>
        <CCol :md="2">
          <CFormInput
            v-model="filters.date_to"
            type="date"
            :label="t('reports.toDate')"
            @change="loadOrders"
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
    <Card :title="t('orders.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="orders.length === 0"
        :title="t('orders.noOrders') || t('common.noData')"
        :description="t('orders.noOrdersFound') || (t('orders.title') + ' - ' + t('common.noData'))"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
            <CIcon icon="cil-plus" class="me-2" />
            {{ t('orders.addNew') }}
          </CButton>
        </template>
      </EmptyState>

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern orders-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-order">{{ t('orders.orderNumber') }}</th>
              <th class="th-customer">{{ t('orders.customer') }}</th>
              <th class="th-staff">{{ t('staff.title') }}</th>
              <th class="th-total">{{ t('orders.total') }}</th>
              <th class="th-status">{{ t('common.status') }}</th>
              <th class="th-payment">{{ t('orders.paymentStatus') || 'Payment Status' }}</th>
              <th class="th-date">{{ t('orders.date') }}</th>
              <th class="th-actions">{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id" class="table-row">
              <td class="td-order">
                <strong class="order-number">#{{ order.order_number || order.id }}</strong>
              </td>
              <td class="td-customer">
                <div class="order-customer-cell">
                  <strong class="customer-name">{{ order.customer_name || '—' }}</strong>
                  <a v-if="order.customer_phone" :href="`tel:${order.customer_phone}`" class="phone-link">
                    <CIcon icon="cil-phone" class="phone-icon" />
                    <span>{{ order.customer_phone }}</span>
                  </a>
                </div>
              </td>
              <td class="td-staff">
                <CBadge class="unified-badge secondary-badge">
                  <CIcon icon="cil-user" class="badge-icon" />
                  <span>{{ order.staff_name || '-' }}</span>
                </CBadge>
              </td>
              <td class="td-total">
                <strong class="unified-amount">
                  <CIcon icon="cil-money" class="money-icon" />
                  {{ formatCurrency(order.total || 0) }}
                </strong>
              </td>
              <td class="td-status">
                <CBadge class="unified-badge status-badge" :class="`status-${order.status || 'pending'}`">
                  <CIcon :icon="getStatusIcon(order.status)" class="badge-icon" />
                  <span>{{ getStatusText(order.status) }}</span>
                </CBadge>
              </td>
              <td class="td-payment">
                <CBadge class="unified-badge payment-badge" :class="`payment-${order.payment_status || 'unpaid'}`">
                  <CIcon :icon="getPaymentStatusIcon(order.payment_status)" class="badge-icon" />
                  <span>{{ getPaymentStatusText(order.payment_status) }}</span>
                </CBadge>
              </td>
              <td class="td-date">
                <div class="date-cell">
                  <CIcon icon="cil-calendar" class="date-icon" />
                  <span>{{ formatDate(order.created_at) }}</span>
                </div>
              </td>
              <td class="td-actions">
                <div class="actions-group">
                  <button class="action-btn" @click="viewOrder(order)" :title="t('common.view')">
                    <CIcon icon="cil-info" />
                  </button>
                  <button class="action-btn" @click="printOrder(order)" :title="t('orders.print') || t('common.print')">
                    <CIcon icon="cil-print" />
                  </button>
                  <button
                    v-if="order.status === 'pending'"
                    class="action-btn"
                    @click="completeOrder(order)"
                    :title="t('orders.completed')"
                  >
                    <CIcon icon="cil-check" />
                  </button>
                  <button
                    class="action-btn action-btn-danger"
                    @click="deleteOrder(order)"
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

    <!-- View Order Modal -->
    <CModal :visible="showViewModal" @close="closeViewModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-cart" class="me-2" />
          {{ t('orders.orderNumber') }} {{ viewingOrder?.order_number || viewingOrder?.id || '' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <LoadingSpinner v-if="loadingDetails" :text="t('common.loading')" />
        <div v-else-if="viewingOrder" class="order-details">
          <div class="order-details-header">
            <div class="order-details-title">
              <h5 class="mb-0">{{ t('orders.orderNumber') }} {{ viewingOrder.order_number || viewingOrder.id }}</h5>
              <div class="order-details-meta">
                <span class="meta-item">
                  <CIcon icon="cil-calendar" class="me-1" />
                  {{ formatDate(viewingOrder.created_at) }}
                </span>
                <span v-if="viewingOrder.customer_phone" class="meta-item">
                  <CIcon icon="cil-phone" class="me-1" />
                  {{ viewingOrder.customer_phone }}
                </span>
              </div>
            </div>
            <div class="order-details-actions">
              <CButton color="primary" class="btn-primary-custom" @click="printOrder(viewingOrder)">
                <CIcon icon="cil-print" class="me-2" />
                {{ t('orders.print') || t('common.print') }}
              </CButton>
            </div>
          </div>

          <div class="details-grid">
            <div class="detail-row">
              <span class="k">{{ t('orders.customer') }}</span>
              <span class="v">{{ viewingOrder.customer_name || '—' }}</span>
            </div>
            <div class="detail-row" v-if="viewingOrder.staff_name">
              <span class="k">{{ t('staff.title') }}</span>
              <span class="v">{{ viewingOrder.staff_name }}</span>
            </div>
            <div class="detail-row">
              <span class="k">{{ t('orders.status') }}</span>
              <span class="v">
                <CBadge class="unified-badge status-badge" :class="`status-${viewingOrder.status || 'pending'}`">
                  <CIcon :icon="getStatusIcon(viewingOrder.status)" class="badge-icon" />
                  <span>{{ getStatusText(viewingOrder.status) }}</span>
                </CBadge>
              </span>
            </div>
            <div class="detail-row">
              <span class="k">{{ t('orders.paymentStatus') }}</span>
              <span class="v">
                <CBadge class="unified-badge payment-badge" :class="`payment-${viewingOrder.payment_status || 'unpaid'}`">
                  <CIcon :icon="getPaymentStatusIcon(viewingOrder.payment_status)" class="badge-icon" />
                  <span>{{ getPaymentStatusText(viewingOrder.payment_status) }}</span>
                </CBadge>
              </span>
            </div>
            <div class="detail-row" v-if="viewingOrder.payment_method">
              <span class="k">{{ t('orders.paymentMethod') }}</span>
              <span class="v">{{ getPaymentMethodText(viewingOrder.payment_method) }}</span>
            </div>
          </div>

          <div class="order-details-stats">
            <div class="stat-item">
              <CIcon icon="cil-money" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('orders.subtotal') }}</div>
                <div class="stat-value">{{ formatCurrency(viewingOrder.subtotal || 0) }}</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-tag" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('orders.discount') }}</div>
                <div class="stat-value">{{ formatCurrency(viewingOrder.discount || 0) }}</div>
              </div>
            </div>
            <div class="stat-item">
              <CIcon icon="cil-calculator" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('orders.tax') }}</div>
                <div class="stat-value">{{ formatCurrency(viewingOrder.tax || 0) }}</div>
              </div>
            </div>
            <div class="stat-item stat-item-highlight">
              <CIcon icon="cil-dollar" class="stat-icon" />
              <div class="stat-content">
                <div class="stat-label">{{ t('orders.total') }}</div>
                <div class="stat-value">{{ formatCurrency(viewingOrder.total || 0) }}</div>
              </div>
            </div>
          </div>

          <div class="detail-row" v-if="viewingOrder.notes">
            <span class="k">{{ t('orders.notes') }}</span>
            <span class="v">{{ viewingOrder.notes }}</span>
          </div>

          <div class="mt-4">
            <h6 class="mb-2">{{ t('orders.items') }}</h6>
            <div v-if="(viewingOrder.items || []).length === 0" class="text-muted">
              {{ t('orders.noItems') }}
            </div>
            <CTable v-else hover responsive class="table-modern">
              <thead>
                <tr>
                  <th>{{ t('orders.itemName') }}</th>
                  <th>{{ t('orders.quantity') }}</th>
                  <th>{{ t('orders.unitPrice') }}</th>
                  <th>{{ t('orders.total') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="it in viewingOrder.items" :key="it.id">
                  <td>
                    <div class="fw-semibold">{{ it.item_name }}</div>
                    <div class="text-muted small">{{ it.item_type }}</div>
                  </td>
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

    <!-- Create Order Modal (Quick) -->
    <CModal :visible="showCreateModal" @close="closeCreateModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('orders.addNew') }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6">
            <CFormSelect v-model="createForm.customer_id" :label="t('orders.customer')" class="filter-select">
              <option value="">{{ t('common.select') || 'Select' }}</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">
                {{ c.name }} ({{ c.phone }})
              </option>
            </CFormSelect>
          </CCol>
          <CCol :md="6">
            <CFormInput v-model="createForm.total" type="number" step="0.001" :label="t('orders.total')" class="filter-input" />
          </CCol>
          <CCol :md="4">
            <CFormSelect v-model="createForm.status" :label="t('orders.status')" class="filter-select">
              <option value="pending">{{ t('orders.pending') }}</option>
              <option value="completed">{{ t('orders.completed') }}</option>
              <option value="cancelled">{{ t('orders.cancelled') }}</option>
            </CFormSelect>
          </CCol>
          <CCol :md="4">
            <CFormSelect v-model="createForm.payment_status" :label="t('orders.paymentStatus')" class="filter-select">
              <option value="unpaid">{{ t('invoices.unpaid') }}</option>
              <option value="partial">{{ t('orders.partiallyPaid') }}</option>
              <option value="paid">{{ t('invoices.paid') }}</option>
            </CFormSelect>
          </CCol>
          <CCol :md="4">
            <CFormSelect v-model="createForm.payment_method" :label="t('orders.paymentMethod')" class="filter-select">
              <option value="cash">{{ t('payments.cash') }}</option>
              <option value="card">{{ t('payments.card') }}</option>
              <option value="knet">KNET</option>
              <option value="online">Online</option>
            </CFormSelect>
          </CCol>
          <CCol :md="12">
            <CFormInput v-model="createForm.notes" :label="t('orders.notes')" class="filter-input" />
          </CCol>
        </CRow>
        <div class="text-muted small mt-2">
          {{ t('orders.createHint') || 'Tip: For detailed items (products/services), create orders from POS.' }}
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeCreateModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="btn-primary-custom" :disabled="creating" @click="createOrder">
          <CIcon icon="cil-save" class="me-2" />
          {{ t('common.save') }}
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {
  CButton,
  CTable,
  CBadge,
  CPagination,
  CFormSelect,
  CFormInput,
  CRow,
  CCol,
  CInputGroup,
  CInputGroupText,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
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

const orders = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const creating = ref(false);

const showViewModal = ref(false);
const loadingDetails = ref(false);
const viewingOrder = ref(null);

const customers = ref([]);

const filters = ref({
  search: '',
  status: '',
  payment_status: '',
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

const stats = ref({
  total: 0,
  pending: 0,
  completed: 0,
  totalRevenue: 0,
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
    completed: 'success',
    cancelled: 'secondary',
  };
  return colors[status] || 'secondary';
};

const getStatusText = (status) => {
  const texts = {
    pending: t('orders.pending'),
    completed: t('orders.completed'),
    cancelled: t('orders.cancelled'),
  };
  return texts[status] || status;
};

const getPaymentStatusColor = (status) => {
  const colors = {
    unpaid: 'danger',
    partial: 'warning',
    paid: 'success',
  };
  return colors[status] || 'secondary';
};

const getPaymentStatusText = (status) => {
  const texts = {
    unpaid: t('invoices.unpaid'),
    partial: t('orders.partiallyPaid') || 'Partially Paid',
    paid: t('invoices.paid'),
  };
  return texts[status] || status;
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

const getStatusIcon = (status) => {
  const icons = {
    pending: 'cil-clock',
    completed: 'cil-check-circle',
    cancelled: 'cil-x-circle',
  };
  return icons[status] || 'cil-info';
};

const getPaymentStatusIcon = (status) => {
  const icons = {
    unpaid: 'cil-x-circle',
    partial: 'cil-clock',
    paid: 'cil-check-circle',
  };
  return icons[status] || 'cil-info';
};

const loadOrders = async () => {
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

    const response = await api.get('/orders', { params, noCache: true });
    const data = response.data?.data || response.data || {};
    
    orders.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
    stats.value = data.stats || stats.value;
  } catch (error) {
    console.error('Error loading orders:', error);
    orders.value = [];
    stats.value = { total: 0, pending: 0, completed: 0, totalRevenue: 0 };
    pagination.value = {
      current_page: 1,
      per_page: 20,
      total: 0,
      total_pages: 0,
    };
    toast.error(t('common.errorLoading') || 'Error loading data');
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  pagination.value.current_page = page;
  loadOrders();
};

const exportData = () => {
  exportOrdersCsv();
};

const viewOrder = (order) => {
  openViewModal(order);
};

const printOrder = async (order) => {
  try {
    const res = await api.get(`/orders/${order.id}`, { noCache: true });
    const ord = res.data?.data || res.data || order;

    const items = Array.isArray(ord.items) ? ord.items : [];
    const customerName = ord.customer_name || 'Customer';
    const orderNo = ord.order_number || ord.id;
    const orderDate = ord.created_at || '';
    const statusText = getStatusText(ord.status);

    // Build items HTML
    let itemsHtml = '';
    if (items.length > 0) {
      const itemsRows = items.map(it => {
        const itemName = (it.item_name || '—').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return '<tr><td>' + itemName + '</td><td>' + (it.quantity || 0) + '</td><td>' + formatCurrency(it.unit_price || 0) + '</td><td>' + formatCurrency(it.total || 0) + '</td></tr>';
      }).join('');
      
      itemsHtml = '<table><thead><tr><th>العنصر</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>' + itemsRows + '</tbody></table>';
    }

    // Build notes HTML
    const notesHtml = ord.notes 
      ? '<div style="margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 8px;"><strong>ملاحظات:</strong> ' + ord.notes.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>'
      : '';

    const html = '<!DOCTYPE html>' +
      '<html dir="rtl" lang="ar">' +
      '<head>' +
      '<meta charset="UTF-8">' +
      '<title>Order ' + orderNo + '</title>' +
      '<style>' +
      '* { margin: 0; padding: 0; box-sizing: border-box; }' +
      'body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; padding: 40px; direction: rtl; }' +
      '.header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }' +
      '.header h1 { font-size: 24px; margin-bottom: 10px; }' +
      '.order-info { display: flex; justify-content: space-between; margin-bottom: 30px; }' +
      '.info-item { flex: 1; }' +
      '.info-label { font-weight: bold; color: #666; }' +
      '.info-value { font-size: 18px; margin-top: 5px; }' +
      'table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }' +
      'th, td { padding: 12px; text-align: right; border-bottom: 1px solid #ddd; }' +
      'th { background: #f5f5f5; font-weight: bold; }' +
      '.totals { margin-top: 20px; text-align: left; }' +
      '.total-row { display: flex; justify-content: space-between; padding: 8px 0; }' +
      '.total-row.final { font-size: 20px; font-weight: bold; border-top: 2px solid #000; padding-top: 15px; margin-top: 10px; }' +
      '@media print { body { padding: 20px; } .no-print { display: none; } }' +
      '</style>' +
      '</head>' +
      '<body>' +
      '<div class="header">' +
      '<h1>🛒 طلب #' + orderNo + '</h1>' +
      '<p>صالون أسماء الجارالله</p>' +
      '</div>' +
      '<div class="order-info">' +
      '<div class="info-item">' +
      '<div class="info-label">العميل:</div>' +
      '<div class="info-value">' + customerName.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>' +
      '</div>' +
      '<div class="info-item">' +
      '<div class="info-label">التاريخ:</div>' +
      '<div class="info-value">' + orderDate + '</div>' +
      '</div>' +
      '<div class="info-item">' +
      '<div class="info-label">الحالة:</div>' +
      '<div class="info-value">' + statusText + '</div>' +
      '</div>' +
      '</div>' +
      itemsHtml +
      '<div class="totals">' +
      '<div class="total-row">' +
      '<span>المجموع:</span>' +
      '<span>' + formatCurrency(ord.subtotal || 0) + '</span>' +
      '</div>' +
      '<div class="total-row">' +
      '<span>الخصم:</span>' +
      '<span>' + formatCurrency(ord.discount || 0) + '</span>' +
      '</div>' +
      '<div class="total-row">' +
      '<span>الضريبة:</span>' +
      '<span>' + formatCurrency(ord.tax || 0) + '</span>' +
      '</div>' +
      '<div class="total-row final">' +
      '<span>الإجمالي النهائي:</span>' +
      '<span>' + formatCurrency(ord.total || 0) + '</span>' +
      '</div>' +
      '</div>' +
      notesHtml +
      '<script>window.onload = function() { window.print(); }<\/script>' +
      '<\/body><\/html>';

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
    console.error('Print error:', e);
    toast.error(t('common.error') || 'Error');
  }
};

const deleteOrder = async (order) => {
  if (!confirm(t('orders.deleteConfirm') + ' ' + order.order_number + '?')) return;
  try {
    await api.delete(`/orders/${order.id}`);
    toast.success(t('common.deleted') || 'Deleted');
    loadOrders();
  } catch (error) {
    console.error('Error deleting order:', error);
    toast.error(t('orders.deleteError') || 'Error deleting order');
  }
};

const completeOrder = async (order) => {
  if (!confirm(`Complete order ${order.order_number}?`)) return;
  try {
    await api.post(`/orders/${order.id}/complete`);
    toast.success(t('orders.completed') || 'Completed');
    loadOrders();
  } catch (error) {
    console.error('Error completing order:', error);
    toast.error(t('common.error') || 'Error');
  }
};

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    payment_status: '',
    payment_method: '',
    date_from: '',
    date_to: '',
  };
  pagination.value.current_page = 1;
  loadOrders();
};

let searchTimer = null;
const debounceSearch = () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    pagination.value.current_page = 1;
    loadOrders();
  }, 400);
};

const loadCustomers = async () => {
  try {
    const response = await api.get('/customers', { params: { per_page: 200, page: 1 }, noCache: true });
    const data = response.data?.data || response.data || {};
    customers.value = data.items || [];
  } catch (e) {
    customers.value = [];
  }
};

const openViewModal = async (order) => {
  showViewModal.value = true;
  viewingOrder.value = null;
  loadingDetails.value = true;
  try {
    const response = await api.get(`/orders/${order.id}`, { noCache: true });
    const data = response.data?.data || response.data;
    viewingOrder.value = data || null;
  } catch (e) {
    viewingOrder.value = order; // fallback to row data
  } finally {
    loadingDetails.value = false;
  }
};

const closeViewModal = () => {
  showViewModal.value = false;
  viewingOrder.value = null;
};

const openCreateModal = async () => {
  showCreateModal.value = true;
  if (customers.value.length === 0) {
    await loadCustomers();
  }
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  creating.value = false;
  createForm.value = {
    customer_id: '',
    total: '',
    status: 'completed',
    payment_status: 'paid',
    payment_method: 'cash',
    notes: 'Manual Order',
  };
};

const createForm = ref({
  customer_id: '',
  total: '',
  status: 'completed',
  payment_status: 'paid',
  payment_method: 'cash',
  notes: 'Manual Order',
});

const createOrder = async () => {
  creating.value = true;
  try {
    const total = parseFloat(createForm.value.total || '0');
    if (!createForm.value.customer_id || !total) {
      toast.error(t('common.error') || 'Error');
      return;
    }

    const payload = {
      customer_id: Number(createForm.value.customer_id),
      subtotal: total,
      discount: 0,
      tax: 0,
      total,
      status: createForm.value.status,
      payment_status: createForm.value.payment_status,
      payment_method: createForm.value.payment_method,
      notes: createForm.value.notes,
      items: [],
    };

    await api.post('/orders', payload);
    toast.success(t('common.saved') || 'Saved');
    closeCreateModal();
    pagination.value.current_page = 1;
    await loadOrders();
  } catch (e) {
    console.error(e);
    toast.error(t('orders.saveError') || 'Error saving order');
  } finally {
    creating.value = false;
  }
};

const exportOrdersCsv = async () => {
  try {
    const perPage = 100;
    let page = 1;
    let all = [];

    while (true) {
      const params = { page, per_page: perPage, ...filters.value };
      Object.keys(params).forEach((k) => {
        if (params[k] === '') delete params[k];
      });

      const response = await api.get('/orders', { params, noCache: true });
      const data = response.data?.data || response.data || {};
      const items = data.items || [];
      const paginationMeta = data.pagination || {};

      all = all.concat(items);
      const totalPages = Number(paginationMeta.total_pages || 0);
      if (!totalPages || page >= totalPages) break;
      page += 1;
    }

    const headers = [
      t('orders.orderNumber'),
      t('orders.customer'),
      t('orders.total'),
      t('orders.status'),
      t('orders.paymentStatus'),
      t('orders.paymentMethod'),
      t('orders.date'),
    ];

    const escape = (v) => `"${String(v ?? '').replaceAll('"', '""')}"`;

    const rows = all.map((o) => [
      o.order_number,
      o.customer_name,
      o.total,
      o.status,
      o.payment_status,
      o.payment_method,
      o.created_at,
    ].map(escape).join(','));

    const csv = [headers.map(escape).join(','), ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `orders-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  } catch (e) {
    console.error(e);
    toast.error(t('common.error') || 'Error');
  }
};

onMounted(async () => {
  await Promise.all([loadOrders(), loadCustomers()]);
});
</script>

<style scoped>
.orders-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.btn-primary-custom {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  border: none;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.25);
  transition: all 0.25s ease;
}

.btn-primary-custom:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(187, 160, 122, 0.35);
}

.filters-card {
  border: 1px solid var(--border-color);
}

.export-btn {
  border-color: var(--border-color);
}

.search-input-group :deep(.form-control) {
  border-left: none;
}

.search-icon-wrapper {
  background: var(--bg-secondary);
  border-color: var(--border-color);
  color: var(--text-secondary);
}

.filter-input,
.filter-select {
  border-color: var(--border-color);
}

.reset-btn {
  border-color: var(--border-color);
}

.table-wrapper {
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
}

.table-modern {
  margin: 0;
}

.table-header-row th {
  background: var(--bg-secondary);
  color: var(--text-secondary);
  font-weight: 800;
  border-bottom: 1px solid var(--border-color);
}

.table-row {
  transition: all 0.2s;
}

.table-row:hover {
  background-color: var(--bg-tertiary);
}

.order-number {
  color: var(--asmaa-primary);
  font-size: 1rem;
}

.order-customer-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.customer-name {
  color: var(--text-primary);
  font-weight: 900;
}

.phone-link {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.825rem;
}

.phone-link:hover {
  color: var(--asmaa-primary);
}

.phone-icon,
.date-icon {
  color: var(--text-secondary);
}

.unified-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 800;
  font-size: 0.85rem;
  padding: 0.35rem 0.6rem;
  border-radius: 999px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.secondary-badge {
  background: var(--asmaa-secondary-soft);
  border-color: var(--asmaa-secondary-soft-border);
  color: var(--asmaa-secondary);
}

.status-badge.status-pending {
  background: var(--asmaa-warning-soft);
  border-color: var(--asmaa-warning-soft-border);
  color: var(--asmaa-warning);
}
.status-badge.status-completed {
  background: var(--asmaa-success-soft);
  border-color: var(--asmaa-success-soft-border);
  color: var(--asmaa-success);
}
.status-badge.status-cancelled {
  background: var(--asmaa-secondary-soft);
  border-color: var(--asmaa-secondary-soft-border);
  color: var(--asmaa-secondary);
}

.payment-badge.payment-unpaid {
  background: var(--asmaa-danger-soft);
  border-color: var(--asmaa-danger-soft-border);
  color: var(--asmaa-danger);
}
.payment-badge.payment-partial {
  background: var(--asmaa-warning-soft);
  border-color: var(--asmaa-warning-soft-border);
  color: var(--asmaa-warning);
}
.payment-badge.payment-paid {
  background: var(--asmaa-success-soft);
  border-color: var(--asmaa-success-soft-border);
  color: var(--asmaa-success);
}

.badge-icon {
  font-size: 0.95rem;
}

.unified-amount {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 900;
  color: var(--text-primary);
}

.date-cell {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
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
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  color: #fff;
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

.action-btn-danger {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
}

.action-btn-danger:hover {
  background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

/* Order Details Modal */
.order-details {
  padding: 0;
}

.order-details-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 2px solid var(--border-color);
}

.order-details-title h5 {
  color: var(--text-primary);
  font-weight: 900;
  margin-bottom: 8px;
}

.order-details-meta {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.order-details-actions {
  display: flex;
  gap: 8px;
}

.order-details-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
  margin: 24px 0;
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: var(--bg-primary);
  border-radius: 8px;
  border: 1px solid var(--border-color);
}

.stat-item-highlight {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.1) 0%, rgba(187, 160, 122, 0.05) 100%);
  border-color: var(--asmaa-primary);
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
  font-weight: 700;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 1.125rem;
  font-weight: 900;
  color: var(--text-primary);
}

.details-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  background: var(--bg-secondary);
}

.detail-row .k {
  color: var(--text-secondary);
  font-weight: 800;
}

.detail-row .v {
  color: var(--text-primary);
  font-weight: 800;
}

/* Responsive */
@media (max-width: 768px) {
  .actions-group {
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .action-btn {
    width: 100%;
    height: 32px;
  }
  
  .order-details-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .order-details-stats {
    grid-template-columns: 1fr;
  }
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>

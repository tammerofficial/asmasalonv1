<template>
  <div class="products-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('products.title')"
      :subtitle="t('products.subtitle') || (t('products.title') + ' - ' + t('dashboard.subtitle'))"
    >
      <template #icon>
        <CIcon icon="cil-basket" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" @click="exportData" class="me-2 export-btn">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('products.addNew') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        :label="t('products.title')"
        :value="stats.total || products.length"
        badge-variant="info"
        color="gold"
        :clickable="true"
        @click="() => { filters.is_active = ''; filters.low_stock = false; pagination.current_page = 1; loadProducts(); }"
      >
        <template #icon>
          <CIcon icon="cil-basket" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('products.active')"
        :value="stats.active"
        badge-variant="success"
        color="gold"
        :clickable="true"
        @click="() => { filters.is_active = 1; pagination.current_page = 1; loadProducts(); }"
      >
        <template #icon>
          <CIcon icon="cil-check-circle" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('products.lowStock')"
        :value="stats.lowStock"
        badge-variant="warning"
        color="gold"
        :clickable="true"
        @click="() => { filters.low_stock = true; pagination.current_page = 1; loadProducts(); }"
      >
        <template #icon>
          <CIcon icon="cil-warning" />
        </template>
      </StatCard>

      <StatCard 
        :label="t('products.totalValue')"
        :value="formatCurrencyShort(stats.totalValue)"
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
          <CFormSelect v-model="filters.category" @change="loadProducts" class="filter-select">
            <option value="">{{ t('products.category') }}</option>
            <option value="Hair Care">Hair Care</option>
            <option value="Skincare">Skincare</option>
            <option value="Nails">Nails</option>
            <option value="Accessories">Accessories</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormSelect v-model="filters.is_active" @change="loadProducts" class="filter-select">
            <option value="">{{ t('common.status') }}</option>
            <option value="1">{{ t('products.active') }}</option>
            <option value="0">{{ t('products.inactive') }}</option>
          </CFormSelect>
        </CCol>
        <CCol :md="2">
          <CButton color="secondary" variant="outline" @click="resetFilters" class="w-100 reset-btn">
            <CIcon icon="cil-reload" class="me-1" />
            {{ t('common.reset') }}
          </CButton>
        </CCol>
        <CCol :md="12">
          <CFormCheck v-model="filters.low_stock" :label="t('products.lowStock')" @change="loadProducts" />
        </CCol>
      </CRow>
    </Card>

    <!-- Products Table -->
    <Card :title="t('products.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="products.length === 0"
        :title="t('common.noData')"
        :description="t('products.title') + ' - ' + t('common.noData')"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" class="btn-primary-custom" @click="openCreateModal">
            {{ t('products.addNew') }}
          </CButton>
        </template>
      </EmptyState>

      <div v-else class="table-wrapper">
        <CTable hover responsive class="table-modern products-table">
          <thead>
            <tr class="table-header-row">
              <th class="th-id">#</th>
              <th class="th-name">{{ t('products.name') }}</th>
              <th class="th-sku">{{ t('products.sku') }}</th>
              <th class="th-purchase">{{ t('products.purchasePrice') }}</th>
              <th class="th-price">{{ t('products.sellingPrice') }}</th>
              <th class="th-stock">{{ t('products.stock') }}</th>
              <th class="th-status">{{ t('common.status') }}</th>
              <th class="th-actions">{{ t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id" class="table-row product-row">
              <td class="td-id">
                <span class="product-id-badge">#{{ product.id }}</span>
              </td>
              <td class="td-name">
                <div class="product-name-cell">
                  <strong class="product-name">{{ product.name_ar || product.name }}</strong>
                  <small class="product-category" v-if="product.category">
                    <CIcon icon="cil-tag" class="tag-icon" />
                    {{ product.category }}
                  </small>
                </div>
              </td>
              <td class="td-sku">
                <code class="sku-code">{{ product.sku || 'N/A' }}</code>
              </td>
              <td class="td-purchase">
                <strong class="unified-amount">
                  <CIcon icon="cil-dollar" class="money-icon" />
                  {{ formatCurrency(product.purchase_price || 0) }}
                </strong>
              </td>
              <td class="td-price">
                <strong class="unified-amount">
                  <CIcon icon="cil-money" class="money-icon" />
                  {{ formatCurrency(product.selling_price || product.price || 0) }}
                </strong>
              </td>
              <td class="td-stock">
                <CBadge class="unified-badge" :class="(product.stock_quantity || 0) <= (product.min_stock_level || 5) ? 'stock-low' : 'stock-ok'">
                  <CIcon icon="cil-storage" class="badge-icon" />
                  <span>{{ product.stock_quantity || 0 }}</span>
                </CBadge>
              </td>
              <td class="td-status">
                <CBadge class="unified-badge status-badge" :class="product.is_active ? 'status-active' : 'status-inactive'">
                  <CIcon :icon="product.is_active ? 'cil-check-circle' : 'cil-x-circle'" class="badge-icon" />
                  <span>{{ product.is_active ? t('products.active') : t('products.inactive') }}</span>
                </CBadge>
              </td>
              <td class="td-actions">
                <div class="actions-group">
                  <button class="action-btn" @click="viewProduct(product)" :title="t('common.view')"><CIcon icon="cil-info" /></button>
                  <button class="action-btn" @click="editProduct(product)" :title="t('common.edit')"><CIcon icon="cil-pencil" /></button>
                  <button class="action-btn" @click="deleteProduct(product)" :title="t('common.delete')"><CIcon icon="cil-trash" /></button>
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

    <!-- Create / Edit Modal -->
    <CModal :visible="showCreateModal || showEditModal" @close="closeModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon :icon="showEditModal ? 'cil-pencil' : 'cil-plus'" class="me-2" />
          {{ showEditModal ? t('products.edit') : t('products.addNew') }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6">
            <CFormInput v-model="form.name_ar" label="Arabic Name" class="filter-input" />
          </CCol>
          <CCol :md="6">
            <CFormInput v-model="form.name" label="Name" class="filter-input" />
          </CCol>
          <CCol :md="6">
            <CFormInput v-model="form.sku" :label="t('products.sku')" class="filter-input" />
          </CCol>
          <CCol :md="6">
            <CFormSelect v-model="form.category" :label="t('products.category')" class="filter-select">
              <option value="">{{ t('common.select') }}</option>
              <option value="Hair Care">Hair Care</option>
              <option value="Skincare">Skincare</option>
              <option value="Nails">Nails</option>
              <option value="Accessories">Accessories</option>
            </CFormSelect>
          </CCol>
          <CCol :md="4">
            <CFormInput v-model.number="form.purchase_price" type="number" step="0.001" min="0" :label="t('products.purchasePrice')" class="filter-input" />
          </CCol>
          <CCol :md="4">
            <CFormInput v-model.number="form.selling_price" type="number" step="0.001" min="0" :label="t('products.sellingPrice')" class="filter-input" />
          </CCol>
          <CCol :md="4">
            <CFormInput v-model.number="form.stock_quantity" type="number" step="1" min="0" :label="t('products.stock')" class="filter-input" />
          </CCol>
          <CCol :md="4">
            <CFormInput v-model.number="form.min_stock_level" type="number" step="1" min="0" :label="t('products.lowStock')" class="filter-input" />
          </CCol>
          <CCol :md="8" class="d-flex align-items-center">
            <CFormCheck v-model="form.is_active" :label="t('products.active')" />
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="btn-primary-custom" :disabled="saving" @click="saveProduct">
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
  CFormInput,
  CFormSelect,
  CFormCheck,
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

const products = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingProduct = ref(null);
const saving = ref(false);

const filters = ref({
  search: '',
  category: '',
  is_active: '',
  low_stock: false,
});

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});


const stats = computed(() => {
  const total = products.value.length;
  const active = products.value.filter(p => p.is_active).length;
  const lowStock = products.value.filter(p => p.stock_quantity <= (p.min_stock_level || 5)).length;
  const totalValue = products.value.reduce((sum, p) => {
    const quantity = p.stock_quantity || 0;
    const price = parseFloat(p.purchase_price) || 0;
    return sum + (quantity * price);
  }, 0);
  
  return {
    total,
    active,
    lowStock,
    totalValue,
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

const loadProducts = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
      ...filters.value,
    };

    if (filters.value.low_stock) {
      params.low_stock = 1;
    }

    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === false) delete params[key];
    });

    const response = await api.get('/products', { params, noCache: true });
    const data = response.data?.data || response.data || {};
    
    products.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading products:', error);
    toast.error(t('common.errorLoading'));
    products.value = [];
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
  loadProducts();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1;
    loadProducts();
  }, 500);
};

const resetFilters = () => {
  filters.value = { search: '', category: '', is_active: '', low_stock: false };
  pagination.value.current_page = 1;
  loadProducts();
};

const exportData = () => {
  console.log('Exporting products data...');
  alert(t('common.export') + ' - ' + t('products.title'));
};

const viewProduct = (product) => {
  console.log('View product:', product);
  alert(`Product: ${product.name_ar || product.name}\nSKU: ${product.sku}\nStock: ${product.stock_quantity}\nPrice: ${formatCurrency(product.selling_price || product.price)}`);
};

const openCreateModal = () => {
  showCreateModal.value = true;
  showEditModal.value = false;
  editingProduct.value = null;
  form.value = {
    name: '',
    name_ar: '',
    sku: '',
    category: '',
    purchase_price: 0,
    selling_price: 0,
    stock_quantity: 0,
    min_stock_level: 5,
    is_active: true,
  };
};

const editProduct = (product) => {
  showEditModal.value = true;
  showCreateModal.value = false;
  editingProduct.value = product;
  form.value = {
    name: product.name || '',
    name_ar: product.name_ar || '',
    sku: product.sku || '',
    category: product.category || '',
    purchase_price: Number(product.purchase_price || 0),
    selling_price: Number(product.selling_price || product.price || 0),
    stock_quantity: Number(product.stock_quantity || 0),
    min_stock_level: Number(product.min_stock_level || 5),
    is_active: !!product.is_active,
  };
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingProduct.value = null;
  saving.value = false;
};

const form = ref({
  name: '',
  name_ar: '',
  sku: '',
  category: '',
  purchase_price: 0,
  selling_price: 0,
  stock_quantity: 0,
  min_stock_level: 5,
  is_active: true,
});

const saveProduct = async () => {
  if (!form.value.name || !form.value.sku) {
    toast.error('Name and SKU are required');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      ...form.value,
      purchase_price: Number(form.value.purchase_price || 0),
      selling_price: Number(form.value.selling_price || 0),
      stock_quantity: Number(form.value.stock_quantity || 0),
      min_stock_level: Number(form.value.min_stock_level || 0),
      is_active: form.value.is_active ? 1 : 0,
    };

    if (editingProduct.value?.id) {
      await api.put(`/products/${editingProduct.value.id}`, payload);
    } else {
      await api.post('/products', payload);
    }

    toast.success(t('common.save'));
    closeModal();
    loadProducts();
  } catch (e) {
    console.error('Save product error:', e);
    toast.error(t('products.saveError') || t('common.errorLoading'));
  } finally {
    saving.value = false;
  }
};

const deleteProduct = async (product) => {
  if (!confirm(`${t('products.deleteConfirm')} ${product.name_ar || product.name}?`)) return;

  try {
    await api.delete(`/products/${product.id}`);
    toast.success(t('common.delete'));
    loadProducts();
  } catch (error) {
    console.error('Error deleting product:', error);
    toast.error(t('products.deleteError') || t('common.errorLoading'));
  }
};

// Check route query for tab
onMounted(() => {
  loadProducts();
});
</script>

<style scoped>
.products-page{display:flex;flex-direction:column;gap:1.5rem;}
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
.products-table{margin:0;border-collapse:separate;border-spacing:0;}
.table-header-row{background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%);border-bottom:2px solid var(--asmaa-primary);}
.table-header-row th{padding:1rem 1.25rem;font-weight:700;color:var(--text-primary);text-transform:uppercase;font-size:0.75rem;letter-spacing:0.5px;border-bottom:none;white-space:nowrap;}
.th-id{width:80px;text-align:center;}
.th-name{min-width:240px;}
.th-sku{min-width:140px;}
.th-purchase,.th-price{min-width:170px;}
.th-stock{min-width:130px;text-align:center;}
.th-status{min-width:160px;text-align:center;}
.th-actions{width:160px;text-align:center;}

.product-row{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);border-bottom:1px solid var(--border-color);}
.product-row:hover{background:linear-gradient(90deg, rgba(187,160,122,0.05) 0%, rgba(187,160,122,0.02) 100%);transform:translateX(4px);box-shadow:0 2px 8px rgba(187,160,122,0.1);}
[dir="rtl"] .product-row:hover{transform:translateX(-4px);}
.product-row td{padding:1rem 1.25rem;vertical-align:middle;border-bottom:1px solid var(--border-color);}

.product-id-badge{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.8) 100%);color:#fff;font-weight:800;font-size:0.875rem;box-shadow:0 2px 8px rgba(187,160,122,0.3);transition:all 0.3s;}
.product-row:hover .product-id-badge{transform:scale(1.1);box-shadow:0 4px 12px rgba(187,160,122,0.4);}

.product-name-cell{display:flex;flex-direction:column;gap:0.375rem;}
.product-name{color:var(--text-primary);font-weight:800;}
.product-category{display:inline-flex;align-items:center;gap:0.375rem;font-size:0.8125rem;color:var(--text-secondary);}
.tag-icon{width:14px;height:14px;color:var(--asmaa-primary);}

.sku-code{background:var(--bg-secondary);padding:0.25rem 0.5rem;border-radius:8px;font-size:0.8125rem;color:var(--text-secondary);border:1px solid var(--border-color);}

.unified-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 0.75rem;border-radius:8px;font-size:0.8125rem;font-weight:600;transition:all 0.3s;background:linear-gradient(135deg, rgba(187,160,122,0.15) 0%, rgba(187,160,122,0.1) 100%);color:var(--asmaa-primary);border:1px solid rgba(187,160,122,0.3);}
.badge-icon{width:14px;height:14px;color:var(--asmaa-primary);}
.unified-badge:hover{transform:translateY(-2px);background:linear-gradient(135deg, rgba(187,160,122,0.25) 0%, rgba(187,160,122,0.15) 100%);box-shadow:0 4px 8px rgba(187,160,122,0.2);border-color:var(--asmaa-primary);}

.stock-low{background:linear-gradient(135deg, var(--asmaa-danger-soft) 0%, hsla(0, 84%, 60%, 0.10) 100%);color:var(--asmaa-danger);border-color:var(--asmaa-danger-soft-border);}
.stock-low .badge-icon{color:var(--asmaa-danger);}
.stock-ok{background:linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);color:var(--asmaa-success);border-color:var(--asmaa-success-soft-border);}
.stock-ok .badge-icon{color:var(--asmaa-success);}

.status-badge.status-active{background:linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);color:var(--asmaa-success);border-color:var(--asmaa-success-soft-border);}
.status-badge.status-active .badge-icon{color:var(--asmaa-success);}
.status-badge.status-inactive{background:linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);color:var(--asmaa-secondary);border-color:var(--asmaa-secondary-soft-border);}
.status-badge.status-inactive .badge-icon{color:var(--asmaa-secondary);}

.unified-amount{display:inline-flex;align-items:center;gap:0.5rem;color:var(--asmaa-primary);font-weight:800;font-size:0.9375rem;padding:0.375rem 0.75rem;border-radius:8px;background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%);transition:all 0.3s;}
.money-icon{width:16px;height:16px;color:var(--asmaa-primary);}

.actions-group{display:flex;align-items:center;justify-content:center;gap:0.5rem;}
.action-btn{width:36px;height:36px;border:none;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.3s cubic-bezier(0.4,0,0.2,1);background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.9) 100%);color:#fff;box-shadow:0 2px 6px rgba(187,160,122,0.3);}
.action-btn:hover{background:linear-gradient(135deg, rgba(187,160,122,0.95) 0%, var(--asmaa-primary) 100%);transform:translateY(-2px) scale(1.05);box-shadow:0 4px 12px rgba(187,160,122,0.4);}
.action-btn:active{transform:translateY(0) scale(0.95);}

@media (max-width:768px){
  .stats-grid{grid-template-columns:1fr;}
  .actions-group{flex-direction:column;gap:0.25rem;}
  .action-btn{width:100%;height:32px;}
}
</style>

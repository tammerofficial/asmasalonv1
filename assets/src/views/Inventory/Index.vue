<template>
  <div class="inventory-page">
    <!-- Page Header -->
    <PageHeader 
      :title="t('inventory.title')"
      :subtitle="t('inventory.title') + ' - ' + t('dashboard.subtitle')"
    >
      <template #icon>
        <CIcon icon="cil-storage" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" @click="exportData" class="me-2">
          <CIcon icon="cil-cloud-download" class="me-2" />
          {{ t('common.export') }}
        </CButton>
        <CButton color="primary" @click="showAdjustModal = true">
          <CIcon icon="cil-plus" class="me-2" />
          Adjust Stock
        </CButton>
      </template>
    </PageHeader>

    <!-- Low Stock Alert -->
    <Card v-if="lowStockItems.length > 0" color="warning" icon="cil-warning">
      <template #title>
        <strong>Low Stock Alert</strong>
      </template>
      <template #default>
        <p class="mb-0">{{ lowStockItems.length }} {{ t('inventory.product') }} with low stock</p>
      </template>
    </Card>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <StatCard 
        label="Total Movements"
        :value="stats.totalMovements || movements.length"
        badge-variant="info"
        color="blue"
      >
        <template #icon>
          <CIcon icon="cil-list" />
        </template>
      </StatCard>

      <StatCard 
        label="Low Stock Items"
        :value="stats.lowStock"
        badge-variant="warning"
        color="yellow"
      >
        <template #icon>
          <CIcon icon="cil-warning" />
        </template>
      </StatCard>

      <StatCard 
        label="Total Products"
        :value="stats.totalProducts"
        badge-variant="info"
        color="green"
      >
        <template #icon>
          <CIcon icon="cil-basket" />
        </template>
      </StatCard>

      <StatCard 
        label="Total Value"
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
    <Card :title="t('common.filter')" icon="cil-filter">
      <CRow class="g-3">
        <CCol :md="3">
          <CFormSelect v-model="filters.type" :label="t('inventory.type')" @change="loadMovements">
            <option value="">{{ t('inventory.type') }}</option>
            <option value="purchase">Purchase</option>
            <option value="sale">Sale</option>
            <option value="adjustment">Adjustment</option>
            <option value="return">Return</option>
          </CFormSelect>
        </CCol>
        <CCol :md="3">
          <CFormInput
            v-model="filters.date_from"
            type="date"
            :label="t('reports.fromDate')"
            @change="loadMovements"
          />
        </CCol>
        <CCol :md="3">
          <CFormInput
            v-model="filters.date_to"
            type="date"
            :label="t('reports.toDate')"
            @change="loadMovements"
          />
        </CCol>
        <CCol :md="3">
          <CButton color="secondary" variant="outline" @click="resetFilters" class="w-100">
            {{ t('common.reset') }}
          </CButton>
        </CCol>
      </CRow>
    </Card>

    <!-- Table -->
    <Card :title="t('inventory.title')" icon="cil-list">
      <LoadingSpinner v-if="loading" :text="t('common.loading')" />
      
      <EmptyState 
        v-else-if="movements.length === 0"
        :title="t('common.noData')"
        :description="t('inventory.title') + ' - ' + t('common.noData')"
        icon-color="gray"
      >
        <template #action>
          <CButton color="primary" @click="showAdjustModal = true">
            Adjust Stock
          </CButton>
        </template>
      </EmptyState>

      <CTable v-else hover responsive class="table-modern">
        <thead>
          <tr>
            <th>{{ t('inventory.date') }}</th>
            <th>{{ t('inventory.product') }}</th>
            <th>{{ t('inventory.type') }}</th>
            <th>{{ t('inventory.quantity') }}</th>
            <th>Before</th>
            <th>After</th>
            <th>Cost</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="movement in movements" :key="movement.id" class="table-row">
            <td>{{ formatDate(movement.movement_date) }}</td>
            <td>
              <div class="movement-product-cell">
                <strong>{{ movement.product_name || 'N/A' }}</strong>
                <small class="text-muted d-block" v-if="movement.product_sku">
                  SKU: {{ movement.product_sku }}
                </small>
              </div>
            </td>
            <td>
              <CBadge :color="getTypeColor(movement.type)" class="type-badge">
                <CIcon :icon="getTypeIcon(movement.type)" class="me-1" />
                {{ getTypeText(movement.type) }}
              </CBadge>
            </td>
            <td>
              <span :class="movement.quantity > 0 ? 'text-success quantity-change' : 'text-danger quantity-change'">
                <CIcon :icon="movement.quantity > 0 ? 'cil-arrow-top' : 'cil-arrow-bottom'" class="me-1" />
                {{ movement.quantity > 0 ? '+' : '' }}{{ movement.quantity }}
              </span>
            </td>
            <td>
              <span class="quantity-before">{{ movement.before_quantity }}</span>
            </td>
            <td>
              <strong class="quantity-after">{{ movement.after_quantity }}</strong>
            </td>
            <td>
              <span class="cost-cell">
                <CIcon icon="cil-dollar" class="me-1" />
                {{ formatCurrency(movement.total_cost || 0) }}
              </span>
            </td>
          </tr>
        </tbody>
      </CTable>

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

const { t } = useTranslation();

const movements = ref([]);
const lowStockItems = ref([]);
const loading = ref(false);
const showAdjustModal = ref(false);

const filters = ref({
  type: '',
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
  const totalMovements = movements.value.length;
  const lowStock = lowStockItems.value.length;
  const totalProducts = new Set(movements.value.map(m => m.product_name)).size;
  const totalValue = movements.value.reduce((sum, m) => sum + (parseFloat(m.total_cost) || 0), 0);
  
  return {
    totalMovements,
    lowStock,
    totalProducts,
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

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getTypeColor = (type) => {
  const colors = {
    purchase: 'success',
    sale: 'danger',
    adjustment: 'warning',
    return: 'info',
  };
  return colors[type] || 'secondary';
};

const getTypeText = (type) => {
  const texts = {
    purchase: 'Purchase',
    sale: 'Sale',
    adjustment: 'Adjustment',
    return: 'Return',
  };
  return texts[type] || type;
};

const getTypeIcon = (type) => {
  const icons = {
    purchase: 'cil-plus',
    sale: 'cil-minus',
    adjustment: 'cil-reload',
    return: 'cil-reload',
  };
  return icons[type] || 'cil-info';
};

const loadMovements = async () => {
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

    const response = await api.get('/inventory/movements', { params });
    const data = response.data?.data || response.data || {};
    
    movements.value = data.items || [];
    pagination.value = data.pagination || pagination.value;
  } catch (error) {
    console.error('Error loading movements:', error);
    movements.value = [];
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

const loadLowStock = async () => {
  try {
    const response = await api.get('/inventory/low-stock');
    lowStockItems.value = response.data?.data || [];
  } catch (error) {
    console.error('Error loading low stock:', error);
    lowStockItems.value = [];
  }
};

const changePage = (page) => {
  pagination.value.current_page = page;
  loadMovements();
};

const resetFilters = () => {
  filters.value = { type: '', date_from: '', date_to: '' };
  pagination.value.current_page = 1;
  loadMovements();
};

const exportData = () => {
  console.log('Exporting inventory data...');
  alert(t('common.export') + ' - ' + t('inventory.title'));
};

onMounted(() => {
  loadMovements();
  loadLowStock();
});
</script>

<style scoped>
.inventory-page {
  font-family: var(--font-family-body);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: var(--spacing-lg);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.table-modern {
  margin: 0;
  color: var(--text-primary);
}

.table-modern thead th {
  border-bottom: 2px solid var(--asmaa-primary-soft-border);
  font-weight: 800;
  color: var(--text-primary);
  background: var(--bg-secondary);
  padding: 1rem;
}

.table-row {
  transition: all 0.3s;
}

.table-row:hover {
  background-color: var(--asmaa-primary-soft);
}

.table-row td {
  padding: 1rem;
  vertical-align: middle;
  border-bottom: 1px solid var(--border-color);
  color: var(--text-primary);
}

.movement-product-cell strong {
  display: block;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
  font-weight: 700;
}

.movement-product-cell small {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.type-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.875rem;
  padding: 0.375rem 0.625rem;
  font-weight: 700;
}

.quantity-change {
  display: inline-flex;
  align-items: center;
  font-weight: 700;
  font-size: 0.9375rem;
}

.quantity-before,
.quantity-after {
  font-size: 0.9375rem;
  color: var(--text-primary);
}

.cost-cell {
  display: inline-flex;
  align-items: center;
  color: var(--text-muted);
  font-size: 0.875rem;
  font-weight: 600;
}

:deep(.card) {
  border-radius: 20px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  box-shadow: var(--shadow-sm);
}

:deep(.card-header) {
  background: var(--bg-tertiary);
  border-bottom: 1px solid var(--border-color);
  font-weight: 800;
  padding: 1.25rem;
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>

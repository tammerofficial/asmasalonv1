<template>
  <div class="quick-stats-bar">
    <div v-for="stat in stats" :key="stat.label" class="stat-item">
      <div class="stat-icon" :class="stat.color">
        <CIcon :icon="stat.icon" />
      </div>
      <div class="stat-content">
        <div class="stat-value">{{ stat.value }}</div>
        <div class="stat-label">{{ stat.label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { CIcon } from '@coreui/icons-vue';

const { t } = useTranslation();

const props = defineProps({
  session: {
    type: Object,
    default: () => ({})
  },
  ordersCount: {
    type: Number,
    default: 0
  },
  activeCustomersCount: {
    type: Number,
    default: 0
  }
});

const stats = computed(() => [
  {
    label: t('pos.todaySales') || 'مبيعات اليوم',
    value: formatCurrency(props.session?.total_sales || 0),
    icon: 'cil-money',
    color: 'success'
  },
  {
    label: t('pos.totalOrders') || 'عدد الطلبات',
    value: props.session?.total_transactions || 0,
    icon: 'cil-cart',
    color: 'primary'
  },
  {
    label: t('pos.activeCustomers') || 'العميلات النشطات',
    value: props.activeCustomersCount,
    icon: 'cil-people',
    color: 'info'
  },
  {
    label: t('pos.openingCash') || 'رصيد الافتتاح',
    value: formatCurrency(props.session?.opening_cash || 0),
    icon: 'cil-bank',
    color: 'warning'
  }
]);

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
}
</script>

<style scoped>
.quick-stats-bar {
  display: flex;
  gap: 1.5rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 16px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
  overflow-x: auto;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 1rem;
  border-right: 1px solid var(--border-color);
  min-width: fit-content;
}

.stat-item:last-child {
  border-right: none;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.stat-icon.success { background: rgba(46, 184, 92, 0.1); color: #2eb85c; }
.stat-icon.primary { background: var(--asmaa-primary-soft); color: var(--asmaa-primary); }
.stat-icon.info { background: rgba(51, 153, 255, 0.1); color: #3399ff; }
.stat-icon.warning { background: rgba(249, 177, 21, 0.1); color: #f9b115; }

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-primary);
}

.stat-label {
  font-size: 0.8125rem;
  color: var(--text-muted);
}

@media (max-width: 768px) {
  .quick-stats-bar {
    gap: 1rem;
    padding: 0.75rem;
  }
  .stat-item {
    padding: 0.25rem 0.5rem;
  }
}
</style>


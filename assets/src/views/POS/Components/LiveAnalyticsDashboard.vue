<template>
  <div class="live-analytics-dashboard p-3">
    <CRow>
      <CCol md="6" class="mb-4">
        <div class="analytics-card border rounded p-3 h-100">
          <div class="fw-bold mb-3">{{ t('analytics.salesByHour') || 'المبيعات حسب الساعة' }}</div>
          <div class="chart-placeholder bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px">
            <CIcon icon="cil-chart" size="xl" class="text-muted" />
          </div>
        </div>
      </CCol>
      <CCol md="6" class="mb-4">
        <div class="analytics-card border rounded p-3 h-100">
          <div class="fw-bold mb-3">{{ t('analytics.topCategories') || 'الأقسام الأكثر مبيعاً' }}</div>
          <div class="chart-placeholder bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px">
            <CIcon icon="cil-chart-pie" size="xl" class="text-muted" />
          </div>
        </div>
      </CCol>
    </CRow>
    <CRow>
      <CCol md="12">
        <div class="analytics-card border rounded p-3">
          <div class="fw-bold mb-3">{{ t('analytics.staffPerformance') || 'أداء الموظفات اليوم' }}</div>
          <div class="staff-performance-list">
            <div v-for="s in staffPerformance" :key="s.name" class="staff-perf-item d-flex align-items-center justify-content-between mb-2 p-2 bg-tertiary rounded">
              <span class="fw-bold">{{ s.name }}</span>
              <div class="d-flex gap-3">
                <span class="text-primary">{{ s.orders }} {{ t('pos.orders') }}</span>
                <span class="text-success fw-bold">{{ formatCurrency(s.revenue) }}</span>
              </div>
            </div>
          </div>
        </div>
      </CCol>
    </CRow>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { CRow, CCol } from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';

const { t } = useTranslation();

const props = defineProps({
  session: Object
});

const staffPerformance = computed(() => {
  // Mock data or calculate from session if available
  return [
    { name: 'ليلى أحمد', orders: 12, revenue: 150.500 },
    { name: 'سارة محمد', orders: 8, revenue: 95.000 },
    { name: 'نورة علي', orders: 5, revenue: 45.750 }
  ];
});

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
}
</script>

<style scoped>
.analytics-card {
  background: var(--bg-secondary);
  transition: all 0.3s;
}
.analytics-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}
.staff-perf-item {
  border-right: 4px solid var(--asmaa-primary);
}
</style>


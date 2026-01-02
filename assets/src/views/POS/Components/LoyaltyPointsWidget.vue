<template>
  <div class="loyalty-widget" v-if="points > 0">
    <div class="widget-header">
      <div class="points-badge">
        <CIcon icon="cil-star" />
        <span>{{ points }} {{ t('loyalty.points') }}</span>
      </div>
      <div class="points-value">
        {{ formatCurrency(pointsValue) }}
      </div>
    </div>
    
    <div class="redeem-actions mt-3">
      <CButton 
        size="sm" 
        color="warning" 
        class="w-100" 
        @click="$emit('redeem', points)"
        :disabled="points < 10"
      >
        <CIcon icon="cil-gift" class="me-2" />
        {{ t('loyalty.redeem') }}
      </CButton>
      <small class="text-muted d-block mt-1 text-center" v-if="points < 10">
        {{ t('loyalty.minPointsToRedeem') || 'الحد الأدنى للاستبدال 10 نقاط' }}
      </small>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { CIcon } from '@coreui/icons-vue';
import { CButton } from '@coreui/vue';

const { t } = useTranslation();

const props = defineProps({
  points: {
    type: Number,
    default: 0
  }
});

// Assuming 1 point = 0.1 KWD for visual feedback, adjust based on real settings
const pointsValue = computed(() => props.points * 0.1);

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
}
</script>

<style scoped>
.loyalty-widget {
  background: var(--asmaa-warning-soft);
  border: 1px solid var(--asmaa-warning-soft-border);
  border-radius: 12px;
  padding: 1rem;
  margin-top: 1rem;
}

.widget-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.points-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  color: var(--asmaa-warning);
}

.points-value {
  font-weight: 800;
  color: var(--asmaa-primary);
}
</style>


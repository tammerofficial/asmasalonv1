<template>
  <div class="payment-methods-grid">
    <div 
      v-for="method in methods" 
      :key="method.id"
      class="payment-method-card"
      :class="{ 'active': modelValue === method.id }"
      @click="$emit('update:modelValue', method.id)"
    >
      <div class="method-icon">
        <CIcon :icon="method.icon" />
      </div>
      <div class="method-name">{{ method.label }}</div>
      <div class="active-check" v-if="modelValue === method.id">
        <CIcon icon="cil-check-circle" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { CIcon } from '@coreui/icons-vue';

const { t } = useTranslation();

defineProps({
  modelValue: {
    type: String,
    default: 'cash'
  }
});

defineEmits(['update:modelValue']);

const methods = computed(() => [
  { id: 'cash', label: t('pos.cash') || 'نقدي', icon: 'cil-money' },
  { id: 'card', label: t('pos.card') || 'بطاقة', icon: 'cil-credit-card' },
  { id: 'knet', label: 'KNET', icon: 'cil-bank' },
  { id: 'wallet', label: t('pos.wallet') || 'المحفظة', icon: 'cil-wallet' },
  { id: 'loyalty', label: t('loyalty.points') || 'النقاط', icon: 'cil-star' }
]);
</script>

<style scoped>
.payment-methods-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.payment-method-card {
  background: var(--bg-secondary);
  border: 2px solid var(--border-color);
  border-radius: 12px;
  padding: 1rem 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.payment-method-card:hover {
  border-color: var(--asmaa-primary);
  transform: translateY(-2px);
}

.payment-method-card.active {
  border-color: var(--asmaa-primary);
  background: rgba(187, 160, 122, 0.1);
}

.method-icon {
  font-size: 1.5rem;
  color: var(--text-secondary);
}

.active .method-icon {
  color: var(--asmaa-primary);
}

.method-name {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--text-primary);
}

.active-check {
  position: absolute;
  top: -8px;
  right: -8px;
  background: var(--asmaa-primary);
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
  .payment-methods-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>


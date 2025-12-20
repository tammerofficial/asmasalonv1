<template>
  <div class="stats-card" :class="`stats-card-${color}`">
    <div class="stats-icon">
      <CIcon :icon="icon" />
    </div>
    <div class="stats-content">
      <div class="stats-value">{{ value }}</div>
      <div class="stats-title">{{ title }}</div>
    </div>
  </div>
</template>

<script setup>
import { CIcon } from '@coreui/icons-vue';

defineProps({
  title: String,
  value: [String, Number],
  icon: String,
  color: {
    type: String,
    default: 'primary',
  },
});
</script>

<style scoped>
.stats-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border-radius: 12px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.stats-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stats-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.stats-card-primary .stats-icon {
  background: rgba(var(--cui-primary-rgb), 0.1);
  color: var(--asmaa-primary);
}

.stats-card-success .stats-icon {
  background: rgba(var(--cui-success-rgb), 0.1);
  color: var(--cui-success);
}

.stats-card-info .stats-icon {
  background: rgba(var(--cui-info-rgb), 0.1);
  color: var(--cui-info);
}

.stats-card-warning .stats-icon {
  background: rgba(var(--cui-warning-rgb), 0.1);
  color: var(--cui-warning);
}

.stats-card-danger .stats-icon {
  background: rgba(var(--cui-danger-rgb), 0.1);
  color: var(--cui-danger);
}

.stats-content {
  flex: 1;
}

.stats-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1;
  margin-bottom: 4px;
}

.stats-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
</style>


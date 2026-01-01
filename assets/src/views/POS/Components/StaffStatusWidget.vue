<template>
  <div class="staff-status-widget">
    <div class="section-title">
      <CIcon icon="cil-user" class="me-2" />
      {{ t('workerCalls.staffStatus') || 'حالة الموظفات' }}
    </div>
    <div class="staff-grid">
      <div 
        v-for="s in staff" 
        :key="s.id" 
        class="staff-status-card"
        :class="s.status"
        @click="$emit('call', s)"
      >
        <div class="staff-avatar">
          {{ s.name?.charAt(0) }}
          <div class="status-indicator"></div>
        </div>
        <div class="staff-name">{{ s.name }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useTranslation } from '@/composables/useTranslation';
import { CIcon } from '@coreui/icons-vue';

const { t } = useTranslation();

defineProps({
  staff: {
    type: Array,
    default: () => []
  }
});
</script>

<style scoped>
.staff-status-widget {
  margin-top: 1.5rem;
}

.section-title {
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 0.75rem;
  letter-spacing: 0.5px;
}

.staff-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
  gap: 0.75rem;
}

.staff-status-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  cursor: pointer;
}

.staff-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--bg-tertiary);
  border: 2px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  position: relative;
  transition: all 0.2s;
}

.staff-status-card:hover .staff-avatar {
  border-color: var(--asmaa-primary);
  transform: scale(1.1);
}

.status-indicator {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid var(--bg-secondary);
  background: #ccc;
}

.staff-status-card.available .status-indicator { background: #2eb85c; }
.staff-status-card.busy .status-indicator { background: #e55353; }
.staff-status-card.away .status-indicator { background: #f9b115; }

.staff-name {
  font-size: 0.625rem;
  font-weight: 600;
  color: var(--text-primary);
  text-align: center;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>


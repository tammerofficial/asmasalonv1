<template>
  <div class="booking-card" :class="status">
    <div class="booking-time">{{ formatTime(booking.booking_time) }}</div>
    <div class="booking-details">
      <div class="customer-name">{{ booking.customer_name }}</div>
      <div class="service-name">{{ booking.service_name }}</div>
      <div class="staff-info" v-if="booking.staff_name">
        <CIcon icon="cil-user" class="me-1" />
        {{ booking.staff_name }}
      </div>
    </div>
    <div class="booking-status">
      <CBadge :color="statusColor">{{ booking.status }}</CBadge>
    </div>
    <div class="booking-actions">
      <CButton size="sm" color="success" @click="$emit('arrive', booking)" v-if="status === 'confirmed' || status === 'pending'">
        <CIcon icon="cil-walk" class="me-1" />
        {{ t('pos.arrived') || 'وصلت' }}
      </CButton>
      <CButton size="sm" color="primary" @click="$emit('process', booking)">
        <CIcon icon="cil-plus" />
      </CButton>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { CBadge, CButton } from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();

const props = defineProps({
  booking: {
    type: Object,
    required: true
  }
});

const status = computed(() => props.booking.status?.toLowerCase() || 'pending');

const statusColor = computed(() => {
  switch (status.value) {
    case 'confirmed': return 'success';
    case 'pending': return 'warning';
    case 'cancelled': return 'danger';
    default: return 'info';
  }
});

function formatTime(time) {
  if (!time) return '';
  return time.slice(0, 5);
}
</script>

<style scoped>
.booking-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  margin-bottom: 0.75rem;
  transition: all 0.2s;
}

.booking-card:hover {
  border-color: var(--asmaa-primary);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.booking-time {
  font-weight: 700;
  color: var(--asmaa-primary);
  font-size: 1.125rem;
  min-width: 60px;
}

.booking-details {
  flex: 1;
  min-width: 0;
}

.customer-name {
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.service-name {
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.staff-info {
  font-size: 0.75rem;
  color: var(--asmaa-primary);
  margin-top: 0.25rem;
}

.booking-actions {
  display: flex;
  gap: 0.5rem;
}
</style>


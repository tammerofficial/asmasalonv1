<template>
  <div class="queue-card" :class="ticket.status">
    <div class="ticket-number">#{{ ticket.ticket_number }}</div>
    <div class="ticket-details">
      <div class="customer-name">{{ ticket.customer_name || t('pos.walkInCustomer') }}</div>
      <div class="waiting-time">
        <CIcon icon="cil-clock" class="me-1" />
        {{ formatTime(ticket.created_at) }}
      </div>
    </div>
    <div class="ticket-actions">
      <CButton 
        v-if="ticket.status === 'waiting'" 
        size="sm" 
        color="warning" 
        @click="$emit('call', ticket)"
      >
        {{ t('queue.call') || 'استدعاء' }}
      </CButton>
      <CButton 
        v-if="ticket.status === 'called'" 
        size="sm" 
        color="success" 
        @click="$emit('serve', ticket)"
      >
        {{ t('queue.serve') || 'بدء الخدمة' }}
      </CButton>
    </div>
  </div>
</template>

<script setup>
import { CButton } from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';

const { t } = useTranslation();

defineProps({
  ticket: {
    type: Object,
    required: true
  }
});

function formatTime(dateTime) {
  if (!dateTime) return '';
  const date = new Date(dateTime);
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}
</script>

<style scoped>
.queue-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  margin-bottom: 0.75rem;
}

.ticket-number {
  font-weight: 800;
  font-size: 1.25rem;
  color: var(--asmaa-primary);
  background: rgba(187, 160, 122, 0.1);
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ticket-details {
  flex: 1;
}

.customer-name {
  font-weight: 600;
  color: var(--text-primary);
}

.waiting-time {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
}

.ticket-actions {
  display: flex;
}
</style>


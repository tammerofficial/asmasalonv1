<template>
  <div class="empty-state">
    <div class="empty-icon-wrapper" :class="`icon-${iconColor}`">
      <slot name="icon">
        <CIcon :icon="icon || 'cil-inbox'" size="3xl" class="empty-icon" />
      </slot>
    </div>
    <h5 class="empty-title">{{ title }}</h5>
    <p class="empty-description" v-if="description">{{ description }}</p>
    <div class="empty-action" v-if="$slots.action">
      <slot name="action"></slot>
    </div>
  </div>
</template>

<script setup>
import { CIcon } from '@coreui/icons-vue';

defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: ''
  },
  iconColor: {
    type: String,
    default: 'gray'
  }
});
</script>

<style scoped>
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon-wrapper {
  margin-bottom: 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.empty-icon {
  color: var(--text-muted);
}

.icon-gray .empty-icon {
  color: var(--text-muted);
}

.icon-blue .empty-icon {
  color: #3b82f6;
}

.icon-green .empty-icon {
  color: #10b981;
}

.icon-yellow .empty-icon {
  color: #f59e0b;
}

.icon-red .empty-icon {
  color: #ef4444;
}

.empty-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem;
}

.empty-description {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0 0 1.5rem;
}

.empty-action {
  margin-top: 1rem;
}
</style>

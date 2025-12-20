<template>
  <div class="card-modern">
    <div class="card-header-modern" v-if="title || $slots.actions">
      <div class="card-header-content">
        <div v-if="icon" class="card-header-icon">
          <CIcon :icon="icon" />
        </div>
        <div class="card-header-text">
          <h3 class="card-title" v-if="title">{{ title }}</h3>
          <p class="card-subtitle" v-if="subtitle">{{ subtitle }}</p>
        </div>
      </div>
      <div class="card-header-actions" v-if="$slots.actions">
        <slot name="actions"></slot>
      </div>
    </div>
    <div class="card-body-modern">
      <slot></slot>
    </div>
    <div class="card-footer-modern" v-if="$slots.footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
import { CIcon } from '@coreui/icons-vue';

defineProps({
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: ''
  }
});
</script>

<style scoped>
.card-modern {
  border-radius: 16px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: all 0.3s;
  background: var(--bg-primary);
}

.card-modern:hover {
  box-shadow: var(--shadow-md);
}

.card-header-modern {
  padding: 1.5rem;
  border-bottom: 1px solid var(--border-color);
  background: linear-gradient(to right, var(--bg-secondary), var(--bg-primary));
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.card-header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.card-header-icon {
  font-size: 1.5rem;
  color: var(--asmaa-primary);
  flex-shrink: 0;
}

.card-header-text {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.card-subtitle {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0;
}

.card-header-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.card-body-modern {
  padding: 1.5rem;
}

.card-footer-modern {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color);
  background: var(--bg-secondary);
}

/* RTL Support */
[dir="rtl"] .card-header-modern {
  flex-direction: row-reverse;
}

[dir="rtl"] .card-header-content {
  flex-direction: row-reverse;
}

[dir="rtl"] .card-header-modern {
  background: linear-gradient(to left, var(--bg-secondary), var(--bg-primary));
}
</style>

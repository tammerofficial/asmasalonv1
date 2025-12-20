<template>
  <div class="page-header">
    <div class="page-header-content">
      <div class="page-header-icon" v-if="$slots.icon">
        <slot name="icon"></slot>
      </div>
      <div class="page-header-text">
        <h1 class="page-header-title">{{ title }}</h1>
        <p class="page-header-subtitle" v-if="subtitle">{{ subtitle }}</p>
      </div>
    </div>
    <div class="page-header-actions" v-if="$slots.actions">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  }
});
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border-color);
}

.page-header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.page-header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 8px 16px rgba(187, 160, 122, 0.35);
  flex-shrink: 0;
}

.page-header-icon :deep(svg) {
  width: 24px;
  height: 24px;
}

.page-header-text {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.page-header-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  line-height: 1.2;
}

.page-header-subtitle {
  font-size: 0.95rem;
  color: var(--text-secondary);
  margin: 0;
}

.page-header-actions {
  display: flex;
  gap: 0.75rem;
  flex-shrink: 0;
}

/* RTL Support */
[dir="rtl"] .page-header-content {
  flex-direction: row-reverse;
}

[dir="rtl"] .page-header {
  flex-direction: row-reverse;
}

/* Responsive */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .page-header-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>

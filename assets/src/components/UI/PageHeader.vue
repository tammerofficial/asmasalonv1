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
  gap: var(--spacing-2xl);
  margin-bottom: var(--spacing-2xl);
  padding-bottom: var(--spacing-lg);
  border-bottom: 1px solid var(--border-color);
  font-family: var(--font-family-base);
}

.page-header-content {
  display: flex;
  align-items: center;
  gap: var(--spacing-base);
  flex: 1;
}

.page-header-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-xl);
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, var(--asmaa-primary-dark) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: var(--shadow-md);
  flex-shrink: 0;
}

.page-header-icon :deep(svg) {
  width: 24px;
  height: 24px;
}

.page-header-text {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.page-header-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family-base);
  color: var(--text-primary);
  margin: 0;
  line-height: var(--line-height-tight);
}

.page-header-subtitle {
  font-size: var(--font-size-base);
  font-family: var(--font-family-base);
  color: var(--text-secondary);
  margin: 0;
  line-height: var(--line-height-normal);
}

.page-header-actions {
  display: flex;
  gap: var(--spacing-md);
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

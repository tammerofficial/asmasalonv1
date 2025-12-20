<template>
  <div class="loading-spinner">
    <CSpinner :color="color" :size="size" />
    <p class="loading-text" v-if="text">{{ text }}</p>
  </div>
</template>

<script setup>
import { CSpinner } from '@coreui/vue';

defineProps({
  text: {
    type: String,
    default: ''
  },
  color: {
    type: String,
    default: 'primary'
  },
  size: {
    type: String,
    default: ''
  }
});
</script>

<style scoped>
.loading-spinner {
  text-align: center;
  padding: 3rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.loading-text {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0;
}
</style>

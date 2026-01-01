<template>
  <div v-if="content" class="help-section mt-4">
    <Card :title="content.title" :subtitle="content.description" icon="cil-info">
      <div class="faq-list">
        <div v-for="(faq, index) in content.faqs" :key="index" class="faq-item">
          <div class="faq-question" @click="toggleFaq(index)">
            <CIcon :icon="faq.icon || 'cil-help'" class="me-2 faq-icon" />
            <span class="question-text">{{ faq.question }}</span>
            <CIcon 
              :icon="expandedFaqs.includes(index) ? 'cil-chevron-top' : 'cil-chevron-bottom'" 
              class="ms-auto toggle-icon" 
            />
          </div>
          <div v-if="expandedFaqs.includes(index)" class="faq-answer">
            {{ faq.answer }}
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Card from '@/components/UI/Card.vue';
import { CIcon } from '@coreui/icons-vue';
import { getHelpContent } from '@/utils/helpContent';

const props = defineProps({
  pageKey: {
    type: String,
    required: true
  }
});

const content = computed(() => getHelpContent(props.pageKey));
const expandedFaqs = ref([]);

const toggleFaq = (index) => {
  const pos = expandedFaqs.value.indexOf(index);
  if (pos === -1) {
    expandedFaqs.value.push(index);
  } else {
    expandedFaqs.value.splice(pos, 1);
  }
};
</script>

<style scoped>
.help-section {
  margin-bottom: var(--spacing-xl);
}

.faq-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.faq-item {
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--bg-primary);
  transition: all var(--transition-base);
}

.faq-item:hover {
  border-color: var(--asmaa-primary);
  box-shadow: var(--shadow-sm);
}

.faq-question {
  padding: var(--spacing-md);
  display: flex;
  align-items: center;
  cursor: pointer;
  font-weight: 600;
  color: var(--text-primary);
  user-select: none;
}

.faq-icon {
  color: var(--asmaa-primary);
  font-size: 1.2rem;
}

.toggle-icon {
  color: var(--text-muted);
  font-size: 0.8rem;
  transition: transform var(--transition-base);
}

.faq-answer {
  padding: 0 var(--spacing-md) var(--spacing-md) calc(var(--spacing-md) + 1.5rem);
  color: var(--text-secondary);
  line-height: var(--line-height-relaxed);
  font-size: 0.9375rem;
  animation: fadeIn var(--transition-base);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>


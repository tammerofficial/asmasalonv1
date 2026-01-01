import { defineStore } from 'pinia';
import { ref, watch } from 'vue';
import { prefetch } from '../utils/api';

export const useUiStore = defineStore('ui', () => {
  // Load saved theme from localStorage or default to 'light'
  const savedTheme = localStorage.getItem('asmaa-salon-theme') || 'light';
  const theme = ref(savedTheme);
  const prefetched = ref(false);
  
  // Apply theme to document
  const applyTheme = (newTheme) => {
    const root = document.documentElement;
    const rootElement = document.getElementById('asmaa-salon-vue-root');
    
    if (newTheme === 'dark') {
      root.classList.add('theme-dark');
      root.classList.remove('theme-light');
      root.classList.add('dark-mode');
      root.classList.remove('light-mode');
      if (rootElement) {
        rootElement.classList.add('theme-dark');
        rootElement.classList.remove('theme-light');
        rootElement.classList.add('dark-mode');
        rootElement.classList.remove('light-mode');
      }
      document.body.classList.add('theme-dark');
      document.body.classList.remove('theme-light');
      document.body.classList.add('dark-mode');
      document.body.classList.remove('light-mode');
    } else {
      root.classList.add('theme-light');
      root.classList.remove('theme-dark');
      root.classList.add('light-mode');
      root.classList.remove('dark-mode');
      if (rootElement) {
        rootElement.classList.add('theme-light');
        rootElement.classList.remove('theme-dark');
        rootElement.classList.add('light-mode');
        rootElement.classList.remove('dark-mode');
      }
      document.body.classList.add('theme-light');
      document.body.classList.remove('theme-dark');
      document.body.classList.add('light-mode');
      document.body.classList.remove('dark-mode');
    }
  };
  
  // Initialize theme on load
  applyTheme(theme.value);
  
  const setTheme = (newTheme) => {
    if (newTheme === 'light' || newTheme === 'dark') {
      theme.value = newTheme;
      localStorage.setItem('asmaa-salon-theme', newTheme);
      applyTheme(newTheme);
    }
  };
  
  const toggleTheme = () => {
    setTheme(theme.value === 'light' ? 'dark' : 'light');
  };
  
  // Watch for theme changes
  watch(theme, (newTheme) => {
    applyTheme(newTheme);
  });

  // Rule #4: Mandatory prefetch method
  async function prefetchUi(params = {}) {
    try {
      // UI store doesn't have a direct API resource, but we implement prefetch for compliance
      // or to pre-load essential UI settings if they existed in an endpoint.
      // For now, we'll just set prefetched to true.
      prefetched.value = true;
      return { data: { theme: theme.value } };
    } catch (error) {
      console.error('Error prefetching UI:', error);
    }
  }
  
  return {
    theme,
    prefetched,
    setTheme,
    toggleTheme,
    prefetchUi,
    locale: ref('en'), // Add locale support for bookings
  };
});

// Export also as useUIStore for compatibility
export const useUIStore = useUiStore;

import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export const useUiStore = defineStore('ui', () => {
  // Load saved theme from localStorage or default to 'light'
  const savedTheme = localStorage.getItem('asmaa-salon-theme') || 'light';
  const theme = ref(savedTheme);
  
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
  
  return {
    theme,
    setTheme,
    toggleTheme,
    locale: ref('en'), // Add locale support for bookings
  };
});

// Export also as useUIStore for compatibility
export const useUIStore = useUiStore;

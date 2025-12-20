import { ref, computed, watch } from 'vue';
import en from '../locales/en.json';
import ar from '../locales/ar.json';

// Load saved locale from localStorage or default to 'en'
const savedLocale = localStorage.getItem('asmaa-salon-locale') || 'en';
const currentLocale = ref(savedLocale);
const messages = { en, ar };

// Set direction based on locale
const setDirection = (locale) => {
  const root = document.documentElement;
  const rootElement = document.getElementById('asmaa-salon-vue-root');
  
  if (locale === 'ar') {
    root.setAttribute('dir', 'rtl');
    root.setAttribute('lang', 'ar');
    if (rootElement) {
      rootElement.setAttribute('dir', 'rtl');
      rootElement.setAttribute('lang', 'ar');
      rootElement.classList.remove('lang-en');
      rootElement.classList.add('lang-ar');
    }
  } else {
    root.setAttribute('dir', 'ltr');
    root.setAttribute('lang', 'en');
    if (rootElement) {
      rootElement.setAttribute('dir', 'ltr');
      rootElement.setAttribute('lang', 'en');
      rootElement.classList.remove('lang-ar');
      rootElement.classList.add('lang-en');
    }
  }
};

// Initialize direction on load
setDirection(currentLocale.value);

export function useTranslation() {
  const t = (key) => {
    const keys = key.split('.');
    let value = messages[currentLocale.value];
    
    for (const k of keys) {
      value = value?.[k];
      if (value === undefined) {
        console.warn(`Translation key not found: ${key}`);
        return key;
      }
    }
    
    return value || key;
  };
  
  const setLocale = (locale) => {
    if (locale === 'ar' || locale === 'en') {
      currentLocale.value = locale;
      localStorage.setItem('asmaa-salon-locale', locale);
      setDirection(locale);
    }
  };
  
  const dir = computed(() => currentLocale.value === 'ar' ? 'rtl' : 'ltr');
  
  // Watch for locale changes and update direction
  watch(currentLocale, (newLocale) => {
    setDirection(newLocale);
  });
  
  return { 
    t, 
    currentLocale, 
    setLocale,
    dir
  };
}

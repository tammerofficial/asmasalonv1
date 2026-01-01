<template>
  <CHeader class="border-bottom topbar-modern">
    <CHeaderBrand>
      <CButton
        class="px-3 me-md-4"
        color="ghost"
        size="lg"
        @click="$emit('toggle-sidebar')"
      >
        <CIcon icon="cil-menu" size="lg" />
      </CButton>
    </CHeaderBrand>
    <CHeaderNav class="ms-auto me-4 topbar-actions">
      <!-- Language Switcher -->
      <CNavItem class="language-switcher">
        <div class="lang-buttons">
          <button
            class="lang-btn"
            :class="{ active: currentLocale === 'ar' }"
            @click="setLocale('ar')"
            :title="languageTitleAr"
          >
            <span>{{ t('common.ar') }}</span>
          </button>
          <button
            class="lang-btn"
            :class="{ active: currentLocale === 'en' }"
            @click="setLocale('en')"
            :title="languageTitleEn"
          >
            <span>{{ t('common.en') }}</span>
          </button>
        </div>
      </CNavItem>
      
      <!-- Theme Toggle -->
      <CNavItem class="theme-toggle">
        <CButton
          color="ghost"
          size="lg"
          @click="toggleTheme"
          :title="theme === 'light' ? t('common.darkMode') : t('common.lightMode')"
          class="theme-btn"
        >
          <CIcon 
            :icon="theme === 'light' ? 'cil-moon' : 'cil-sun'" 
            size="lg" 
          />
        </CButton>
      </CNavItem>
      
      <!-- Notifications -->
      <CNavItem>
        <NotificationsBell />
      </CNavItem>
      
      <!-- User Profile -->
      <CNavItem>
        <CNavLink>
          <CIcon icon="cil-user" size="lg" />
        </CNavLink>
      </CNavItem>
    </CHeaderNav>
  </CHeader>
</template>

<script setup>
import { computed } from 'vue';
import {
  CHeader,
  CHeaderBrand,
  CHeaderNav,
  CNavItem,
  CButton,
} from '@coreui/vue';
import { useTranslation } from '@/composables/useTranslation';
import { useUiStore } from '@/stores/ui';
import NotificationsBell from '@/components/Notifications/NotificationsBell.vue';

const { currentLocale, setLocale, t } = useTranslation();
const uiStore = useUiStore();

const theme = computed(() => uiStore.theme);
const languageTitleAr = computed(() => `${t('common.language')}: العربية`);
const languageTitleEn = computed(() => `${t('common.language')}: English`);

const toggleTheme = () => {
  uiStore.toggleTheme();
};
</script>

<style scoped>
.topbar-modern {
  background: var(--bg-primary);
  border-bottom: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
  padding: var(--spacing-sm) var(--spacing-base);
  font-family: var(--font-family-base);
}

.topbar-modern :deep(.nav-link) {
  color: var(--text-secondary);
  transition: all 0.2s;
}
.topbar-modern :deep(.nav-link:hover) {
  color: var(--asmaa-primary);
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.language-switcher {
  margin: 0 var(--spacing-sm);
}

.lang-buttons {
  display: flex;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  padding: 2px;
  gap: 2px;
}

.lang-btn {
  border: none;
  background: transparent;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  font-family: var(--font-family-base);
  cursor: pointer;
  transition: all 0.2s;
  color: var(--text-secondary);
  min-width: 44px;
  line-height: var(--line-height-normal);
}

.lang-btn:hover {
  background: var(--asmaa-primary-soft);
  color: var(--text-primary);
}

.lang-btn.active {
  background: var(--bg-primary);
  color: var(--text-primary);
  box-shadow: var(--shadow-sm);
}

.theme-toggle {
  margin: 0 var(--spacing-xs);
}

.theme-btn {
  color: var(--text-secondary);
  transition: all 0.2s;
}

.theme-btn:hover {
  color: var(--text-primary);
  background: var(--asmaa-primary-soft);
}

/* RTL Support */
[dir="rtl"] .topbar-actions {
  flex-direction: row-reverse;
}

[dir="rtl"] .lang-buttons {
  flex-direction: row-reverse;
}
</style>

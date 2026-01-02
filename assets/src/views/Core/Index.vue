<template>
  <div class="asmaa-salon-core">
    <PageHeader :title="t('core.title')" :subtitle="t('core.subtitle')">
      <template #icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path
            d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z"
          />
          <path
            d="M19.4 15a1.7 1.7 0 0 0 .3 1.87l.04.04a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.04-.04a1.7 1.7 0 0 0-1.87-.3 1.7 1.7 0 0 0-1 1.54V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.06a1.7 1.7 0 0 0-1-1.54 1.7 1.7 0 0 0-1.87.3l-.04.04a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.04-.04A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.54-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.06A1.7 1.7 0 0 0 4.6 8a1.7 1.7 0 0 0-.3-1.87l-.04-.04a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.04.04A1.7 1.7 0 0 0 9 3.6 1.7 1.7 0 0 0 10.54 2.06V2a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.06A1.7 1.7 0 0 0 15 3.6a1.7 1.7 0 0 0 1.87-.3l.04-.04a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.04.04A1.7 1.7 0 0 0 19.4 8a1.7 1.7 0 0 0 1.54 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.06A1.7 1.7 0 0 0 19.4 15z"
          />
        </svg>
      </template>
    </PageHeader>

    <div class="core-grid">
      <Card :title="t('core.systemInfo')" :subtitle="t('core.systemInfoSubtitle')" icon="cil-info">
        <div class="info-list">
          <div class="info-row">
            <div class="info-left">
              <CIcon icon="cil-code" class="info-icon" />
              <span class="info-label">{{ t('core.pluginVersion') }}</span>
            </div>
            <span class="info-value">{{ config.version || '1.0.0' }}</span>
          </div>
          <div class="info-row">
            <div class="info-left">
              <CIcon icon="cil-link" class="info-icon" />
              <span class="info-label">{{ t('core.apiBase') }}</span>
            </div>
            <span class="info-value monospace">{{ config.restUrl || '-' }}</span>
          </div>
          <div class="info-row">
            <div class="info-left">
              <CIcon icon="cil-palette" class="info-icon" />
              <span class="info-label">{{ t('core.primaryColor') }}</span>
            </div>
            <span class="info-value">
              <span class="color-dot" :style="{ background: config.primaryColor || '#8E7E78' }" />
              <span class="monospace">{{ config.primaryColor || '#8E7E78' }}</span>
            </span>
          </div>
          <div class="info-row">
            <div class="info-left">
              <CIcon :icon="isDark ? 'cil-moon' : 'cil-sun'" class="info-icon" />
              <span class="info-label">{{ t('core.mode') }}</span>
            </div>
            <span class="info-value">
              <CBadge :color="isDark ? 'secondary' : 'warning'">
                {{ isDark ? t('common.darkMode') : t('common.lightMode') }}
              </CBadge>
            </span>
          </div>
          <div class="info-row">
            <div class="info-left">
              <CIcon icon="cil-language" class="info-icon" />
              <span class="info-label">{{ t('common.language') }}</span>
            </div>
            <span class="info-value">
              <CBadge color="info">{{ uiStore.locale?.toUpperCase?.() || 'EN' }}</CBadge>
            </span>
          </div>
        </div>
      </Card>

      <Card :title="t('core.preferences')" :subtitle="t('core.preferencesSubtitle')" icon="cil-settings">
        <div class="prefs">
          <div class="pref-row">
            <div class="pref-text">
              <div class="pref-title">{{ t('common.darkMode') }}</div>
              <div class="pref-subtitle">{{ t('core.darkModeHint') }}</div>
            </div>
            <button class="toggle" :class="{ on: isDark }" type="button" @click="uiStore.toggleTheme()">
              <span class="toggle-knob" />
            </button>
          </div>

          <div class="pref-row">
            <div class="pref-text">
              <div class="pref-title">{{ t('common.language') }}</div>
              <div class="pref-subtitle">{{ t('core.languageHint') }}</div>
            </div>
            <div class="lang-actions">
              <button class="chip" type="button" :class="{ active: uiStore.locale === 'en' }" @click="setLocale('en')">{{ t('common.en') }}</button>
              <button class="chip" type="button" :class="{ active: uiStore.locale === 'ar' }" @click="setLocale('ar')">{{ t('common.ar') }}</button>
            </div>
          </div>
        </div>
      </Card>

      <Card :title="t('core.shortcuts')" :subtitle="t('core.shortcutsSubtitle')" icon="cil-bolt">
        <div class="shortcuts">
          <button class="shortcut" type="button" @click="$router.push('/bookings/settings')">
            <span class="shortcut-title">{{ t('booking.settingsTitle') || 'Booking Settings' }}</span>
            <span class="shortcut-desc">{{ t('core.goToSettings') }}</span>
          </button>
          <button class="shortcut" type="button" @click="$router.push('/bookings/appearance')">
            <span class="shortcut-title">{{ t('booking.appearanceTitle') || 'Booking Appearance' }}</span>
            <span class="shortcut-desc">{{ t('core.customizeBookingForm') }}</span>
          </button>
          <button class="shortcut" type="button" @click="$router.push('/reports')">
            <span class="shortcut-title">{{ t('nav.reports') }}</span>
            <span class="shortcut-desc">{{ t('core.viewReports') }}</span>
          </button>
        </div>
      </Card>

      <Card :title="t('core.accessControl')" :subtitle="t('core.accessControlSubtitle')" icon="cil-shield-alt">
        <div class="access-control">
          <button class="access-button" type="button" @click="$router.push('/users')">
            <CIcon icon="cil-people" class="access-icon" />
            <div class="access-text">
              <span class="access-title">{{ t('users.title') }}</span>
              <span class="access-desc">{{ t('core.manageUsers') }}</span>
            </div>
          </button>
          
          <button class="access-button" type="button" @click="$router.push('/roles')">
            <CIcon icon="cil-shield-alt" class="access-icon" />
            <div class="access-text">
              <span class="access-title">{{ t('roles.title') }}</span>
              <span class="access-desc">{{ t('core.manageRoles') }}</span>
            </div>
          </button>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { CIcon } from '@coreui/icons-vue';
import { CBadge } from '@coreui/vue';
import PageHeader from '@/components/UI/PageHeader.vue';
import Card from '@/components/UI/Card.vue';
import { useUIStore } from '@/stores/ui';
import { useTranslation } from '@/composables/useTranslation';

const uiStore = useUIStore();
const { t, setLocale: setLocaleFromI18n } = useTranslation();

const isDark = computed(() => uiStore.theme === 'dark');

const config = computed(() => {
  // This comes from wp_localize_script in Plugin.php
  // eslint-disable-next-line no-undef
  return (typeof AsmaaSalonConfig !== 'undefined' && AsmaaSalonConfig) ? AsmaaSalonConfig : {};
});

const setLocale = (locale) => {
  uiStore.locale = locale;
  setLocaleFromI18n(locale);
};
</script>

<style scoped>
.asmaa-salon-core {
  width: 100%;
  padding: var(--spacing-lg);
  font-family: var(--font-family-body);
}

.core-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 1.5rem;
}

@media (max-width: 1200px) {
  .core-grid { grid-template-columns: 1fr; }
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  background: var(--bg-secondary);
  transition: all 0.3s;
}

.info-row:hover {
  background: var(--bg-tertiary);
  border-color: var(--asmaa-primary);
  transform: translateX(4px);
}

.info-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.info-icon {
  width: 18px;
  height: 18px;
  color: var(--asmaa-primary);
  flex-shrink: 0;
}

.info-label {
  color: var(--text-muted);
  font-weight: 600;
  font-size: 13px;
}

.info-value {
  color: var(--text-primary);
  font-weight: 700;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  text-align: right;
}

.monospace {
  font-family: var(--font-family-body), monospace;
  font-weight: 600;
}

.color-dot {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.08);
}

.prefs {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.pref-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px;
  border: 1px solid var(--border-color);
  border-radius: 14px;
  background: var(--bg-secondary);
}

.pref-title {
  font-weight: 800;
  color: var(--text-primary);
  font-size: 14px;
}

.pref-subtitle {
  margin-top: 2px;
  font-size: 12px;
  color: var(--text-muted);
}

.toggle {
  width: 52px;
  height: 30px;
  border-radius: 999px;
  border: 1px solid var(--border-color);
  background: var(--bg-tertiary);
  padding: 3px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.toggle.on {
  background: var(--asmaa-primary-soft);
  border-color: var(--asmaa-primary-soft-border);
}

.toggle-knob {
  width: 24px;
  height: 24px;
  border-radius: 999px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
  display: block;
  transform: translateX(0);
  transition: transform 0.2s ease, background-color 0.2s ease;
}

[dir="rtl"] .toggle-knob { transform: translateX(0); }
.toggle.on .toggle-knob { transform: translateX(22px); background: var(--asmaa-primary); border-color: var(--asmaa-primary-dark); }
[dir="rtl"] .toggle.on .toggle-knob { transform: translateX(-22px); }

.lang-actions {
  display: flex;
  gap: 8px;
}

.chip {
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
  color: var(--text-primary);
  border-radius: 999px;
  padding: 8px 12px;
  font-weight: 800;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.chip.active {
  border-color: var(--asmaa-primary);
  background: var(--asmaa-primary-soft);
  color: var(--asmaa-primary-dark);
}

.shortcuts {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

@media (max-width: 900px) {
  .shortcuts { grid-template-columns: 1fr; }
}

.shortcut {
  text-align: left;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  border-radius: 14px;
  padding: 14px;
  cursor: pointer;
  transition: transform 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
}

[dir="rtl"] .shortcut { text-align: right; }

.shortcut:hover {
  transform: translateY(-2px);
  border-color: var(--asmaa-primary);
  background: var(--bg-tertiary);
  box-shadow: var(--shadow-sm);
}

.shortcut-title {
  display: block;
  font-weight: 900;
  color: var(--text-primary);
  font-size: 14px;
}

.shortcut-desc {
  display: block;
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 600;
}

.access-control {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.access-button {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: left;
}

[dir="rtl"] .access-button { text-align: right; }

.access-button:hover {
  background: var(--bg-tertiary);
  border-color: var(--asmaa-primary);
  transform: translateX(4px);
  box-shadow: var(--shadow-sm);
}

[dir="rtl"] .access-button:hover { transform: translateX(-4px); }

.access-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--asmaa-primary-soft);
  color: var(--asmaa-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
  box-shadow: var(--shadow-sm);
}

.access-text {
  flex: 1;
}

.access-title {
  display: block;
  font-weight: 700;
  color: var(--text-primary);
  font-size: 15px;
}

.access-desc {
  display: block;
  margin-top: 2px;
  color: var(--text-muted);
  font-size: 12px;
}
</style>


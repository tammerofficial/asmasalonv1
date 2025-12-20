<template>
  <div class="roles-page">
    <PageHeader :title="t('roles.title')" :subtitle="t('roles.subtitle')">
      <template #icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
        </svg>
      </template>
      <template #actions>
        <CButton class="btn-refresh" color="secondary" variant="outline" @click="refresh">
          <CIcon icon="cil-reload" class="me-1" />
          {{ t('common.refresh') }}
        </CButton>
      </template>
    </PageHeader>

    <!-- Stats -->
    <div v-if="!loading" class="stats-grid">
      <StatsCard :title="t('roles.totalRoles')" :value="stats.totalRoles" icon="cil-shield-alt" color="primary" />
      <StatsCard :title="t('roles.totalAsmaaCaps')" :value="stats.totalAsmaaCaps" icon="cil-lock-locked" color="success" />
      <StatsCard :title="t('roles.totalWpCaps')" :value="stats.totalWpCaps" icon="cil-list" color="info" />
    </div>

    <!-- Filters -->
    <CCard v-if="!loading" class="mb-4">
      <CCardBody>
        <div class="filters-row">
          <div class="search-box">
            <CIcon icon="cil-search" class="search-icon" />
            <input
              v-model="filters.search"
              type="text"
              class="search-input"
              :placeholder="t('roles.searchPlaceholder')"
            />
          </div>
          <div class="filter-toggle">
            <label class="toggle-row">
              <input v-model="filters.showAsmaaOnly" type="checkbox" />
              <span>{{ t('roles.showAsmaaOnly') }}</span>
            </label>
          </div>
        </div>
      </CCardBody>
    </CCard>

    <div v-if="loading" class="text-center py-5">
      <CSpinner color="primary" />
      <p class="mt-3 text-muted">{{ t('common.loading') }}</p>
    </div>

    <div v-else-if="filteredRoles.length === 0" class="py-5">
      <EmptyState
        :title="t('roles.noRoles')"
        :description="t('roles.noRolesDescription')"
        icon="cil-shield-alt"
      />
    </div>

    <div v-else class="roles-grid">
      <CCard v-for="role in filteredRoles" :key="role.key" class="role-card">
        <CCardBody>
          <div class="role-header">
            <div class="role-icon" :class="`role-icon-${getRoleColor(role.key)}`">
              <CIcon :icon="getRoleIcon(role.key)" />
            </div>
            <div class="role-info">
              <h4 class="role-name">{{ role.name }}</h4>
              <div class="role-meta">
                <CBadge :color="getRoleColor(role.key)">
                  {{ getAsmaaCount(role) }} {{ t('roles.asmaaCapabilities') }}
                </CBadge>
                <span class="role-meta-muted">
                  {{ role.capabilities_count }} {{ t('roles.wpCapabilities') }}
                </span>
              </div>
            </div>
          </div>

          <CButton
            class="btn-view-caps w-100 mt-3"
            color="secondary"
            variant="outline"
            @click="viewCapabilities(role)"
          >
            <CIcon icon="cil-list" class="me-1" />
            {{ t('roles.viewCapabilities') }}
          </CButton>
        </CCardBody>
      </CCard>
    </div>

    <!-- Capabilities Modal -->
    <CModal
      :visible="showCapabilitiesModal"
      size="xl"
      scrollable
      @close="showCapabilitiesModal = false"
    >
      <CModalHeader>
        <CModalTitle>
          {{ selectedRole?.name }} - {{ t('roles.capabilities') }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <div v-if="loadingCapabilities" class="text-center py-4">
          <CSpinner color="primary" />
        </div>

        <div v-else-if="capabilitiesArray.length">
          <div class="modal-filters">
            <div class="search-box">
              <CIcon icon="cil-search" class="search-icon" />
              <input
                v-model="capFilters.search"
                type="text"
                class="search-input"
                :placeholder="t('roles.searchCapabilitiesPlaceholder')"
              />
            </div>
            <label class="toggle-row">
              <input v-model="capFilters.grantedOnly" type="checkbox" />
              <span>{{ t('roles.showGrantedOnly') }}</span>
            </label>
          </div>

          <div class="caps-summary">
            <CBadge color="success">{{ capsStats.granted }} {{ t('roles.granted') }}</CBadge>
            <CBadge color="secondary">{{ capsStats.denied }} {{ t('roles.denied') }}</CBadge>
          </div>

          <div v-for="module in filteredCapabilities" :key="module.key" class="capability-module">
            <details class="module-details" open>
              <summary class="module-title">
                <span class="module-title-left">
                  <CIcon icon="cil-folder" class="me-2" />
                  {{ formatModuleName(module.key) }}
                </span>
                <span class="module-title-right">
                  <CBadge color="success">{{ module.granted }}</CBadge>
                  <CBadge color="secondary">{{ module.total - module.granted }}</CBadge>
                </span>
              </summary>

              <div class="capabilities-list">
                <button
                  v-for="cap in module.items"
                  :key="cap.key"
                  type="button"
                  class="capability-item"
                  :class="{ 'has-capability': cap.has }"
                  @click="copyCapability(cap.key)"
                  :title="t('roles.copyCapabilityHint')"
                >
                  <div class="capability-icon">
                    <CIcon v-if="cap.has" icon="cil-check-circle" class="text-success" />
                    <CIcon v-else icon="cil-x-circle" class="text-muted" />
                  </div>
                  <div class="capability-name">{{ cap.name }}</div>
                  <div class="capability-key monospace">{{ cap.key }}</div>
                </button>
              </div>
            </details>
          </div>
        </div>
        <div v-else class="py-4">
          <EmptyState
            :title="t('roles.noCapabilities')"
            :description="t('roles.noCapabilitiesDescription')"
            icon="cil-lock-locked"
          />
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="showCapabilitiesModal = false">
          {{ t('common.close') }}
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import {
  CCard,
  CCardBody,
  CButton,
  CBadge,
  CSpinner,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CFormSelect,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import api from '@/utils/api';
import { useTranslation } from '@/composables/useTranslation';
import PageHeader from '@/components/UI/PageHeader.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import StatsCard from '@/components/UI/StatsCard.vue';

const { t } = useTranslation();

// State
const loading = ref(false);
const loadingCapabilities = ref(false);
const roles = ref([]);
const selectedRole = ref(null);
const capabilities = ref(null);
const showCapabilitiesModal = ref(false);

const filters = reactive({
  search: '',
  showAsmaaOnly: true,
});

const capFilters = reactive({
  search: '',
  grantedOnly: false,
});

// Methods
const loadRoles = async () => {
  loading.value = true;
  try {
    const response = await api.get('users/roles');
    if (response.data.success) {
      roles.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading roles:', error);
    alert(t('roles.errorLoading'));
  } finally {
    loading.value = false;
  }
};

const refresh = () => {
  loadRoles();
};

const viewCapabilities = async (role) => {
  selectedRole.value = role;
  showCapabilitiesModal.value = true;
  loadingCapabilities.value = true;
  capFilters.search = '';
  capFilters.grantedOnly = false;

  try {
    const response = await api.get(`users/roles/${role.key}`);
    if (response.data.success) {
      capabilities.value = response.data.data.capabilities;
    }
  } catch (error) {
    console.error('Error loading capabilities:', error);
    alert(t('roles.errorLoadingCapabilities'));
  } finally {
    loadingCapabilities.value = false;
  }
};

const getAsmaaCount = (role) => {
  return role?.asmaa_capabilities_count ?? 0;
};

const filteredRoles = computed(() => {
  const term = (filters.search || '').trim().toLowerCase();
  return roles.value.filter((r) => {
    if (filters.showAsmaaOnly && !String(r.key).startsWith('asmaa_') && r.key !== 'administrator') {
      // keep admin + asmaa roles; (admin often exists)
    }
    const hay = `${r.name} ${r.key}`.toLowerCase();
    return !term || hay.includes(term);
  });
});

const stats = computed(() => {
  return {
    totalRoles: roles.value.length,
    totalAsmaaCaps: roles.value.reduce((sum, r) => sum + (r.asmaa_capabilities_count || 0), 0),
    totalWpCaps: roles.value.reduce((sum, r) => sum + (r.capabilities_count || 0), 0),
  };
});

const capabilitiesArray = computed(() => {
  if (!capabilities.value) return [];
  return Object.entries(capabilities.value).map(([key, items]) => ({
    key,
    items,
    total: Array.isArray(items) ? items.length : 0,
    granted: Array.isArray(items) ? items.filter((x) => x.has).length : 0,
  }));
});

const filteredCapabilities = computed(() => {
  const term = (capFilters.search || '').trim().toLowerCase();
  return capabilitiesArray.value
    .map((m) => {
      const items = (m.items || []).filter((cap) => {
        if (capFilters.grantedOnly && !cap.has) return false;
        if (!term) return true;
        const hay = `${cap.name} ${cap.key}`.toLowerCase();
        return hay.includes(term);
      });
      return {
        ...m,
        items,
        total: items.length,
        granted: items.filter((x) => x.has).length,
      };
    })
    .filter((m) => m.items.length > 0);
});

const capsStats = computed(() => {
  const all = capabilitiesArray.value.flatMap((m) => m.items || []);
  const filtered = (capFilters.grantedOnly || (capFilters.search || '').trim())
    ? filteredCapabilities.value.flatMap((m) => m.items || [])
    : all;
  const granted = filtered.filter((x) => x.has).length;
  return {
    granted,
    denied: filtered.length - granted,
  };
});

const copyCapability = async (key) => {
  try {
    await navigator.clipboard.writeText(key);
  } catch (e) {
    // fallback
    const el = document.createElement('textarea');
    el.value = key;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }
};

const getRoleColor = (roleKey) => {
  if (roleKey === 'administrator' || roleKey === 'asmaa_super_admin') return 'danger';
  if (roleKey === 'asmaa_admin') return 'warning';
  if (roleKey === 'asmaa_manager') return 'primary';
  if (roleKey === 'asmaa_accountant') return 'success';
  if (roleKey === 'asmaa_receptionist') return 'info';
  if (roleKey === 'asmaa_cashier') return 'secondary';
  return 'dark';
};

const getRoleIcon = (roleKey) => {
  if (roleKey === 'administrator' || roleKey === 'asmaa_super_admin') return 'cil-star';
  if (roleKey === 'asmaa_admin') return 'cil-shield-alt';
  if (roleKey === 'asmaa_manager') return 'cil-briefcase';
  if (roleKey === 'asmaa_accountant') return 'cil-calculator';
  if (roleKey === 'asmaa_receptionist') return 'cil-phone';
  if (roleKey === 'asmaa_cashier') return 'cil-dollar';
  return 'cil-user';
};

const formatModuleName = (module) => {
  return module.charAt(0).toUpperCase() + module.slice(1).replace(/_/g, ' ');
};

// Lifecycle
onMounted(() => {
  loadRoles();
});
</script>

<style scoped>
.roles-page {
  width: 100%;
}

.btn-refresh {
  border-color: var(--asmaa-primary) !important;
  color: var(--asmaa-primary) !important;
}

.btn-refresh:hover,
.btn-refresh:focus {
  background: rgba(187, 160, 122, 0.12) !important;
  border-color: var(--asmaa-primary) !important;
  color: var(--asmaa-primary) !important;
}

.btn-view-caps {
  border-color: var(--asmaa-primary) !important;
  color: var(--asmaa-primary) !important;
  font-weight: 800;
}

.btn-view-caps:hover,
.btn-view-caps:focus {
  background: rgba(187, 160, 122, 0.12) !important;
  border-color: var(--asmaa-primary) !important;
  color: var(--asmaa-primary) !important;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.filters-row {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  position: relative;
  min-width: 260px;
}

.search-icon {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  width: 18px;
  height: 18px;
}

[dir="ltr"] .search-icon { left: 12px; }
[dir="rtl"] .search-icon { right: 12px; }

.search-input {
  width: 100%;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: var(--bg-primary);
  color: var(--text-primary);
  font-size: 14px;
  transition: all 0.2s;
}

[dir="ltr"] .search-input { padding: 10px 12px 10px 40px; }
[dir="rtl"] .search-input { padding: 10px 40px 10px 12px; }

.search-input:focus {
  outline: none;
  border-color: var(--asmaa-primary);
  box-shadow: 0 0 0 3px rgba(187, 160, 122, 0.1);
}

.toggle-row {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  color: var(--text-secondary);
  font-size: 13px;
}

.toggle-row input[type="checkbox"] {
  width: 18px;
  height: 18px;
}

.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.role-card {
  transition: all 0.3s ease;
}

.role-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.role-header {
  display: flex;
  align-items: center;
  gap: 16px;
}

.role-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
}

.role-icon-danger {
  background: rgba(var(--cui-danger-rgb), 0.1);
  color: var(--cui-danger);
}

.role-icon-warning {
  background: rgba(var(--cui-warning-rgb), 0.1);
  color: var(--cui-warning);
}

.role-icon-primary {
  background: rgba(var(--cui-primary-rgb), 0.1);
  color: var(--asmaa-primary);
}

.role-icon-success {
  background: rgba(var(--cui-success-rgb), 0.1);
  color: var(--cui-success);
}

.role-icon-info {
  background: rgba(var(--cui-info-rgb), 0.1);
  color: var(--cui-info);
}

.role-icon-secondary {
  background: rgba(0, 0, 0, 0.05);
  color: var(--text-secondary);
}

.role-icon-dark {
  background: rgba(0, 0, 0, 0.1);
  color: var(--text-primary);
}

.role-info {
  flex: 1;
}

.role-name {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 8px 0;
}

.role-meta {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.role-meta-muted {
  font-size: 12px;
  color: var(--text-muted);
}

.capability-module {
  margin-bottom: 2rem;
}

.modal-filters {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.caps-summary {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 12px;
}

.module-details {
  border: 1px solid var(--border-color);
  border-radius: 12px;
  background: var(--bg-secondary);
  overflow: hidden;
}

.module-details + .module-details {
  margin-top: 12px;
}

.module-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  list-style: none;
}

.module-title::-webkit-details-marker {
  display: none;
}

.module-title-left {
  display: inline-flex;
  align-items: center;
}

.module-title-right {
  display: inline-flex;
  gap: 8px;
  align-items: center;
}

.capabilities-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 8px;
  padding: 12px;
}

.capability-item {
  display: grid;
  grid-template-columns: 18px 1fr;
  gap: 8px;
  align-items: center;
  padding: 8px 12px;
  border-radius: 6px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  transition: all 0.2s;
  text-align: left;
}

[dir="rtl"] .capability-item {
  text-align: right;
}

.capability-item.has-capability {
  background: rgba(var(--cui-success-rgb), 0.05);
  border-color: rgba(var(--cui-success-rgb), 0.2);
}

.capability-item:hover {
  border-color: rgba(var(--cui-primary-rgb), 0.35);
  background: var(--bg-tertiary);
}

.capability-icon {
  font-size: 16px;
  display: flex;
  align-items: center;
}

.capability-name {
  font-size: 13px;
  font-weight: 500;
  color: var(--text-primary);
}

.capability-key {
  grid-column: 2;
  font-size: 11px;
  color: var(--text-muted);
}

.monospace {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.text-muted {
  color: var(--text-muted);
}
</style>


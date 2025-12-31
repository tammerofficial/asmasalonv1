<template>
  <div class="programs-settings-page">
    <PageHeader :title="t('programsSettings.title')" :subtitle="t('programsSettings.subtitle')">
      <template #icon>
        <CIcon icon="cil-settings" />
      </template>
      <template #actions>
        <CButton
          color="secondary"
          variant="outline"
          class="me-2 apple-wallet-btn"
          @click="openAppleWalletModal"
        >
          <CIcon icon="cil-wallet" class="me-2" />
          {{ t('programsSettings.appleWallet') || 'Apple Wallet' }}
        </CButton>
        <CButton
          color="primary"
          class="btn-primary-custom"
          :disabled="saving"
          @click="saveSettings"
        >
          <CIcon icon="cil-save" class="me-2" />
          {{ saving ? t('common.saving') : t('common.save') }}
        </CButton>
      </template>
    </PageHeader>

    <div class="stats-grid">
      <StatCard
        :label="t('programsSettings.loyaltySection')"
        :value="settings.loyalty.enabled ? t('common.active') : t('common.inactive')"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-star" />
        </template>
      </StatCard>

      <StatCard
        :label="t('programsSettings.commissionsSection')"
        :value="settings.commissions.enabled ? t('common.active') : t('common.inactive')"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-dollar" />
        </template>
      </StatCard>

      <StatCard
        :label="t('programsSettings.overrides')"
        :value="overrideCount"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-list" />
        </template>
      </StatCard>

      <StatCard
        :label="t('programsSettings.lastSaved')"
        :value="lastSavedText"
        badge-variant="info"
        color="gold"
      >
        <template #icon>
          <CIcon icon="cil-clock" />
        </template>
      </StatCard>
    </div>

    <!-- Loyalty Settings -->
    <Card :title="t('programsSettings.loyaltySection')" icon="cil-star" class="section-card">
      <CRow class="g-3">
        <CCol :md="4">
          <CFormSwitch v-model="settings.loyalty.enabled" :label="t('programsSettings.enabled')" />
        </CCol>
        <CCol :md="4">
          <CFormInput
            v-model.number="loyaltyDefaultPoints"
            type="number"
            min="0"
            step="1"
            :label="t('programsSettings.defaultPointsPerItem')"
            class="filter-input"
          />
        </CCol>
        <CCol :md="4">
          <CFormInput
            v-model.number="settings.loyalty.point_value_kwd"
            type="number"
            min="0"
            step="0.001"
            :label="t('programsSettings.pointValueKwd')"
            class="filter-input"
          />
        </CCol>
      </CRow>
    </Card>

    <!-- Commissions Settings -->
    <Card :title="t('programsSettings.commissionsSection')" icon="cil-dollar" class="section-card">
      <CRow class="g-3">
        <CCol :md="4">
          <CFormSwitch v-model="settings.commissions.enabled" :label="t('programsSettings.enabled')" />
        </CCol>
        <CCol :md="4">
          <CFormInput
            v-model.number="settings.commissions.default_service_rate"
            type="number"
            min="0"
            step="0.01"
            :label="t('programsSettings.defaultServiceCommission')"
            class="filter-input"
          />
        </CCol>
        <CCol :md="4">
          <CFormInput
            v-model.number="settings.commissions.default_product_rate"
            type="number"
            min="0"
            step="0.01"
            :label="t('programsSettings.defaultProductCommission')"
            class="filter-input"
          />
        </CCol>

        <CCol :md="12">
          <div class="override-builder">
            <div class="override-builder-title">{{ t('programsSettings.addStaffOverride') }}</div>
            <div class="override-builder-row staff-override-row">
              <CFormSelect v-model="staffOverrideDraft.staffId" class="filter-select">
                <option value="">{{ t('programsSettings.selectStaff') }}</option>
                <option v-for="s in staffOptions" :key="s.id" :value="String(s.id)">
                  {{ s.name }}
                </option>
              </CFormSelect>

              <div class="staff-rate">
                <label class="small text-muted">{{ t('programsSettings.service') }} %</label>
                <CFormInput v-model.number="staffOverrideDraft.service_rate" type="number" min="0" step="0.01" class="filter-input" />
              </div>

              <div class="staff-rate">
                <label class="small text-muted">{{ t('programsSettings.product') }} %</label>
                <CFormInput v-model.number="staffOverrideDraft.product_rate" type="number" min="0" step="0.01" class="filter-input" />
              </div>

              <button class="action-btn" type="button" @click="addStaffOverride" :title="t('common.add')">
                <CIcon icon="cil-plus" />
              </button>
            </div>
          </div>
        </CCol>

        <CCol :md="12">
          <div class="overrides-list">
            <div class="overrides-list-title">{{ t('programsSettings.currentStaffOverrides') }}</div>

            <div v-if="Object.keys(settings.commissions.staff_overrides || {}).length === 0" class="text-muted small">
              {{ t('programsSettings.noStaffOverrides') }}
            </div>

            <div v-else class="overrides-grid">
              <div
                v-for="(ov, staffId) in settings.commissions.staff_overrides"
                :key="staffId"
                class="override-pill"
              >
                <div class="override-pill-title">{{ renderStaffName(staffId) }}</div>
                <div class="override-pill-value">
                  {{ t('programsSettings.service') }}: {{ Number(ov?.service_rate || 0) }}% ·
                  {{ t('programsSettings.product') }}: {{ Number(ov?.product_rate || 0) }}%
                </div>
                <button class="pill-remove" type="button" @click="removeStaffOverride(staffId)">×</button>
              </div>
            </div>
          </div>
        </CCol>
      </CRow>
    </Card>
    
    <!-- Apple Wallet Modal -->
    <CModal :visible="showAppleWalletModal" @close="closeAppleWalletModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-wallet" class="me-2" />
          {{ t('programsSettings.createAppleWallet') || 'Create Apple Wallet Pass' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CFormSelect v-model="appleWalletForm.customer_id" :label="t('common.selectCustomer') || 'Select Customer'" class="mb-3">
          <option value="">{{ t('common.selectCustomer') || 'Select Customer' }}</option>
          <option v-for="customer in customers" :key="customer.id" :value="customer.id">
            {{ customer.display_name || customer.user_email }}
          </option>
        </CFormSelect>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeAppleWalletModal">
          {{ t('common.cancel') }}
        </CButton>
        <CButton color="primary" @click="createAppleWalletPass" :disabled="creatingPass || !appleWalletForm.customer_id">
          <CSpinner v-if="creatingPass" size="sm" class="me-2" />
          <CIcon v-else icon="cil-wallet" class="me-2" />
          {{ creatingPass ? t('common.creating') : t('common.create') }}
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  CButton,
  CRow,
  CCol,
  CFormInput,
  CFormSelect,
  CFormSwitch,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CSpinner,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();

const saving = ref(false);
const lastSavedAt = ref(null);
const showAppleWalletModal = ref(false);
const creatingPass = ref(false);
const customers = ref([]);
const appleWalletForm = ref({ customer_id: '' });

const settings = ref({
  loyalty: {
    enabled: true,
    default_service_points: 1,
    default_product_points: 1,
    point_value_kwd: 0,
    item_overrides: {},
  },
  commissions: {
    enabled: true,
    default_service_rate: 10,
    default_product_rate: 5,
    staff_overrides: {},
  },
});

const staff = ref([]);

const staffOverrideDraft = ref({
  staffId: '',
  service_rate: 0,
  product_rate: 0,
});

// One value applies to all services + products
const loyaltyDefaultPoints = computed({
  get() {
    const s = Number(settings.value?.loyalty?.default_service_points ?? 1);
    const p = Number(settings.value?.loyalty?.default_product_points ?? s);
    return Number.isFinite(s) ? s : (Number.isFinite(p) ? p : 1);
  },
  set(v) {
    const n = Math.max(0, Number(v || 0));
    settings.value.loyalty.default_service_points = n;
    settings.value.loyalty.default_product_points = n;
  },
});

const staffOptions = computed(() => staff.value || []);

const overrideCount = computed(() => {
  const staffOv = Object.keys(settings.value?.commissions?.staff_overrides || {}).length;
  return staffOv;
});

const lastSavedText = computed(() => {
  if (!lastSavedAt.value) return '—';
  try {
    return new Date(lastSavedAt.value).toLocaleString();
  } catch {
    return '—';
  }
});

const loadSettings = async () => {
  const res = await api.get('/programs/settings', { noCache: true });
  const data = res.data?.data || res.data || {};
  settings.value = {
    loyalty: {
      ...(settings.value.loyalty || {}),
      ...(data.loyalty || {}),
      // Enforce global default points (no per-item overrides)
      item_overrides: {},
    },
    commissions: {
      ...(settings.value.commissions || {}),
      ...(data.commissions || {}),
      staff_overrides: data.commissions?.staff_overrides || {},
    },
  };
};

const loadLookups = async () => {
  const [staffRes] = await Promise.all([
    api.get('/staff', { params: { per_page: 200 }, noCache: true }),
  ]);

  const staffData = staffRes.data?.data || staffRes.data || {};
  staff.value = staffData.items || staffData || [];
};

const addStaffOverride = () => {
  const staffId = String(staffOverrideDraft.value.staffId || '');
  if (!staffId) {
    toast.error(t('programsSettings.selectStaff'));
    return;
  }

  const serviceRate = Number(staffOverrideDraft.value.service_rate || 0);
  const productRate = Number(staffOverrideDraft.value.product_rate || 0);

  settings.value.commissions.staff_overrides = {
    ...(settings.value.commissions.staff_overrides || {}),
    [staffId]: {
      service_rate: serviceRate,
      product_rate: productRate,
    },
  };

  staffOverrideDraft.value = { staffId: '', service_rate: 0, product_rate: 0 };
  toast.success(t('programsSettings.added'));
};

const removeStaffOverride = (staffId) => {
  const next = { ...(settings.value.commissions.staff_overrides || {}) };
  delete next[String(staffId)];
  settings.value.commissions.staff_overrides = next;
};

const renderStaffName = (staffId) => {
  const s = staff.value.find((x) => String(x.id) === String(staffId));
  return s ? s.name : `#${staffId}`;
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const payload = {
      loyalty: {
        ...settings.value.loyalty,
        default_service_points: Number(settings.value.loyalty.default_service_points || 0),
        default_product_points: Number(settings.value.loyalty.default_product_points || 0),
        point_value_kwd: Number(settings.value.loyalty.point_value_kwd || 0),
        // No per-item overrides (apply to all)
        item_overrides: {},
      },
      commissions: {
        ...settings.value.commissions,
        default_service_rate: Number(settings.value.commissions.default_service_rate || 0),
        default_product_rate: Number(settings.value.commissions.default_product_rate || 0),
      },
    };

    await api.put('/programs/settings', payload);
    lastSavedAt.value = new Date().toISOString();
    toast.success(t('programsSettings.saved'));
  } catch (e) {
    console.error('Save programs settings error:', e);
    toast.error(t('common.errorLoading'));
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  try {
    await Promise.all([loadLookups(), loadSettings()]);
  } catch (e) {
    console.error('Programs settings init error:', e);
    toast.error(t('common.errorLoading'));
  }
});
</script>

<style scoped>
.programs-settings-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.section-card {
  border: 1px solid var(--border-color);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.btn-primary-custom {
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  border: none;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
  transition: all 0.3s;
}

.btn-primary-custom:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
  box-shadow: 0 6px 16px rgba(187, 160, 122, 0.4);
  transform: translateY(-2px);
}

.filter-input,
.filter-select {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid var(--border-color);
}

.filter-input:focus,
.filter-select:focus {
  border-color: var(--asmaa-primary);
  box-shadow: 0 0 0 3px rgba(187, 160, 122, 0.15);
  outline: none;
}

.override-builder {
  border: 1px dashed rgba(187, 160, 122, 0.45);
  border-radius: 12px;
  padding: 1rem;
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.06) 0%, rgba(187, 160, 122, 0.03) 100%);
}

.override-builder-title {
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.75rem;
}

.override-builder-row {
  display: grid;
  grid-template-columns: 160px 1fr 160px 44px;
  gap: 0.75rem;
  align-items: center;
}

.staff-override-row {
  grid-template-columns: 1fr 220px 220px 44px;
}

.staff-rate {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.action-btn {
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, rgba(187, 160, 122, 0.9) 100%);
  color: #fff;
  box-shadow: 0 2px 6px rgba(187, 160, 122, 0.3);
}

.action-btn:hover {
  background: linear-gradient(135deg, rgba(187, 160, 122, 0.95) 0%, var(--asmaa-primary) 100%);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.4);
}

.overrides-list {
  border-top: 1px solid var(--border-color);
  padding-top: 1rem;
}

.overrides-list-title {
  font-weight: 800;
  margin-bottom: 0.75rem;
}

.overrides-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 0.75rem;
}

.override-pill {
  position: relative;
  border-radius: 12px;
  border: 1px solid rgba(187, 160, 122, 0.25);
  background: #fff;
  padding: 0.9rem 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.override-pill-title {
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.override-pill-value {
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.pill-remove {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 28px;
  height: 28px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary);
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s;
}

[dir="rtl"] .pill-remove {
  right: auto;
  left: 10px;
}

.pill-remove:hover {
  border-color: var(--asmaa-primary);
  color: var(--asmaa-primary);
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .override-builder-row,
  .staff-override-row {
    grid-template-columns: 1fr;
  }
}
</style>


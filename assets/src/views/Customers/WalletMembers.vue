<template>
  <div class="wallet-members-page">
    <PageHeader :title="t('customers.walletMembers')" :subtitle="t('customers.walletMembersSubtitle')">
      <template #icon>
        <CIcon icon="cil-wallet" />
      </template>
      <template #actions>
        <CButton color="primary" class="btn-primary-custom" @click="loadMembers">
          <CIcon icon="cil-reload" class="me-2" />
          {{ t('common.refresh') }}
        </CButton>
      </template>
    </PageHeader>

    <LoadingSpinner v-if="loading" :text="t('common.loading')" />

    <EmptyState 
      v-else-if="members.length === 0"
      :title="t('customers.noWalletMembers')"
      :description="t('customers.noWalletMembersDesc')"
      icon-color="gray"
    />

    <div v-else class="members-grid">
      <div v-for="member in members" :key="member.id" class="member-wallet-card">
        <div class="member-info" @click="goToProfile(member.id)">
          <div class="member-header">
            <strong class="member-name">{{ member.display_name }}</strong>
            <CBadge color="info" class="points-badge">
              <CIcon icon="cil-star" class="me-1" />
              {{ member.loyalty_points || 0 }} {{ t('customers.points') }}
            </CBadge>
          </div>
          <div class="member-email text-muted small">{{ member.user_email }}</div>
        </div>

        <div class="passes-container">
          <div v-for="pass in member.passes" :key="pass.id" class="apple-pass-visual" :class="pass.pass_type">
            <div class="pass-top">
              <CIcon :icon="getPassIcon(pass.pass_type)" class="pass-icon" />
              <span class="pass-type-label">{{ getPassLabel(pass.pass_type) }}</span>
            </div>
            <div class="pass-content">
              <div v-if="pass.pass_type === 'loyalty'" class="pass-data">
                <div class="data-label">{{ t('customers.points') }}</div>
                <div class="data-value">{{ member.loyalty_points || 0 }}</div>
              </div>
              <div v-else-if="pass.pass_type === 'membership'" class="pass-data">
                <div class="data-label">{{ t('memberships.plan') }}</div>
                <div class="data-value small">{{ getPassValue(pass, 'plan') }}</div>
              </div>
              <div v-else class="pass-data">
                <div class="data-label">{{ t('common.status') }}</div>
                <div class="data-value small">{{ t('common.active') }}</div>
              </div>
            </div>
            <div class="pass-footer">
              <CButton color="light" size="sm" class="download-pass-btn" @click.stop="downloadPass(pass)">
                <CIcon icon="cil-cloud-download" class="me-1" />
                {{ t('common.view') }}
              </CButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { CButton, CBadge } from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import PageHeader from '@/components/UI/PageHeader.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();

const members = ref([]);
const loading = ref(false);

const loadMembers = async () => {
  loading.value = true;
  try {
    const response = await api.get('/apple-wallet/members', { noCache: true });
    members.value = response.data?.data || response.data || [];
  } catch (error) {
    console.error('Error loading wallet members:', error);
    toast.error(t('common.errorLoading'));
  } finally {
    loading.value = false;
  }
};

const goToProfile = (id) => {
  router.push(`/customers/${id}/profile`);
};

const downloadPass = (pass) => {
  const url = api.baseURL + '/apple-wallet/pass/' + pass.serial_number;
  window.open(url, '_blank');
};

const getPassIcon = (type) => {
  switch (type) {
    case 'loyalty': return 'cil-star';
    case 'membership': return 'cil-credit-card';
    case 'commissions': return 'cil-money';
    case 'programs': return 'cil-list';
    default: return 'cil-wallet';
  }
};

const getPassLabel = (type) => {
  switch (type) {
    case 'loyalty': return t('loyalty.title');
    case 'membership': return t('memberships.title');
    case 'commissions': return t('commissions.title');
    case 'programs': return t('nav.programsSettings');
    default: return 'Apple Wallet';
  }
};

const getPassValue = (pass, key) => {
  try {
    const data = pass.pass_data?.storeCard || {};
    if (key === 'plan') {
      return data.primaryFields?.[0]?.value || '—';
    }
    return '—';
  } catch {
    return '—';
  }
};

onMounted(loadMembers);
</script>

<style scoped>
.wallet-members-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.member-wallet-card {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: var(--shadow-sm);
  transition: all 0.3s ease;
}

.member-wallet-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}

.member-info {
  margin-bottom: 1.25rem;
  cursor: pointer;
}

.member-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.member-name {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--text-primary);
}

.points-badge {
  font-size: 0.8rem;
  padding: 0.4rem 0.75rem;
  border-radius: 8px;
}

.passes-container {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding-bottom: 0.5rem;
  scrollbar-width: thin;
}

.apple-pass-visual {
  min-width: 140px;
  height: 180px;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  color: white;
  position: relative;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.apple-pass-visual::after {
  content: "";
  position: absolute;
  top: -50%;
  right: -50%;
  width: 100%;
  height: 100%;
  background: rgba(255,255,255,0.1);
  transform: rotate(45deg);
  pointer-events: none;
}

/* Pass Colors */
.apple-pass-visual.loyalty { background: linear-gradient(135deg, #8E7E78 0%, #a18a62 100%); }
.apple-pass-visual.membership { background: linear-gradient(135deg, #8b4513 0%, #5d2e0d 100%); }
.apple-pass-visual.programs { background: linear-gradient(135deg, #4b0082 0%, #310055 100%); }
.apple-pass-visual.commissions { background: linear-gradient(135deg, #006400 0%, #004400 100%); }

.pass-top {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.pass-icon {
  font-size: 1.5rem;
  opacity: 0.9;
}

.pass-type-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 700;
  text-align: center;
}

.pass-content {
  text-align: center;
}

.data-label {
  font-size: 0.65rem;
  opacity: 0.8;
  margin-bottom: 0.25rem;
}

.data-value {
  font-size: 1.25rem;
  font-weight: 800;
}

.data-value.small {
  font-size: 0.9rem;
}

.pass-footer {
  display: flex;
  justify-content: center;
}

.download-pass-btn {
  background: rgba(255,255,255,0.2);
  border: none;
  color: white;
  font-size: 0.7rem;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.download-pass-btn:hover {
  background: rgba(255,255,255,0.3);
}

@media (max-width: 768px) {
  .members-grid {
    grid-template-columns: 1fr;
  }
}
</style>


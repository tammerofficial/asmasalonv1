<template>
  <div class="customer-profile-page">
    <PageHeader :title="profile.customer?.name || t('customers.title')" :subtitle="t('customers.profileSubtitle') || t('customers.title')">
      <template #icon>
        <CIcon icon="cil-user" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" class="me-2" @click="goBack">
          <CIcon icon="cil-arrow-left" class="me-2" />
          {{ t('common.back') || 'Back' }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="refresh">
          <CIcon icon="cil-reload" class="me-2" />
          {{ t('common.refresh') || 'Refresh' }}
        </CButton>
      </template>
    </PageHeader>

    <LoadingSpinner v-if="loading" :text="t('common.loading')" />

    <template v-else>
      <div class="stats-grid">
        <StatCard :label="t('customers.points')" :value="profile.customer?.loyalty_points || 0" badge-variant="info" color="gold">
          <template #icon><CIcon icon="cil-star" /></template>
        </StatCard>
        <StatCard :label="t('customers.spending')" :value="formatCurrencyShort(profile.customer?.total_spent || 0)" badge-variant="info" color="gold">
          <template #icon><CIcon icon="cil-money" /></template>
        </StatCard>
        <StatCard :label="t('customers.todaysPurchases') || 'Today Purchases'" :value="formatCurrencyShort(profile.today?.total_amount || 0)" badge-variant="success" color="gold">
          <template #icon><CIcon icon="cil-cart" /></template>
        </StatCard>
        <StatCard :label="t('customers.todaysPoints') || 'Today Points'" :value="profile.today?.points_earned || 0" badge-variant="warning" color="gold">
          <template #icon><CIcon icon="cil-gift" /></template>
        </StatCard>
      </div>

      <Card :title="t('customers.customerDetails') || 'Customer Details'" icon="cil-user" class="section-card">
        <div class="details-grid">
          <div class="detail-row">
            <div class="detail-label">{{ t('customers.phone') }}</div>
            <div class="detail-value">
              <a v-if="profile.customer?.phone" :href="`tel:${profile.customer.phone}`" class="detail-link">{{ profile.customer.phone }}</a>
              <span v-else>—</span>
            </div>
          </div>
          <div class="detail-row" v-if="profile.customer?.email">
            <div class="detail-label">{{ t('customers.email') }}</div>
            <div class="detail-value">
              <a :href="`mailto:${profile.customer.email}`" class="detail-link">{{ profile.customer.email }}</a>
            </div>
          </div>
          <div class="detail-row" v-if="profile.customer?.city">
            <div class="detail-label">{{ t('customers.city') }}</div>
            <div class="detail-value">{{ profile.customer.city }}</div>
          </div>
          <div class="detail-row" v-if="profile.customer?.address">
            <div class="detail-label">{{ t('customers.address') || 'Address' }}</div>
            <div class="detail-value">{{ profile.customer.address }}</div>
          </div>
          <div class="detail-row" v-if="profile.customer?.gender">
            <div class="detail-label">{{ t('customers.gender') }}</div>
            <div class="detail-value">{{ profile.customer.gender }}</div>
          </div>
          <div class="detail-row" v-if="profile.customer?.notes">
            <div class="detail-label">{{ t('customers.notes') }}</div>
            <div class="detail-value">{{ profile.customer.notes }}</div>
          </div>
          <div class="detail-row" v-if="profile.customer?.created_at">
            <div class="detail-label">{{ t('common.createdAt') || 'Created At' }}</div>
            <div class="detail-value">{{ formatDate(profile.customer.created_at) }}</div>
          </div>
        </div>
      </Card>

      <Card :title="t('memberships.title') || 'Memberships'" icon="cil-credit-card" class="section-card">
        <div class="membership-header">
          <div class="membership-summary">
            <div class="membership-title">
              <strong>{{ currentMembership?.plan_name_ar || currentMembership?.plan_name || (t('memberships.noMembership') || 'No membership') }}</strong>
            </div>
            <div v-if="currentMembership" class="membership-sub">
              <span class="text-muted">{{ currentMembership.start_date }} → {{ currentMembership.end_date }}</span>
              <span class="text-muted"> · </span>
              <span class="text-muted">{{ t('common.status') }}: {{ currentMembership.status }}</span>
            </div>
          </div>

          <div class="membership-actions">
            <CButton color="primary" class="btn-primary-custom" @click="openAssignModal">
              <CIcon icon="cil-user-follow" class="me-2" />
              {{ t('memberships.assign') || 'Assign Membership' }}
            </CButton>
            <CButton v-if="currentMembership" color="secondary" variant="outline" class="ms-2" @click="openRenewModal">
              <CIcon icon="cil-clock" class="me-2" />
              {{ t('memberships.renew') || 'Renew' }}
            </CButton>
            <CButton v-if="currentMembership" color="secondary" variant="outline" class="ms-2" @click="cancelMembership">
              <CIcon icon="cil-ban" class="me-2" />
              {{ t('memberships.cancel') || 'Cancel' }}
            </CButton>
          </div>
        </div>

        <div v-if="currentMembership" class="membership-badges">
          <CBadge class="unified-badge">
            <CIcon icon="cil-spreadsheet" class="badge-icon" />
            <span>{{ currentMembership.services_used || 0 }} / {{ currentMembership.services_limit ?? '∞' }}</span>
          </CBadge>
          <CBadge class="unified-badge">
            <CIcon icon="cil-star" class="badge-icon" />
            <span>x{{ currentMembership.points_multiplier || 1 }}</span>
          </CBadge>
        </div>
      </Card>

      <Card :title="t('loyalty.appleWallet') || 'Apple Wallet Passes'" icon="cil-wallet" class="section-card">
        <div class="wallet-passes-grid">
          <div class="wallet-pass-item">
            <div class="pass-info">
              <CIcon icon="cil-star" class="me-2" />
              <strong>{{ t('loyalty.title') }}</strong>
            </div>
            <CButton color="secondary" variant="outline" size="sm" @click="createTypedPass('loyalty')" :disabled="creatingPass">
              <CSpinner v-if="creatingPass && activePassType === 'loyalty'" size="sm" class="me-1" />
              <CIcon v-else icon="cil-wallet" class="me-1" />
              {{ t('common.create') }}
            </CButton>
          </div>
          <div class="wallet-pass-item" v-if="currentMembership">
            <div class="pass-info">
              <CIcon icon="cil-credit-card" class="me-2" />
              <strong>{{ t('memberships.title') }}</strong>
            </div>
            <CButton color="secondary" variant="outline" size="sm" @click="createTypedPass('membership')" :disabled="creatingPass">
              <CSpinner v-if="creatingPass && activePassType === 'membership'" size="sm" class="me-1" />
              <CIcon v-else icon="cil-wallet" class="me-1" />
              {{ t('common.create') }}
            </CButton>
          </div>
        </div>
      </Card>

      <Card :title="t('customers.todayDetails') || 'Today Details'" icon="cil-calendar" class="section-card">
        <div class="today-header">
          <div class="today-date">
            <CIcon icon="cil-calendar" class="me-2" />
            <strong>{{ profile.today?.date || '—' }}</strong>
          </div>
          <div class="today-meta">
            <CBadge class="unified-badge">{{ (profile.today?.orders || []).length }} {{ t('customers.ordersToday') || 'orders' }}</CBadge>
            <CBadge class="unified-badge">{{ (profile.today?.items || []).length }} {{ t('customers.itemsToday') || 'items' }}</CBadge>
          </div>
        </div>

        <div v-if="(profile.today?.items || []).length === 0" class="text-muted">
          {{ t('customers.noPurchasesToday') || 'No purchases today' }}
        </div>

        <div v-else class="table-wrapper">
          <CTable hover responsive class="table-modern">
            <thead>
              <tr class="table-header-row">
                <th>{{ t('common.date') }}</th>
                <th>{{ t('customers.item') || 'Item' }}</th>
                <th>{{ t('customers.itemType') || 'Type' }}</th>
                <th class="text-center">{{ t('customers.quantity') || 'Qty' }}</th>
                <th class="text-end">{{ t('customers.amount') || 'Amount' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in profile.today.items" :key="it.id" class="table-row">
                <td>{{ formatDate(it.created_at) }}</td>
                <td><strong>{{ it.item_name }}</strong></td>
                <td>
                  <CBadge class="unified-badge" :class="it.item_type === 'product' ? 'badge-product' : 'badge-service'">
                    <CIcon :icon="it.item_type === 'product' ? 'cil-basket' : 'cil-spreadsheet'" class="badge-icon" />
                    <span>{{ it.item_type }}</span>
                  </CBadge>
                </td>
                <td class="text-center">{{ it.quantity || 1 }}</td>
                <td class="text-end"><strong class="unified-amount">{{ formatCurrency(it.total || 0) }}</strong></td>
              </tr>
            </tbody>
          </CTable>
        </div>
      </Card>

      <Card :title="t('customers.todayLoyalty') || 'Today Loyalty Transactions'" icon="cil-star" class="section-card">
        <div v-if="(profile.loyalty?.today_transactions || []).length === 0" class="text-muted">
          {{ t('customers.noLoyaltyToday') || 'No loyalty transactions today' }}
        </div>
        <div v-else class="table-wrapper">
          <CTable hover responsive class="table-modern">
            <thead>
              <tr class="table-header-row">
                <th>{{ t('common.date') }}</th>
                <th>{{ t('loyalty.type') || 'Type' }}</th>
                <th class="text-center">{{ t('loyalty.points') }}</th>
                <th>{{ t('loyalty.description') || 'Description' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in profile.loyalty.today_transactions" :key="tx.id" class="table-row">
                <td>{{ formatDate(tx.created_at) }}</td>
                <td>
                  <CBadge class="unified-badge" :class="tx.type === 'earned' ? 'badge-earned' : 'badge-other'">
                    <CIcon :icon="tx.type === 'earned' ? 'cil-plus' : 'cil-info'" class="badge-icon" />
                    <span>{{ tx.type }}</span>
                  </CBadge>
                </td>
                <td class="text-center"><strong>{{ tx.points }}</strong></td>
                <td>{{ tx.description || '—' }}</td>
              </tr>
            </tbody>
          </CTable>
        </div>
      </Card>

      <!-- Assign Membership Modal -->
      <CModal :visible="showAssignModal" @close="closeAssignModal" size="lg">
        <CModalHeader>
          <CModalTitle>
            <CIcon icon="cil-user-follow" class="me-2" />
            {{ t('memberships.assign') || 'Assign Membership' }}
          </CModalTitle>
        </CModalHeader>
        <CModalBody>
          <CRow class="g-3">
            <CCol :md="12">
              <CFormSelect v-model="assignForm.membership_plan_id" :label="t('memberships.plan') || 'Plan'">
                <option value="">{{ t('common.select') }}</option>
                <option v-for="p in plans" :key="p.id" :value="String(p.id)">{{ p.name_ar || p.name }}</option>
              </CFormSelect>
            </CCol>
            <CCol :md="6">
              <CFormInput v-model.number="assignForm.duration_months" type="number" min="1" step="1" :label="t('memberships.durationMonths') || 'Duration (months)'" />
            </CCol>
            <CCol :md="6" class="d-flex align-items-center">
              <CFormCheck v-model="assignForm.auto_renew" :label="t('memberships.autoRenew') || 'Auto renew'" />
            </CCol>
          </CRow>
        </CModalBody>
        <CModalFooter>
          <CButton color="secondary" @click="closeAssignModal">{{ t('common.cancel') }}</CButton>
          <CButton color="primary" class="btn-primary-custom" :disabled="assigning" @click="assignMembership">
            <CIcon icon="cil-check" class="me-2" />
            {{ assigning ? t('common.saving') : (t('memberships.assign') || 'Assign') }}
          </CButton>
        </CModalFooter>
      </CModal>

      <!-- Renew Membership Modal -->
      <CModal :visible="showRenewModal" @close="closeRenewModal" size="lg">
        <CModalHeader>
          <CModalTitle>
            <CIcon icon="cil-clock" class="me-2" />
            {{ t('memberships.renew') || 'Renew' }}
          </CModalTitle>
        </CModalHeader>
        <CModalBody>
          <CRow class="g-3">
            <CCol :md="6">
              <CFormInput v-model.number="renewForm.months" type="number" min="1" step="1" :label="t('memberships.monthsToExtend') || 'Months'" />
            </CCol>
            <CCol :md="6">
              <CFormInput v-model.number="renewForm.amount_paid" type="number" min="0" step="0.001" :label="t('memberships.amountPaid') || 'Amount paid'" />
            </CCol>
          </CRow>
        </CModalBody>
        <CModalFooter>
          <CButton color="secondary" @click="closeRenewModal">{{ t('common.cancel') }}</CButton>
          <CButton color="primary" class="btn-primary-custom" :disabled="renewing" @click="renewMembership">
            <CIcon icon="cil-check" class="me-2" />
            {{ renewing ? t('common.saving') : (t('memberships.renew') || 'Renew') }}
          </CButton>
        </CModalFooter>
      </CModal>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { CButton, CTable, CBadge, CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter, CFormSelect, CFormInput, CFormCheck, CRow, CCol, CSpinner } from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const route = useRoute();
const router = useRouter();

const loading = ref(false);
const profile = ref({ customer: null, today: { orders: [], items: [], total_amount: 0, points_earned: 0 }, membership: { current: null, history: [] }, loyalty: { today_transactions: [] } });

const plans = ref([]);
const showAssignModal = ref(false);
const assigning = ref(false);
const showRenewModal = ref(false);
const renewing = ref(false);
const creatingPass = ref(false);
const activePassType = ref('');

const assignForm = ref({ membership_plan_id: '', duration_months: 1, auto_renew: false });
const renewForm = ref({ months: 1, amount_paid: 0 });

const currentMembership = computed(() => profile.value?.membership?.current || null);

const loadPlans = async () => {
  try {
    const res = await api.get('/memberships/plans', { noCache: true });
    plans.value = res.data?.data || [];
  } catch {
    plans.value = [];
  }
};

const formatCurrency = (amount) =>
  new Intl.NumberFormat('en-KW', { style: 'currency', currency: 'KWD', minimumFractionDigits: 3 }).format(amount || 0);

const formatCurrencyShort = (amount) => {
  const value = Number(amount || 0);
  if (value >= 1000) return `${(value / 1000).toFixed(1)}K KWD`;
  return `${value.toFixed(0)} KWD`;
};

const formatDate = (d) => {
  if (!d) return '—';
  try {
    return new Date(d).toLocaleString();
  } catch {
    return String(d);
  }
};

const loadProfile = async () => {
  loading.value = true;
  try {
    const id = route.params.id;
    const res = await api.get(`/customers/${id}/profile`, { noCache: true });
    profile.value = res.data?.data || res.data || profile.value;
  } catch (e) {
    console.error('Load customer profile error:', e);
    toast.error(t('common.errorLoading'));
  } finally {
    loading.value = false;
  }
};

const refresh = () => loadProfile();
const goBack = () => router.push('/customers');

const openAssignModal = async () => {
  showAssignModal.value = true;
  assignForm.value = { membership_plan_id: '', duration_months: 1, auto_renew: false };
  if (plans.value.length === 0) {
    await loadPlans();
  }
};

const closeAssignModal = () => {
  showAssignModal.value = false;
  assigning.value = false;
};

const assignMembership = async () => {
  if (!profile.value?.customer?.id || !assignForm.value.membership_plan_id) {
    toast.error(t('memberships.selectPlan') || 'Select plan');
    return;
  }
  assigning.value = true;
  try {
    await api.post('/memberships', {
      customer_id: Number(profile.value.customer.id),
      membership_plan_id: Number(assignForm.value.membership_plan_id),
      duration_months: Number(assignForm.value.duration_months || 1),
      auto_renew: assignForm.value.auto_renew ? 1 : 0,
    });
    toast.success(t('memberships.assigned') || 'Assigned');
    closeAssignModal();
    await loadProfile();
  } catch (e) {
    console.error('Assign membership error:', e);
    toast.error(t('memberships.assignError') || t('common.errorLoading'));
  } finally {
    assigning.value = false;
  }
};

const openRenewModal = () => {
  showRenewModal.value = true;
  renewForm.value = { months: 1, amount_paid: 0 };
};

const closeRenewModal = () => {
  showRenewModal.value = false;
  renewing.value = false;
};

const renewMembership = async () => {
  if (!currentMembership.value?.id) return;
  renewing.value = true;
  try {
    await api.post(`/memberships/${currentMembership.value.id}/renew`, {
      months: Number(renewForm.value.months || 1),
      amount_paid: Number(renewForm.value.amount_paid || 0),
    });
    toast.success(t('memberships.renewed') || 'Renewed');
    closeRenewModal();
    await loadProfile();
  } catch (e) {
    console.error('Renew membership error:', e);
    toast.error(t('memberships.renewError') || t('common.errorLoading'));
  } finally {
    renewing.value = false;
  }
};

const cancelMembership = async () => {
  if (!currentMembership.value?.id) return;
  if (!confirm(t('memberships.cancelConfirm') || 'Cancel membership?')) return;
  try {
    await api.delete(`/memberships/${currentMembership.value.id}`);
    toast.success(t('memberships.cancelled') || 'Cancelled');
    await loadProfile();
  } catch (e) {
    toast.error(t('common.errorLoading'));
  }
};

const createTypedPass = async (type) => {
  if (!profile.value?.customer?.id) return;
  
  creatingPass.value = true;
  activePassType.value = type;
  try {
    const customerId = profile.value.customer.id;
    const response = await api.post(`/apple-wallet/create/${customerId}/${type}`);
    const data = response.data?.data || response.data || {};
    
    if (data.pass_url) {
      window.open(data.pass_url, '_blank');
      toast.success(t('loyalty.passCreated') || 'Pass created successfully');
    } else {
      toast.error(t('loyalty.passError') || 'Error creating pass');
    }
  } catch (error) {
    console.error('Error creating Apple Wallet pass:', error);
    toast.error(error.response?.data?.message || t('loyalty.passError') || 'Error creating pass');
  } finally {
    creatingPass.value = false;
    activePassType.value = '';
  }
};

onMounted(() => {
  loadProfile();
  loadPlans();
});
</script>

<style scoped>
.customer-profile-page{display:flex;flex-direction:column;gap:1.5rem;}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;}
.section-card{border:1px solid var(--border-color);box-shadow:0 2px 8px rgba(0,0,0,0.04);}

.btn-primary-custom{background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.9) 100%);border:none;box-shadow:0 4px 12px rgba(187,160,122,0.3);transition:all 0.3s;}
.btn-primary-custom:hover{background:linear-gradient(135deg, rgba(187,160,122,0.95) 0%, var(--asmaa-primary) 100%);box-shadow:0 6px 16px rgba(187,160,122,0.4);transform:translateY(-2px);}

.today-header{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;}
.today-meta{display:flex;gap:0.5rem;flex-wrap:wrap;}

.details-grid{display:flex;flex-direction:column;gap:0.75rem;}
.detail-row{display:flex;gap:1rem;padding:0.85rem 1rem;background:var(--bg-secondary);border-radius:10px;border:1px solid var(--border-color);}
.detail-label{min-width:160px;font-weight:800;color:var(--text-secondary);}
.detail-value{flex:1;color:var(--text-primary);}
.detail-link{color:var(--asmaa-primary);text-decoration:none;}
.detail-link:hover{text-decoration:underline;}

.membership-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.membership-actions{display:flex;flex-wrap:wrap;gap:0.5rem;}
.membership-badges{display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;}

.table-wrapper{overflow-x:auto;border-radius:12px;border:1px solid var(--border-color);background:var(--bg-primary);}
.table-header-row{background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%);border-bottom:2px solid var(--asmaa-primary);}
.table-header-row th{padding:1rem 1.25rem;font-weight:700;color:var(--text-primary);text-transform:uppercase;font-size:0.75rem;letter-spacing:0.5px;border-bottom:none;white-space:nowrap;}
.table-row td{padding:1rem 1.25rem;vertical-align:middle;border-bottom:1px solid var(--border-color);}

.unified-amount{color:var(--asmaa-primary);font-weight:800;}
.unified-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 0.75rem;border-radius:8px;font-size:0.8125rem;font-weight:600;background:linear-gradient(135deg, rgba(187,160,122,0.15) 0%, rgba(187,160,122,0.1) 100%);color:var(--asmaa-primary);border:1px solid rgba(187,160,122,0.3);}
.badge-icon{width:14px;height:14px;color:currentColor;}

.wallet-passes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
.wallet-pass-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}
.pass-info {
  display: flex;
  align-items: center;
  color: var(--text-primary);
}

.badge-product{background:linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);color:var(--asmaa-secondary);border-color:var(--asmaa-secondary-soft-border);}
.badge-service{background:linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);color:var(--asmaa-success);border-color:var(--asmaa-success-soft-border);}
.badge-earned{background:linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);color:var(--asmaa-success);border-color:var(--asmaa-success-soft-border);}
.badge-other{background:linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);color:var(--asmaa-secondary);border-color:var(--asmaa-secondary-soft-border);}

@media (max-width:768px){.stats-grid{grid-template-columns:1fr;}.today-header{flex-direction:column;align-items:flex-start;}}
</style>


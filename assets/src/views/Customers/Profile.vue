<template>
  <div class="customer-profile-page nano-banana-theme">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-4 shadow-sm border border-light">
      <div class="header-left d-flex align-items-center gap-3">
        <div class="avatar-circle-nano">
          {{ profile.customer?.name?.charAt(0) || 'C' }}
        </div>
        <div>
          <h2 class="mb-0 fw-bold text-navy">{{ profile.customer?.name || t('customers.title') }}</h2>
          <p class="text-muted small mb-0">{{ profile.customer?.phone || t('customers.profileSubtitle') }}</p>
        </div>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="secondary" variant="ghost" class="nano-btn-icon" @click="goBack">
          <CIcon icon="cil-arrow-left" />
        </CButton>
        <CButton color="primary" class="nano-btn" @click="refresh">
          <CIcon icon="cil-reload" class="me-2" :class="{ 'spinning': loading }" />
          {{ t('common.refresh') || 'تحديث' }}
        </CButton>
      </div>
    </div>

    <LoadingSpinner v-if="loading" :text="t('common.loading')" />

    <template v-else>
      <!-- Quick Stats Bar (Nano Banana Style) -->
      <div class="nano-stats-bar mb-4">
        <div class="stat-card-nano">
          <div class="stat-icon-bg points"><CIcon icon="cil-star" /></div>
          <div class="stat-info">
            <div class="stat-value text-warning">{{ profile.customer?.loyalty_points || 0 }}</div>
            <div class="stat-label">{{ t('customers.points') }}</div>
          </div>
        </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg revenue"><CIcon icon="cil-money" /></div>
          <div class="stat-info">
            <div class="stat-value text-success">{{ formatCurrencyShort(profile.customer?.total_spent || 0) }}</div>
            <div class="stat-label">{{ t('customers.spending') }}</div>
          </div>
        </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg purchases"><CIcon icon="cil-cart" /></div>
          <div class="stat-info">
            <div class="stat-value text-primary">{{ formatCurrencyShort(profile.today?.total_amount || 0) }}</div>
            <div class="stat-label">{{ t('customers.todaysPurchases') || 'مشتريات اليوم' }}</div>
          </div>
        </div>
        <div class="stat-card-nano">
          <div class="stat-icon-bg gift"><CIcon icon="cil-gift" /></div>
          <div class="stat-info">
            <div class="stat-value text-info">{{ profile.today?.points_earned || 0 }}</div>
            <div class="stat-label">{{ t('customers.todaysPoints') || 'نقاط اليوم' }}</div>
          </div>
        </div>
      </div>

      <CRow class="g-4">
        <!-- Customer Details -->
        <CCol lg="4">
          <Card :title="t('customers.customerDetails') || 'بيانات العميل'" icon="cil-user" class="h-100 nano-card-luxury">
            <div class="details-list-nano">
              <div class="detail-item-nano" v-if="profile.customer?.phone">
                <div class="icon-wrap-nano"><CIcon icon="cil-phone" /></div>
                <div class="content-wrap">
                  <span class="label">{{ t('customers.phone') }}</span>
                  <a :href="`tel:${profile.customer.phone}`" class="value link">{{ profile.customer.phone }}</a>
                </div>
              </div>
              <div class="detail-item-nano" v-if="profile.customer?.email">
                <div class="icon-wrap-nano"><CIcon icon="cil-envelope-open" /></div>
                <div class="content-wrap">
                  <span class="label">{{ t('customers.email') }}</span>
                  <a :href="`mailto:${profile.customer.email}`" class="value link">{{ profile.customer.email }}</a>
                </div>
              </div>
              <div class="detail-item-nano" v-if="profile.customer?.city">
                <div class="icon-wrap-nano"><CIcon icon="cil-location-pin" /></div>
                <div class="content-wrap">
                  <span class="label">{{ t('customers.city') }}</span>
                  <span class="value">{{ profile.customer.city }}</span>
                </div>
              </div>
              <div class="detail-item-nano" v-if="profile.customer?.gender">
                <div class="icon-wrap-nano"><CIcon icon="cil-people" /></div>
                <div class="content-wrap">
                  <span class="label">{{ t('customers.gender') }}</span>
                  <span class="value">{{ profile.customer.gender }}</span>
                </div>
              </div>
              <div class="detail-item-nano" v-if="profile.customer?.created_at">
                <div class="icon-wrap-nano"><CIcon icon="cil-calendar" /></div>
                <div class="content-wrap">
                  <span class="label">{{ t('common.createdAt') || 'تاريخ التسجيل' }}</span>
                  <span class="value">{{ formatDate(profile.customer.created_at) }}</span>
                </div>
              </div>
              <div class="detail-item-nano" v-if="profile.customer?.notes">
                <div class="icon-wrap-nano"><CIcon icon="cil-notes" /></div>
                <div class="content-wrap">
                  <span class="label">{{ t('customers.notes') }}</span>
                  <p class="value small mb-0">{{ profile.customer.notes }}</p>
                </div>
              </div>
            </div>
          </Card>
        </CCol>

        <!-- Memberships & Wallet -->
        <CCol lg="8">
          <div class="d-flex flex-column gap-4">
            <!-- Membership Card -->
            <Card :title="t('memberships.title') || 'العضويات'" icon="cil-credit-card" class="nano-card-luxury">
              <div class="membership-nano-content">
                <div class="membership-info-box d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-3">
                    <div class="membership-icon-large" :class="{ 'active': currentMembership }">
                      <CIcon icon="cil-id-card" />
                    </div>
                    <div>
                      <h4 class="mb-1 fw-bold">{{ currentMembership?.plan_name_ar || currentMembership?.plan_name || (t('memberships.noMembership') || 'لا توجد عضوية نشطة') }}</h4>
                      <div v-if="currentMembership" class="text-muted small">
                        <CIcon icon="cil-calendar" class="me-1" />
                        {{ currentMembership.start_date }} → {{ currentMembership.end_date }}
                        <span class="mx-2">|</span>
                        <CBadge color="success" shape="rounded-pill">{{ currentMembership.status }}</CBadge>
                      </div>
                    </div>
                  </div>
                  <div class="d-flex gap-2">
                    <CButton v-if="!currentMembership" color="primary" class="nano-btn" @click="openAssignModal">
                      <CIcon icon="cil-plus" class="me-2" />
                      {{ t('memberships.assign') || 'إسناد عضوية' }}
                    </CButton>
                    <template v-else>
                      <CButton color="warning" variant="ghost" class="nano-btn-icon" @click="openRenewModal">
                        <CIcon icon="cil-history" />
                      </CButton>
                      <CButton color="danger" variant="ghost" class="nano-btn-icon" @click="cancelMembership">
                        <CIcon icon="cil-trash" />
                      </CButton>
                    </template>
                  </div>
                </div>

                <div v-if="currentMembership" class="membership-stats-row mt-4">
                  <div class="m-stat">
                    <div class="m-label">{{ t('common.usage') || 'الاستخدام' }}</div>
                    <div class="m-value">
                      <span class="text-primary">{{ currentMembership.services_used || 0 }}</span>
                      <span class="mx-1 text-muted">/</span>
                      <span>{{ currentMembership.services_limit ?? '∞' }}</span>
                    </div>
                    <div class="m-progress mt-2">
                      <CProgress :value="calculateProgress(currentMembership.services_used, currentMembership.services_limit)" height="6" color="primary" />
                    </div>
                  </div>
                  <div class="m-stat">
                    <div class="m-label">{{ t('loyalty.multiplier') || 'مضاعف النقاط' }}</div>
                    <div class="m-value text-warning">x{{ currentMembership.points_multiplier || 1 }}</div>
                  </div>
                  <div class="m-stat">
                    <div class="m-label">{{ t('common.remaining') || 'المتبقي' }}</div>
                    <div class="m-value">{{ getRemainingDays(currentMembership.end_date) }} {{ t('common.days') || 'أيام' }}</div>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Wallet Passes -->
            <Card :title="t('loyalty.appleWallet') || 'بطاقات Apple Wallet'" icon="cil-wallet" class="nano-card-luxury">
              <div class="nano-wallet-grid">
                <div class="nano-wallet-item loyalty">
                  <div class="wallet-icon"><CIcon icon="cil-star" /></div>
                  <div class="wallet-text">
                    <div class="label">{{ t('loyalty.title') }}</div>
                    <div class="desc">{{ profile.customer?.loyalty_points || 0 }} {{ t('customers.points') }}</div>
                  </div>
                  <CButton color="light" size="sm" class="rounded-pill px-3" @click="createTypedPass('loyalty')" :disabled="creatingPass">
                    <CSpinner v-if="creatingPass && activePassType === 'loyalty'" size="sm" />
                    <CIcon v-else icon="cil-plus" />
                  </CButton>
                </div>
                <div class="nano-wallet-item membership" :class="{ 'disabled': !currentMembership }">
                  <div class="wallet-icon"><CIcon icon="cil-credit-card" /></div>
                  <div class="wallet-text">
                    <div class="label">{{ t('memberships.title') }}</div>
                    <div class="desc">{{ currentMembership?.plan_name_ar || t('memberships.noMembership') }}</div>
                  </div>
                  <CButton color="light" size="sm" class="rounded-pill px-3" @click="createTypedPass('membership')" :disabled="creatingPass || !currentMembership">
                    <CSpinner v-if="creatingPass && activePassType === 'membership'" size="sm" />
                    <CIcon v-else icon="cil-plus" />
                  </CButton>
                </div>
              </div>
            </Card>
          </div>
        </CCol>
      </CRow>

      <!-- Activities Tables -->
      <div class="nano-tabs-container mt-4">
        <div class="d-flex gap-3 mb-4 tabs-header">
          <div class="tab-banana" :class="{ 'active': activeTab === 'purchases' }" @click="activeTab = 'purchases'">
            <CIcon icon="cil-cart" class="me-2" />
            {{ t('customers.todayDetails') || 'مشتريات اليوم' }}
          </div>
          <div class="tab-banana" :class="{ 'active': activeTab === 'loyalty' }" @click="activeTab = 'loyalty'">
            <CIcon icon="cil-star" class="me-2" />
            {{ t('customers.todayLoyalty') || 'نقاط الولاء' }}
          </div>
        </div>

        <div class="nano-panel">
          <div v-if="activeTab === 'purchases'">
            <div v-if="(profile.today?.items || []).length === 0" class="text-center p-5">
              <EmptyState :title="t('customers.noPurchasesToday')" icon="cil-cart" />
            </div>
            <div v-else class="table-responsive nano-table-wrap">
              <CTable align="middle" hover class="nano-table">
                <thead>
                  <tr>
                    <th>{{ t('common.date') }}</th>
                    <th>{{ t('customers.item') || 'البند' }}</th>
                    <th>{{ t('customers.itemType') || 'النوع' }}</th>
                    <th class="text-center">{{ t('customers.quantity') || 'الكمية' }}</th>
                    <th class="text-end">{{ t('customers.amount') || 'القيمة' }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="it in profile.today.items" :key="it.id">
                    <td class="small">{{ formatDate(it.created_at) }}</td>
                    <td><strong>{{ it.item_name }}</strong></td>
                    <td>
                      <CBadge :color="it.item_type === 'product' ? 'info' : 'success'" shape="rounded-pill" class="px-2 py-1">
                        <CIcon :icon="it.item_type === 'product' ? 'cil-basket' : 'cil-spreadsheet'" class="me-1" />
                        {{ it.item_type }}
                      </CBadge>
                    </td>
                    <td class="text-center">{{ it.quantity || 1 }}</td>
                    <td class="text-end fw-bold text-navy">{{ formatCurrency(it.total || 0) }}</td>
                  </tr>
                </tbody>
              </CTable>
            </div>
          </div>

          <div v-else>
            <div v-if="(profile.loyalty?.today_transactions || []).length === 0" class="text-center p-5">
              <EmptyState :title="t('customers.noLoyaltyToday')" icon="cil-star" />
            </div>
            <div v-else class="table-responsive nano-table-wrap">
              <CTable align="middle" hover class="nano-table">
                <thead>
                  <tr>
                    <th>{{ t('common.date') }}</th>
                    <th>{{ t('loyalty.type') || 'النوع' }}</th>
                    <th>{{ t('loyalty.points') }}</th>
                    <th>{{ t('loyalty.description') || 'الوصف' }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="tx in profile.loyalty.today_transactions" :key="tx.id">
                    <td class="small">{{ formatDate(tx.created_at) }}</td>
                    <td>
                      <CBadge :color="tx.type === 'earned' ? 'success' : 'warning'" shape="rounded-pill">
                        {{ tx.type }}
                      </CBadge>
                    </td>
                    <td class="fw-bold" :class="tx.type === 'earned' ? 'text-success' : 'text-warning'">
                      {{ tx.type === 'earned' ? '+' : '-' }}{{ tx.points }}
                    </td>
                    <td class="small text-muted">{{ tx.description || '—' }}</td>
                  </tr>
                </tbody>
              </CTable>
            </div>
          </div>
        </div>
      </div>

      <!-- Modals (Simplified Design) -->
      <CModal :visible="showAssignModal" @close="closeAssignModal" alignment="center">
        <CModalHeader class="border-0 pb-0">
          <CModalTitle class="fw-bold">{{ t('memberships.assign') }}</CModalTitle>
        </CModalHeader>
        <CModalBody class="p-4">
          <div class="d-flex flex-column gap-3">
            <div>
              <label class="form-label small fw-bold">{{ t('memberships.plan') }}</label>
              <CFormSelect v-model="assignForm.membership_plan_id" class="rounded-3 p-2">
                <option value="">{{ t('common.select') }}</option>
                <option v-for="p in plans" :key="p.id" :value="String(p.id)">{{ p.name_ar || p.name }}</option>
              </CFormSelect>
            </div>
            <div class="row">
              <div class="col-6">
                <label class="form-label small fw-bold">{{ t('memberships.durationMonths') }}</label>
                <CFormInput v-model.number="assignForm.duration_months" type="number" class="rounded-3" />
              </div>
              <div class="col-6 d-flex align-items-end p-2">
                <CFormCheck v-model="assignForm.auto_renew" :label="t('memberships.autoRenew')" class="small" />
              </div>
            </div>
          </div>
        </CModalBody>
        <CModalFooter class="border-0 pt-0">
          <CButton color="light" class="rounded-pill px-4" @click="closeAssignModal">{{ t('common.cancel') }}</CButton>
          <CButton color="primary" class="nano-btn px-4" :disabled="assigning" @click="assignMembership">
            {{ assigning ? t('common.saving') : t('memberships.assign') }}
          </CButton>
        </CModalFooter>
      </CModal>

      <CModal :visible="showRenewModal" @close="closeRenewModal" alignment="center">
        <CModalHeader class="border-0 pb-0">
          <CModalTitle class="fw-bold">{{ t('memberships.renew') }}</CModalTitle>
        </CModalHeader>
        <CModalBody class="p-4">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label small fw-bold">{{ t('memberships.monthsToExtend') }}</label>
              <CFormInput v-model.number="renewForm.months" type="number" class="rounded-3" />
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold">{{ t('memberships.amountPaid') }}</label>
              <CFormInput v-model.number="renewForm.amount_paid" type="number" step="0.001" class="rounded-3" />
            </div>
          </div>
        </CModalBody>
        <CModalFooter class="border-0 pt-0">
          <CButton color="light" class="rounded-pill px-4" @click="closeRenewModal">{{ t('common.cancel') }}</CButton>
          <CButton color="primary" class="nano-btn px-4" :disabled="renewing" @click="renewMembership">
            {{ renewing ? t('common.saving') : t('memberships.renew') }}
          </CButton>
        </CModalFooter>
      </CModal>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  CButton, CTable, CBadge, CModal, CModalHeader, CModalTitle, 
  CModalBody, CModalFooter, CFormSelect, CFormInput, CFormCheck, 
  CRow, CCol, CSpinner, CProgress 
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import PageHeader from '@/components/UI/PageHeader.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const route = useRoute();
const router = useRouter();

// State
const loading = ref(false);
const activeTab = ref('purchases');
const profile = ref({ 
  customer: null, 
  today: { orders: [], items: [], total_amount: 0, points_earned: 0 }, 
  membership: { current: null, history: [] }, 
  loyalty: { today_transactions: [] } 
});

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

// Helpers
const formatCurrency = (amount) =>
  new Intl.NumberFormat('en-KW', { 
    style: 'currency', 
    currency: 'KWD', 
    minimumFractionDigits: 3 
  }).format(amount || 0);

const formatCurrencyShort = (amount) => {
  const value = Number(amount || 0);
  if (value >= 1000) return `${(value / 1000).toFixed(1)}K د.ك`;
  return `${value.toFixed(3)} د.ك`;
};

const formatDate = (d) => {
  if (!d) return '—';
  try {
    return new Date(d).toLocaleDateString('ar-KW', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return String(d);
  }
};

const calculateProgress = (used, limit) => {
  if (!limit || limit === '∞') return 0;
  return Math.min(Math.round((used / limit) * 100), 100);
};

const getRemainingDays = (endDate) => {
  if (!endDate) return 0;
  const today = new Date();
  const end = new Date(endDate);
  const diffTime = end - today;
  return Math.max(Math.ceil(diffTime / (1000 * 60 * 60 * 24)), 0);
};

// Actions
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

const loadPlans = async () => {
  try {
    const res = await api.get('/memberships/plans', { noCache: true });
    plans.value = res.data?.data || [];
  } catch {
    plans.value = [];
  }
};

const refresh = () => loadProfile();
const goBack = () => router.push('/customers');

const openAssignModal = async () => {
  showAssignModal.value = true;
  assignForm.value = { membership_plan_id: '', duration_months: 1, auto_renew: false };
  if (plans.value.length === 0) await loadPlans();
};

const closeAssignModal = () => {
  showAssignModal.value = false;
  assigning.value = false;
};

const assignMembership = async () => {
  if (!profile.value?.customer?.id || !assignForm.value.membership_plan_id) {
    toast.error(t('memberships.selectPlan') || 'اختر الخطة');
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
    toast.success(t('memberships.assigned') || 'تم التعيين');
    closeAssignModal();
    await loadProfile();
  } catch (e) {
    toast.error(t('common.errorLoading'));
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
    toast.success(t('memberships.renewed') || 'تم التجديد');
    closeRenewModal();
    await loadProfile();
  } catch (e) {
    toast.error(t('common.errorLoading'));
  } finally {
    renewing.value = false;
  }
};

const cancelMembership = async () => {
  if (!currentMembership.value?.id) return;
  if (!confirm(t('memberships.cancelConfirm') || 'هل أنت متأكد من إلغاء العضوية؟')) return;
  try {
    await api.delete(`/memberships/${currentMembership.value.id}`);
    toast.success(t('memberships.cancelled') || 'تم الإلغاء');
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
      toast.success(t('loyalty.passCreated') || 'تم إنشاء البطاقة بنجاح');
    }
  } catch (error) {
    toast.error(error.response?.data?.message || t('loyalty.passError'));
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
.customer-profile-page {
  font-family: 'Cairo', sans-serif;
  padding: var(--spacing-lg);
  background: var(--color-gray-50);
  min-height: 100vh;
}

/* Nano Header */
.avatar-circle-nano {
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 800;
  box-shadow: var(--shadow-md);
}

.text-navy { color: var(--color-navy); }

.nano-btn {
  border-radius: var(--radius-pill);
  padding: 0.6rem 1.25rem;
  font-weight: 700;
  transition: var(--transition-smooth);
  box-shadow: var(--shadow-sm);
}
.nano-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.nano-btn-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: var(--color-white);
  border: 1px solid var(--color-gray-100);
}

/* Stats Bar */
.nano-stats-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.stat-card-nano {
  background: var(--color-white);
  border-radius: 24px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: var(--shadow-luxury);
  border: 1px solid var(--color-gray-100);
  transition: var(--transition-smooth);
}
.stat-card-nano:hover {
  transform: translateY(-5px);
}

.stat-icon-bg {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}
.stat-icon-bg.points { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-icon-bg.revenue { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.purchases { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); }
.stat-icon-bg.gift { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }

.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.8rem; color: var(--color-gray-500); font-weight: 600; margin-top: 4px; }

/* Nano Cards */
.nano-card-luxury {
  border: 1px solid var(--color-gray-100);
  box-shadow: var(--shadow-luxury);
  border-radius: 24px !important;
}

.details-list-nano {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-item-nano {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: var(--color-gray-50);
  border-radius: 16px;
  transition: var(--transition-base);
}

.icon-wrap-nano {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--color-white);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}

.content-wrap .label {
  display: block;
  font-size: 0.7rem;
  color: var(--color-gray-500);
  font-weight: 700;
  text-transform: uppercase;
}
.content-wrap .value {
  font-weight: 700;
  color: var(--color-navy);
  font-size: 0.95rem;
}
.content-wrap .value.link {
  color: var(--color-primary);
  text-decoration: none;
}

/* Membership Styles */
.membership-info-box {
  background: var(--color-gray-50);
  padding: 1.5rem;
  border-radius: 20px;
  border: 1px solid var(--color-gray-100);
}

.membership-icon-large {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  background: var(--color-gray-200);
  color: var(--color-gray-400);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}
.membership-icon-large.active {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  color: white;
}

.membership-stats-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1.5rem;
}
.m-stat .m-label { font-size: 0.75rem; font-weight: 700; color: var(--color-gray-500); margin-bottom: 0.5rem; }
.m-stat .m-value { font-size: 1.1rem; font-weight: 800; color: var(--color-navy); }

/* Wallet Grid */
.nano-wallet-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.nano-wallet-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 20px;
  color: white;
  transition: var(--transition-smooth);
}
.nano-wallet-item.loyalty { background: linear-gradient(135deg, #4b1a1a 0%, #2a0f0f 100%); }
.nano-wallet-item.membership { background: linear-gradient(135deg, #1a2a4b 0%, #0f162a 100%); }
.nano-wallet-item.disabled { opacity: 0.5; filter: grayscale(1); cursor: not-allowed; }

.wallet-icon { font-size: 1.5rem; opacity: 0.8; }
.wallet-text .label { font-size: 0.7rem; opacity: 0.7; font-weight: 700; text-transform: uppercase; }
.wallet-text .desc { font-weight: 700; font-size: 0.9rem; }

/* Tabs Banana Style */
.tab-banana {
  padding: 0.75rem 1.5rem;
  border-radius: 14px;
  background: var(--color-white);
  color: var(--color-gray-500);
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition-smooth);
  border: 1px solid var(--color-gray-100);
  display: flex;
  align-items: center;
}
.tab-banana.active {
  background: var(--color-primary);
  color: white;
  box-shadow: var(--shadow-md);
  border-color: var(--color-primary);
}

.nano-panel {
  background: var(--color-white);
  border-radius: 24px;
  padding: 1.5rem;
  box-shadow: var(--shadow-luxury);
}

.nano-table-wrap {
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--color-gray-100);
}

.nano-table th {
  background: var(--color-gray-50);
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--color-gray-500);
  padding: 1rem;
  border-bottom: 2px solid var(--color-gray-100);
}
.nano-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--color-gray-100);
}

.spinning { animation: spin 1s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

@media (max-width: 992px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
  .nano-wallet-grid { grid-template-columns: 1fr; }
}

@media (max-width: 576px) {
  .nano-stats-bar { grid-template-columns: 1fr; }
  .membership-stats-row { grid-template-columns: 1fr; }
}
</style>

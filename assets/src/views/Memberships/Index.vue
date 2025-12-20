<template>
  <div class="memberships-page">
    <PageHeader :title="t('memberships.title')" :subtitle="t('memberships.subtitle') || (t('memberships.title') + ' - ' + t('dashboard.subtitle'))">
      <template #icon>
        <CIcon icon="cil-credit-card" />
      </template>
      <template #actions>
        <CButton color="secondary" variant="outline" class="me-2 settings-btn" @click="goToSettings">
          <CIcon icon="cil-settings" class="me-2" />
          {{ t('nav.programsSettings') }}
        </CButton>
        <CButton color="secondary" variant="outline" class="me-2" @click="openAssignModal">
          <CIcon icon="cil-user-follow" class="me-2" />
          {{ t('memberships.assign') || 'Assign Membership' }}
        </CButton>
        <CButton color="primary" class="btn-primary-custom" @click="openPlanModal()">
          <CIcon icon="cil-plus" class="me-2" />
          {{ t('memberships.newPlan') || 'New Plan' }}
        </CButton>
      </template>
    </PageHeader>

    <div class="stats-grid">
      <StatCard :label="t('memberships.plans') || 'Plans'" :value="plansStats.total" badge-variant="info" color="gold" :clickable="true" @click="() => (activeTab = 'plans')">
        <template #icon><CIcon icon="cil-layers" /></template>
      </StatCard>
      <StatCard :label="t('memberships.activePlans') || 'Active Plans'" :value="plansStats.active" badge-variant="success" color="gold" :clickable="true" @click="() => (activeTab = 'plans')">
        <template #icon><CIcon icon="cil-check-circle" /></template>
      </StatCard>
      <StatCard :label="t('memberships.members') || 'Members'" :value="membersStats.total" badge-variant="info" color="gold" :clickable="true" @click="() => (activeTab = 'members')">
        <template #icon><CIcon icon="cil-people" /></template>
      </StatCard>
      <StatCard :label="t('memberships.active')" :value="membersStats.active" badge-variant="success" color="gold" :clickable="true" @click="() => { activeTab = 'members'; memberFilters.status = 'active'; memberPagination.current_page = 1; loadMembers(); }">
        <template #icon><CIcon icon="cil-user" /></template>
      </StatCard>
    </div>

    <!-- Tabs -->
    <CCard class="tabs-card">
      <CCardHeader>
        <CNav variant="tabs">
          <CNavItem :active="activeTab === 'plans'" @click="activeTab = 'plans'">{{ t('memberships.plansTab') || 'Plans' }}</CNavItem>
          <CNavItem :active="activeTab === 'members'" @click="activeTab = 'members'">{{ t('memberships.membersTab') || 'Members' }}</CNavItem>
        </CNav>
      </CCardHeader>
      <CCardBody>
        <!-- Plans Tab -->
        <div v-if="activeTab === 'plans'">
          <LoadingSpinner v-if="loadingPlans" :text="t('common.loading')" />

          <EmptyState
            v-else-if="plans.length === 0"
            :title="t('common.noData')"
            :description="t('memberships.noPlans') || t('common.noData')"
            icon-color="gray"
          >
            <template #action>
              <CButton color="primary" class="btn-primary-custom" @click="openPlanModal()">
                <CIcon icon="cil-plus" class="me-2" />
                {{ t('memberships.newPlan') || 'New Plan' }}
              </CButton>
            </template>
          </EmptyState>

          <div v-else class="table-wrapper">
            <CTable hover responsive class="table-modern memberships-table">
              <thead>
                <tr class="table-header-row">
                  <th class="th-name">{{ t('memberships.plan') }}</th>
                  <th class="th-price">{{ t('memberships.price') || 'Price' }}</th>
                  <th class="th-duration">{{ t('memberships.durationMonths') || 'Duration' }}</th>
                  <th class="th-discount">{{ t('memberships.discount') || 'Discount' }}</th>
                  <th class="th-free">{{ t('memberships.freeServices') || 'Free Services' }}</th>
                  <th class="th-mult">{{ t('memberships.pointsMultiplier') || 'Points Multiplier' }}</th>
                  <th class="th-status">{{ t('common.status') }}</th>
                  <th class="th-actions">{{ t('common.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="plan in plans" :key="plan.id" class="table-row plan-row">
                  <td class="td-name">
                    <div class="name-cell">
                      <strong class="plan-name">{{ plan.name_ar || plan.name }}</strong>
                      <small class="text-muted" v-if="plan.description">{{ plan.description }}</small>
                    </div>
                  </td>
                  <td class="td-price"><strong class="unified-amount">{{ formatCurrency(plan.price || 0) }}</strong></td>
                  <td class="td-duration">
                    <CBadge class="unified-badge badge-info">
                      <CIcon icon="cil-calendar" class="badge-icon" />
                      <span>{{ plan.duration_months || 1 }} {{ t('memberships.months') || 'months' }}</span>
                    </CBadge>
                  </td>
                  <td class="td-discount">
                    <CBadge class="unified-badge badge-rate">
                      <CIcon icon="cil-percent" class="badge-icon" />
                      <span>{{ plan.discount_percentage || 0 }}%</span>
                    </CBadge>
                  </td>
                  <td class="td-free">
                    <CBadge class="unified-badge badge-bonus">
                      <CIcon icon="cil-spreadsheet" class="badge-icon" />
                      <span>{{ plan.free_services_count || 0 }}</span>
                    </CBadge>
                  </td>
                  <td class="td-mult">
                    <CBadge class="unified-badge badge-info">
                      <CIcon icon="cil-star" class="badge-icon" />
                      <span>x{{ plan.points_multiplier || 1 }}</span>
                    </CBadge>
                  </td>
                  <td class="td-status">
                    <CBadge class="unified-badge" :class="plan.is_active ? 'status-active' : 'status-inactive'">
                      <CIcon :icon="plan.is_active ? 'cil-check-circle' : 'cil-x-circle'" class="badge-icon" />
                      <span>{{ plan.is_active ? t('common.active') : t('common.inactive') }}</span>
                    </CBadge>
                  </td>
                  <td class="td-actions">
                    <div class="actions-group">
                      <button class="action-btn" type="button" @click="openPlanModal(plan)" :title="t('common.edit')"><CIcon icon="cil-pencil" /></button>
                      <button class="action-btn" type="button" @click="deletePlan(plan)" :title="t('common.delete')"><CIcon icon="cil-trash" /></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </CTable>
          </div>
        </div>

        <!-- Members Tab -->
        <div v-else>
          <Card :title="t('common.filter')" icon="cil-filter" class="filters-card">
            <CRow class="g-3">
              <CCol :md="3">
                <CFormSelect v-model="memberFilters.status" @change="loadMembers" class="filter-select">
                  <option value="">{{ t('common.status') }}</option>
                  <option value="active">{{ t('memberships.active') }}</option>
                  <option value="expired">{{ t('memberships.expired') }}</option>
                  <option value="cancelled">{{ t('memberships.cancelled') || 'Cancelled' }}</option>
                </CFormSelect>
              </CCol>
              <CCol :md="9">
                <CInputGroup class="search-input-group">
                  <CInputGroupText class="search-icon-wrapper"><CIcon icon="cil-magnifying-glass" /></CInputGroupText>
                  <CFormInput v-model="memberFilters.search" :placeholder="t('memberships.searchCustomer') || 'Search customer...'
                    " @input="debounceSearch" class="filter-input search-input" />
                </CInputGroup>
              </CCol>
              <CCol :md="3">
                <CButton color="secondary" variant="outline" @click="resetMemberFilters" class="w-100 reset-btn">
                  <CIcon icon="cil-reload" class="me-1" />
                  {{ t('common.reset') }}
                </CButton>
              </CCol>
            </CRow>
          </Card>

          <LoadingSpinner v-if="loadingMembers" :text="t('common.loading')" />

          <EmptyState
            v-else-if="members.length === 0"
            :title="t('common.noData')"
            :description="t('memberships.noMembers') || t('common.noData')"
            icon-color="gray"
          >
            <template #action>
              <CButton color="primary" class="btn-primary-custom" @click="openAssignModal">
                <CIcon icon="cil-user-follow" class="me-2" />
                {{ t('memberships.assign') || 'Assign Membership' }}
              </CButton>
            </template>
          </EmptyState>

          <div v-else class="table-wrapper">
            <CTable hover responsive class="table-modern memberships-table">
              <thead>
                <tr class="table-header-row">
                  <th class="th-customer">{{ t('memberships.customer') }}</th>
                  <th class="th-plan">{{ t('memberships.plan') }}</th>
                  <th class="th-dates">{{ t('memberships.startDate') }} / {{ t('memberships.endDate') }}</th>
                  <th class="th-usage">{{ t('memberships.servicesUsed') || 'Services Used' }}</th>
                  <th class="th-status">{{ t('common.status') }}</th>
                  <th class="th-actions">{{ t('common.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="m in members" :key="m.id" class="table-row member-row">
                  <td class="td-customer">
                    <div class="customer-cell">
                      <strong class="customer-name">{{ m.customer_name || '—' }}</strong>
                      <a v-if="m.customer_phone" class="customer-phone" :href="`tel:${m.customer_phone}`">{{ m.customer_phone }}</a>
                    </div>
                  </td>
                  <td class="td-plan">
                    <strong>{{ m.plan_name_ar || m.plan_name || '—' }}</strong>
                  </td>
                  <td class="td-dates">
                    <div class="date-stack">
                      <span>{{ formatDate(m.start_date) }}</span>
                      <span class="text-muted">{{ formatDate(m.end_date) }}</span>
                    </div>
                  </td>
                  <td class="td-usage">
                    <CBadge class="unified-badge badge-info">
                      <CIcon icon="cil-spreadsheet" class="badge-icon" />
                      <span>{{ m.services_used || 0 }} / {{ m.services_limit ?? '∞' }}</span>
                    </CBadge>
                  </td>
                  <td class="td-status">
                    <CBadge class="unified-badge" :class="membershipStatusClass(m.status)">
                      <CIcon :icon="membershipStatusIcon(m.status)" class="badge-icon" />
                      <span>{{ membershipStatusText(m.status) }}</span>
                    </CBadge>
                  </td>
                  <td class="td-actions">
                    <div class="actions-group">
                      <button class="action-btn" type="button" @click="openRenewModal(m)" :title="t('memberships.renew') || 'Renew'"><CIcon icon="cil-clock" /></button>
                      <button class="action-btn" type="button" @click="cancelMembership(m)" :title="t('memberships.cancel') || 'Cancel'"><CIcon icon="cil-ban" /></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </CTable>
          </div>

          <div v-if="memberPagination.total_pages > 1" class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
              {{ t('common.view') }} {{ (memberPagination.current_page - 1) * memberPagination.per_page + 1 }}
              {{ t('common.to') }}
              {{ Math.min(memberPagination.current_page * memberPagination.per_page, memberPagination.total) }}
              {{ t('common.of') }}
              {{ memberPagination.total }}
            </div>
            <CPagination :pages="memberPagination.total_pages" :active-page="memberPagination.current_page" @update:active-page="changeMemberPage" />
          </div>
        </div>
      </CCardBody>
    </CCard>

    <!-- Plan Modal -->
    <CModal :visible="showPlanModal" @close="closePlanModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon :icon="editingPlan ? 'cil-pencil' : 'cil-plus'" class="me-2" />
          {{ editingPlan ? (t('memberships.editPlan') || 'Edit Plan') : (t('memberships.newPlan') || 'New Plan') }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6"><CFormInput v-model="planForm.name" :label="t('memberships.planName') || 'Name'" class="filter-input" /></CCol>
          <CCol :md="6"><CFormInput v-model="planForm.name_ar" :label="t('memberships.planNameAr') || 'Arabic Name'" class="filter-input" /></CCol>
          <CCol :md="12"><CFormInput v-model="planForm.description" :label="t('memberships.description') || 'Description'" class="filter-input" /></CCol>
          <CCol :md="4"><CFormInput v-model.number="planForm.price" type="number" min="0" step="0.001" :label="t('memberships.price') || 'Price'" class="filter-input" /></CCol>
          <CCol :md="4"><CFormInput v-model.number="planForm.duration_months" type="number" min="1" step="1" :label="t('memberships.durationMonths') || 'Duration (months)'" class="filter-input" /></CCol>
          <CCol :md="4"><CFormInput v-model.number="planForm.discount_percentage" type="number" min="0" step="0.01" :label="t('memberships.discount') || 'Discount (%)'" class="filter-input" /></CCol>
          <CCol :md="4"><CFormInput v-model.number="planForm.free_services_count" type="number" min="0" step="1" :label="t('memberships.freeServices') || 'Free Services'" class="filter-input" /></CCol>
          <CCol :md="4"><CFormInput v-model.number="planForm.points_multiplier" type="number" min="0" step="0.01" :label="t('memberships.pointsMultiplier') || 'Points Multiplier'" class="filter-input" /></CCol>
          <CCol :md="4" class="d-flex align-items-center">
            <CFormCheck v-model="planForm.priority_booking" :label="t('memberships.priorityBooking') || 'Priority booking'" />
          </CCol>
          <CCol :md="4" class="d-flex align-items-center">
            <CFormCheck v-model="planForm.is_active" :label="t('common.active')" />
          </CCol>
        </CRow>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closePlanModal">{{ t('common.cancel') }}</CButton>
        <CButton color="primary" class="btn-primary-custom" :disabled="savingPlan" @click="savePlan">
          <CIcon icon="cil-save" class="me-2" />
          {{ savingPlan ? t('common.saving') : t('common.save') }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Assign Modal -->
    <CModal :visible="showAssignModal" @close="closeAssignModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-user-follow" class="me-2" />
          {{ t('memberships.assign') || 'Assign Membership' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6">
            <CFormSelect v-model="assignForm.customer_id" :label="t('memberships.customer')" class="filter-select">
              <option value="">{{ t('common.select') }}</option>
              <option v-for="c in customerOptions" :key="c.id" :value="String(c.id)">{{ c.name }} ({{ c.phone }})</option>
            </CFormSelect>
          </CCol>
          <CCol :md="6">
            <CFormSelect v-model="assignForm.membership_plan_id" :label="t('memberships.plan')" class="filter-select">
              <option value="">{{ t('common.select') }}</option>
              <option v-for="p in plans" :key="p.id" :value="String(p.id)">{{ p.name_ar || p.name }}</option>
            </CFormSelect>
          </CCol>
          <CCol :md="6">
            <CFormInput v-model.number="assignForm.duration_months" type="number" min="1" step="1" :label="t('memberships.durationMonths') || 'Duration (months)'" class="filter-input" />
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

    <!-- Renew Modal -->
    <CModal :visible="showRenewModal" @close="closeRenewModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon icon="cil-clock" class="me-2" />
          {{ t('memberships.renew') || 'Renew Membership' }}
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CRow class="g-3">
          <CCol :md="6">
            <CFormInput v-model.number="renewForm.months" type="number" min="1" step="1" :label="t('memberships.monthsToExtend') || 'Months'" class="filter-input" />
          </CCol>
          <CCol :md="6">
            <CFormInput v-model.number="renewForm.amount_paid" type="number" min="0" step="0.001" :label="t('memberships.amountPaid') || 'Amount paid'" class="filter-input" />
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import {
  CButton,
  CCard,
  CCardHeader,
  CCardBody,
  CTable,
  CBadge,
  CPagination,
  CFormSelect,
  CFormInput,
  CFormCheck,
  CInputGroup,
  CInputGroupText,
  CNav,
  CNavItem,
  CRow,
  CCol,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import { useRouter } from 'vue-router';
import PageHeader from '@/components/UI/PageHeader.vue';
import StatCard from '@/components/UI/StatCard.vue';
import Card from '@/components/UI/Card.vue';
import EmptyState from '@/components/UI/EmptyState.vue';
import LoadingSpinner from '@/components/UI/LoadingSpinner.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();

const activeTab = ref('plans');

const plans = ref([]);
const members = ref([]);
const customers = ref([]);

const loadingPlans = ref(false);
const loadingMembers = ref(false);

const showPlanModal = ref(false);
const editingPlan = ref(null);
const savingPlan = ref(false);

const showAssignModal = ref(false);
const assigning = ref(false);

const showRenewModal = ref(false);
const renewing = ref(false);
const renewingMember = ref(null);

const memberFilters = ref({
  status: '',
  search: '',
});

const memberPagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
});

const planForm = ref({
  name: '',
  name_ar: '',
  description: '',
  price: 0,
  duration_months: 1,
  discount_percentage: 0,
  free_services_count: 0,
  priority_booking: false,
  points_multiplier: 1,
  is_active: true,
});

const assignForm = ref({
  customer_id: '',
  membership_plan_id: '',
  duration_months: 1,
  auto_renew: false,
});

const renewForm = ref({
  months: 1,
  amount_paid: 0,
});

const formatCurrency = (amount) =>
  new Intl.NumberFormat('en-KW', { style: 'currency', currency: 'KWD', minimumFractionDigits: 3 }).format(amount || 0);

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const plansStats = computed(() => ({
  total: plans.value.length,
  active: plans.value.filter((p) => Number(p.is_active) === 1).length,
}));

const membersStats = computed(() => ({
  total: members.value.length,
  active: members.value.filter((m) => String(m.status) === 'active').length,
}));

const customerOptions = computed(() => customers.value || []);

const membershipStatusText = (status) => {
  const st = String(status || '');
  if (st === 'active') return t('memberships.active');
  if (st === 'expired') return t('memberships.expired');
  if (st === 'cancelled') return t('memberships.cancelled') || 'Cancelled';
  return st || '—';
};

const membershipStatusIcon = (status) => {
  const st = String(status || '');
  if (st === 'active') return 'cil-check-circle';
  if (st === 'expired') return 'cil-x-circle';
  if (st === 'cancelled') return 'cil-ban';
  return 'cil-info';
};

const membershipStatusClass = (status) => {
  const st = String(status || '');
  if (st === 'active') return 'status-active';
  if (st === 'expired') return 'status-expired';
  if (st === 'cancelled') return 'status-inactive';
  return 'status-other';
};

const loadPlans = async () => {
  loadingPlans.value = true;
  try {
    const res = await api.get('/memberships/plans', { noCache: true });
    plans.value = res.data?.data || [];
  } catch (e) {
    plans.value = [];
    toast.error(t('common.errorLoading'));
  } finally {
    loadingPlans.value = false;
  }
};

const loadMembers = async () => {
  loadingMembers.value = true;
  try {
    const params = {
      page: memberPagination.value.current_page,
      per_page: memberPagination.value.per_page,
      ...memberFilters.value,
    };
    Object.keys(params).forEach((k) => {
      if (params[k] === '') delete params[k];
    });

    const res = await api.get('/memberships/members', { params, noCache: true });
    const data = res.data?.data || res.data || {};
    members.value = data.items || [];
    memberPagination.value = data.pagination || memberPagination.value;
  } catch (e) {
    members.value = [];
    toast.error(t('common.errorLoading'));
  } finally {
    loadingMembers.value = false;
  }
};

const loadCustomers = async () => {
  try {
    const res = await api.get('/customers', { params: { per_page: 200 }, noCache: true });
    const data = res.data?.data || res.data || {};
    customers.value = data.items || data || [];
  } catch {
    customers.value = [];
  }
};

const changeMemberPage = (page) => {
  memberPagination.value.current_page = page;
  loadMembers();
};

let searchTimeout;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    memberPagination.value.current_page = 1;
    loadMembers();
  }, 500);
};

const resetMemberFilters = () => {
  memberFilters.value = { status: '', search: '' };
  memberPagination.value.current_page = 1;
  loadMembers();
};

const openPlanModal = (plan = null) => {
  editingPlan.value = plan;
  planForm.value = {
    name: plan?.name || '',
    name_ar: plan?.name_ar || '',
    description: plan?.description || '',
    price: Number(plan?.price || 0),
    duration_months: Number(plan?.duration_months || 1),
    discount_percentage: Number(plan?.discount_percentage || 0),
    free_services_count: Number(plan?.free_services_count || 0),
    priority_booking: !!Number(plan?.priority_booking || 0),
    points_multiplier: Number(plan?.points_multiplier || 1),
    is_active: plan ? !!Number(plan?.is_active || 0) : true,
  };
  showPlanModal.value = true;
};

const closePlanModal = () => {
  showPlanModal.value = false;
  savingPlan.value = false;
  editingPlan.value = null;
};

const savePlan = async () => {
  if (!planForm.value.name) {
    toast.error(t('memberships.planNameRequired') || 'Name is required');
    return;
  }

  savingPlan.value = true;
  try {
    const payload = {
      ...planForm.value,
      price: Number(planForm.value.price || 0),
      duration_months: Number(planForm.value.duration_months || 1),
      discount_percentage: Number(planForm.value.discount_percentage || 0),
      free_services_count: Number(planForm.value.free_services_count || 0),
      points_multiplier: Number(planForm.value.points_multiplier || 1),
      priority_booking: planForm.value.priority_booking ? 1 : 0,
      is_active: planForm.value.is_active ? 1 : 0,
    };

    if (editingPlan.value?.id) {
      await api.put(`/memberships/plans/${editingPlan.value.id}`, payload);
    } else {
      await api.post('/memberships/plans', payload);
    }

    toast.success(t('memberships.saved') || t('common.save'));
    closePlanModal();
    await loadPlans();
  } catch (e) {
    console.error('Save plan error:', e);
    toast.error(t('memberships.saveError') || t('common.errorLoading'));
  } finally {
    savingPlan.value = false;
  }
};

const deletePlan = async (plan) => {
  if (!confirm(`${t('memberships.deletePlanConfirm') || 'Delete plan?'} ${plan?.name_ar || plan?.name || ''}`)) return;
  try {
    await api.delete(`/memberships/plans/${plan.id}`);
    toast.success(t('common.delete'));
    await loadPlans();
  } catch (e) {
    toast.error(t('common.errorLoading'));
  }
};

const openAssignModal = () => {
  showAssignModal.value = true;
  assignForm.value = { customer_id: '', membership_plan_id: '', duration_months: 1, auto_renew: false };
};

const closeAssignModal = () => {
  showAssignModal.value = false;
  assigning.value = false;
};

const assignMembership = async () => {
  if (!assignForm.value.customer_id || !assignForm.value.membership_plan_id) {
    toast.error(t('memberships.selectCustomerPlan') || 'Select customer and plan');
    return;
  }
  assigning.value = true;
  try {
    await api.post('/memberships', {
      customer_id: Number(assignForm.value.customer_id),
      membership_plan_id: Number(assignForm.value.membership_plan_id),
      duration_months: Number(assignForm.value.duration_months || 1),
      auto_renew: assignForm.value.auto_renew ? 1 : 0,
    });
    toast.success(t('memberships.assigned') || 'Assigned');
    closeAssignModal();
    activeTab.value = 'members';
    await loadMembers();
  } catch (e) {
    console.error('Assign membership error:', e);
    toast.error(t('memberships.assignError') || t('common.errorLoading'));
  } finally {
    assigning.value = false;
  }
};

const openRenewModal = (member) => {
  renewingMember.value = member;
  renewForm.value = { months: 1, amount_paid: 0 };
  showRenewModal.value = true;
};

const closeRenewModal = () => {
  showRenewModal.value = false;
  renewing.value = false;
  renewingMember.value = null;
};

const renewMembership = async () => {
  if (!renewingMember.value?.id) return;
  renewing.value = true;
  try {
    await api.post(`/memberships/${renewingMember.value.id}/renew`, {
      months: Number(renewForm.value.months || 1),
      amount_paid: Number(renewForm.value.amount_paid || 0),
    });
    toast.success(t('memberships.renewed') || 'Renewed');
    closeRenewModal();
    await loadMembers();
  } catch (e) {
    console.error('Renew membership error:', e);
    toast.error(t('memberships.renewError') || t('common.errorLoading'));
  } finally {
    renewing.value = false;
  }
};

const cancelMembership = async (member) => {
  if (!confirm(`${t('memberships.cancelConfirm') || 'Cancel membership?'} ${member?.customer_name || ''}`)) return;
  try {
    await api.delete(`/memberships/${member.id}`);
    toast.success(t('memberships.cancelled') || 'Cancelled');
    await loadMembers();
  } catch (e) {
    toast.error(t('common.errorLoading'));
  }
};

const goToSettings = () => {
  router.push('/programs/settings');
};

watch(
  () => activeTab.value,
  (tab) => {
    if (tab === 'members') {
      loadMembers();
    }
  }
);

onMounted(async () => {
  await Promise.all([loadPlans(), loadCustomers(), loadMembers()]);
});
</script>

<style scoped>
.memberships-page{display:flex;flex-direction:column;gap:1.5rem;}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;}
.tabs-card{border:1px solid var(--border-color);box-shadow:0 2px 8px rgba(0,0,0,0.04);}

.btn-primary-custom{background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.9) 100%);border:none;box-shadow:0 4px 12px rgba(187,160,122,0.3);transition:all 0.3s;}
.btn-primary-custom:hover{background:linear-gradient(135deg, rgba(187,160,122,0.95) 0%, var(--asmaa-primary) 100%);box-shadow:0 6px 16px rgba(187,160,122,0.4);transform:translateY(-2px);}

.filters-card{border:1px solid var(--border-color);box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.search-input-group{position:relative;}
.search-icon-wrapper{background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.8) 100%);color:#fff;border-color:var(--asmaa-primary);}
.filter-input,.filter-select{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);border:1px solid var(--border-color);}
.filter-input:focus,.filter-select:focus{border-color:var(--asmaa-primary);box-shadow:0 0 0 3px rgba(187,160,122,0.15);outline:none;}
.search-input:focus{border-left:none;}
.reset-btn{transition:all 0.3s;}
.reset-btn:hover{background:var(--asmaa-primary);color:#fff;border-color:var(--asmaa-primary);transform:translateY(-1px);}

.table-wrapper{overflow-x:auto;border-radius:12px;border:1px solid var(--border-color);background:var(--bg-primary);}
.memberships-table{margin:0;border-collapse:separate;border-spacing:0;}
.table-header-row{background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%);border-bottom:2px solid var(--asmaa-primary);}
.table-header-row th{padding:1rem 1.25rem;font-weight:700;color:var(--text-primary);text-transform:uppercase;font-size:0.75rem;letter-spacing:0.5px;border-bottom:none;white-space:nowrap;}

.plan-row,.member-row{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);border-bottom:1px solid var(--border-color);}
.plan-row:hover,.member-row:hover{background:linear-gradient(90deg, rgba(187,160,122,0.05) 0%, rgba(187,160,122,0.02) 100%);transform:translateX(4px);box-shadow:0 2px 8px rgba(187,160,122,0.1);}
[dir="rtl"] .plan-row:hover,[dir="rtl"] .member-row:hover{transform:translateX(-4px);}
.plan-row td,.member-row td{padding:1rem 1.25rem;vertical-align:middle;border-bottom:1px solid var(--border-color);}

.unified-amount{display:inline-flex;align-items:center;gap:0.5rem;color:var(--asmaa-primary);font-weight:800;font-size:0.9375rem;padding:0.375rem 0.75rem;border-radius:8px;background:linear-gradient(135deg, rgba(187,160,122,0.1) 0%, rgba(187,160,122,0.05) 100%);}
.unified-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 0.75rem;border-radius:8px;font-size:0.8125rem;font-weight:600;transition:all 0.3s;background:linear-gradient(135deg, rgba(187,160,122,0.15) 0%, rgba(187,160,122,0.1) 100%);color:var(--asmaa-primary);border:1px solid rgba(187,160,122,0.3);justify-content:center;}
.badge-icon{width:14px;height:14px;color:currentColor;}
.badge-rate{background:linear-gradient(135deg, var(--asmaa-primary-soft) 0%, hsla(35, 30%, 61%, 0.10) 100%);color:var(--asmaa-primary);border-color:var(--asmaa-primary-soft-border);}
.badge-bonus{background:linear-gradient(135deg, var(--asmaa-warning-soft) 0%, hsla(38, 92%, 50%, 0.10) 100%);color:var(--asmaa-warning);border-color:var(--asmaa-warning-soft-border);}
.badge-info{background:linear-gradient(135deg, rgba(187,160,122,0.15) 0%, rgba(187,160,122,0.1) 100%);}

.status-active{background:linear-gradient(135deg, var(--asmaa-success-soft) 0%, hsla(142, 71%, 45%, 0.10) 100%);color:var(--asmaa-success);border-color:var(--asmaa-success-soft-border);}
.status-expired{background:linear-gradient(135deg, var(--asmaa-danger-soft) 0%, hsla(0, 84%, 60%, 0.10) 100%);color:var(--asmaa-danger);border-color:var(--asmaa-danger-soft-border);}
.status-inactive{background:linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);color:var(--asmaa-secondary);border-color:var(--asmaa-secondary-soft-border);}
.status-other{background:linear-gradient(135deg, var(--asmaa-secondary-soft) 0%, hsla(218, 13%, 28%, 0.10) 100%);color:var(--asmaa-secondary);border-color:var(--asmaa-secondary-soft-border);}

.actions-group{display:flex;align-items:center;justify-content:center;gap:0.5rem;}
.action-btn{width:36px;height:36px;border:none;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.3s cubic-bezier(0.4,0,0.2,1);background:linear-gradient(135deg,var(--asmaa-primary) 0%, rgba(187,160,122,0.9) 100%);color:#fff;box-shadow:0 2px 6px rgba(187,160,122,0.3);}
.action-btn:hover{background:linear-gradient(135deg, rgba(187,160,122,0.95) 0%, var(--asmaa-primary) 100%);transform:translateY(-2px) scale(1.05);box-shadow:0 4px 12px rgba(187,160,122,0.4);}

.name-cell{display:flex;flex-direction:column;gap:0.25rem;}
.plan-name{font-weight:800;color:var(--text-primary);}
.customer-cell{display:flex;flex-direction:column;gap:0.25rem;min-width:0;}
.customer-name{font-weight:800;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;}
.customer-phone{font-size:0.8125rem;color:var(--asmaa-primary);text-decoration:none;}
.customer-phone:hover{text-decoration:underline;}
.date-stack{display:flex;flex-direction:column;gap:0.15rem;}

@media (max-width:768px){.stats-grid{grid-template-columns:1fr;}.actions-group{flex-direction:column;gap:0.25rem;}.action-btn{width:100%;height:32px;}}
</style>

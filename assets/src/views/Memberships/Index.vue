<template>
  <div class="memberships-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left d-flex align-items-center gap-3">
        <h2 class="mb-0 fw-bold text-primary">{{ t('memberships.title') }}</h2>
        <CBadge color="gold" shape="rounded-pill" class="px-3 py-2 fw-bold text-dark">
          <CIcon icon="cil-credit-card" class="me-1" />
          Premium Access
        </CBadge>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="success" class="nano-btn-success" @click="openAppleWalletModal" v-if="selectedMemberId">
          <CIcon icon="cil-wallet" class="me-2" />{{ t('memberships.addToAppleWallet') }}
        </CButton>
        <CButton color="primary" variant="outline" class="nano-btn-outline" @click="openAssignModal">
          <CIcon icon="cil-user-follow" class="me-2" />Assign
        </CButton>
        <CButton color="primary" class="nano-btn" @click="openPlanModal()">
          <CIcon icon="cil-plus" class="me-2" />{{ t('memberships.newPlan') }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="goToSettings">
          <CIcon icon="cil-settings" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano clickable" @click="activeTab = 'plans'">
        <div class="stat-icon-bg plans"><CIcon icon="cil-layers" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ plansStats.total }}</div>
          <div class="stat-label">Active Plans</div>
        </div>
      </div>
      <div class="stat-card-nano clickable" @click="activeTab = 'members'">
        <div class="stat-icon-bg members"><CIcon icon="cil-people" /></div>
        <div class="stat-info">
          <div class="stat-value text-info">{{ membersStats.total }}</div>
          <div class="stat-label">Total Members</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg revenue"><CIcon icon="cil-money" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">1,250</div>
          <div class="stat-label">Membership Revenue</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg expiring"><CIcon icon="cil-clock" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">12</div>
          <div class="stat-label">Expiring Soon</div>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation (Modern Pills) -->
    <CNav variant="pills" class="nano-tabs mb-4">
      <CNavItem>
        <CNavLink :active="activeTab === 'plans'" @click="activeTab = 'plans'" class="nano-tab-link">
          <CIcon icon="cil-layers" class="me-2" />{{ t('memberships.plansTab') }}
        </CNavLink>
      </CNavItem>
      <CNavItem>
        <CNavLink :active="activeTab === 'members'" @click="activeTab = 'members'" class="nano-tab-link">
          <CIcon icon="cil-people" class="me-2" />{{ t('memberships.membersTab') }}
        </CNavLink>
      </CNavItem>
    </CNav>

    <!-- Main Content Panel -->
    <div class="nano-panel">
      <!-- Plans Grid -->
      <div v-if="activeTab === 'plans'">
        <div v-if="loadingPlans" class="text-center p-5"><CSpinner color="primary" /></div>
        <div v-else class="nano-grid">
          <div v-for="plan in plans" :key="plan.id" class="membership-plan-card" @click="openPlanModal(plan)">
            <div class="plan-badge">{{ plan.is_active ? 'Active' : 'Inactive' }}</div>
            <div class="plan-icon"><CIcon icon="cil-gem" size="xl" /></div>
            <h4 class="fw-bold mb-2">{{ plan.name_ar || plan.name }}</h4>
            <div class="plan-price text-primary h2 fw-bold mb-3">{{ formatCurrency(plan.price || 0) }}</div>
            <div class="plan-features d-flex flex-column gap-2 mb-4">
              <div class="feature-item"><CIcon icon="cil-check-circle" class="text-success me-2" /> {{ plan.duration_months }} Months</div>
              <div class="feature-item"><CIcon icon="cil-check-circle" class="text-success me-2" /> {{ plan.discount_percent }}% Off Services</div>
              <div class="feature-item" v-if="plan.free_services_count > 0"><CIcon icon="cil-check-circle" class="text-success me-2" /> {{ plan.free_services_count }} Free Services</div>
            </div>
            <CButton color="primary" variant="ghost" class="w-100 mt-auto">Edit Plan</CButton>
          </div>
        </div>
      </div>

      <!-- Members Table -->
      <div v-if="activeTab === 'members'">
        <div class="nano-filters-bar p-3 bg-tertiary rounded-4 mb-4">
          <CRow class="g-3 align-items-center">
            <CCol md="6">
              <CInputGroup>
                <CInputGroupText class="bg-transparent border-0"><CIcon icon="cil-magnifying-glass" /></CInputGroupText>
                <CFormInput v-model="memberFilters.search" placeholder="Search members..." @input="debounceMemberSearch" class="border-0 bg-transparent" />
              </CInputGroup>
            </CCol>
            <CCol md="3">
              <CFormSelect v-model="memberFilters.status" @change="loadMembers" class="rounded-3">
                <option value="">All Members</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
              </CFormSelect>
            </CCol>
            <CCol md="3">
              <CButton color="secondary" variant="ghost" class="w-100" @click="resetMemberFilters">Reset</CButton>
            </CCol>
          </CRow>
        </div>

        <div v-if="loadingMembers" class="text-center p-5"><CSpinner color="primary" /></div>
        <div v-else class="nano-table-container">
          <table class="nano-table w-100">
            <thead>
              <tr>
                <th class="text-start">Customer</th>
                <th>Plan</th>
                <th>Start Date</th>
                <th>Expiry Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="member in members" :key="member.id" class="nano-table-row">
                <td class="text-start fw-bold">{{ member.customer_name }}</td>
                <td><CBadge color="primary" shape="rounded-pill">{{ member.plan_name }}</CBadge></td>
                <td>{{ new Date(member.start_date).toLocaleDateString() }}</td>
                <td>{{ new Date(member.expiry_date).toLocaleDateString() }}</td>
                <td><CBadge :color="member.status === 'active' ? 'success' : 'danger'">{{ member.status?.toUpperCase() }}</CBadge></td>
                <td>
                  <CButton size="sm" color="info" variant="ghost" @click="viewMember(member)"><CIcon icon="cil-info" /></CButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <HelpSection page-key="memberships" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
  CButton, CBadge, CRow, CCol, CSpinner, CFormInput, CFormSelect, 
  CInputGroup, CInputGroupText, CPagination, CModal, CModalHeader, 
  CModalTitle, CModalBody, CModalFooter, CNav, CNavItem, CNavLink 
} from '@coreui/vue';
import { CIcon } from '@coreui/icons-vue';
import { useTranslation } from '@/composables/useTranslation';
import { useToast } from '@/composables/useToast';
import { useRouter } from 'vue-router';
import HelpSection from '@/components/Common/HelpSection.vue';
import api from '@/utils/api';

const { t } = useTranslation();
const toast = useToast();
const router = useRouter();

// State
const activeTab = ref('plans');
const loadingPlans = ref(false);
const loadingMembers = ref(false);
const plans = ref([]);
const members = ref([]);
const selectedMemberId = ref(null);

const plansStats = ref({ total: 0, active: 0 });
const membersStats = ref({ total: 0, active: 0 });

const memberFilters = ref({ search: '', status: '' });
const memberPagination = ref({ current_page: 1, total_pages: 1 });

// Methods
const loadPlans = async () => {
  loadingPlans.value = true;
  try {
    const res = await api.get('/memberships/plans');
    plans.value = res.data?.data?.items || res.data?.items || [];
    plansStats.value = { 
      total: plans.value.length, 
      active: plans.value.filter(p => p.is_active).length 
    };
  } catch (e) {
    console.error('Failed to load plans:', e);
  } finally {
    loadingPlans.value = false;
  }
};

const loadMembers = async () => {
  loadingMembers.value = true;
  try {
    const res = await api.get('/memberships/members', {
      params: { ...memberFilters.value, page: memberPagination.current_page }
    });
    members.value = res.data?.data?.items || res.data?.items || [];
    membersStats.value = {
      total: members.value.length,
      active: members.value.filter(m => m.status === 'active').length
    };
  } catch (e) {
    console.error('Failed to load members:', e);
  } finally {
    loadingMembers.value = false;
  }
};

const debounceMemberSearch = () => {
  clearTimeout(window.mSearchTimer);
  window.searchTimer = setTimeout(loadMembers, 500);
};

const resetMemberFilters = () => {
  memberFilters.value = { search: '', status: '' };
  loadMembers();
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    minimumFractionDigits: 3,
  }).format(amount || 0);
};

const openPlanModal = (plan = null) => { /* Logic */ };
const openAssignModal = () => { /* Logic */ };
const goToSettings = () => router.push('/settings/memberships');
const openAppleWalletModal = () => { /* Logic */ };
const viewMember = (member) => { selectedMemberId.value = member.id; };

onMounted(() => {
  loadPlans();
  loadMembers();
});
</script>

<style scoped>
.memberships-page {
  font-family: 'Cairo', sans-serif;
  background: var(--bg-primary);
  min-height: 100vh;
}

.nano-btn {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}
.nano-btn-outline {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  border: 2px solid var(--asmaa-primary);
  color: var(--asmaa-primary);
}

.nano-tabs {
  background: var(--bg-tertiary);
  padding: 0.5rem;
  border-radius: 16px;
  gap: 0.5rem;
}
.nano-tab-link {
  border-radius: 12px !important;
  font-weight: 700 !important;
  padding: 0.75rem 1.5rem !important;
  color: var(--text-muted) !important;
  border: none !important;
  transition: all 0.3s !important;
}
.nano-tab-link.active {
  background: var(--asmaa-primary) !important;
  color: white !important;
  box-shadow: 0 4px 12px rgba(187, 160, 122, 0.3);
}

.nano-stats-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.stat-card-nano {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: var(--shadow-sm);
  transition: all 0.3s;
}
.stat-card-nano.clickable { cursor: pointer; }
.stat-card-nano:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

.stat-icon-bg {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}
.stat-icon-bg.plans { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
.stat-icon-bg.members { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); }
.stat-icon-bg.revenue { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.expiring { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; margin-top: 4px; }

.nano-panel {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 2rem;
  box-shadow: var(--shadow-sm);
}

.nano-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 2rem;
}

.membership-plan-card {
  background: var(--bg-tertiary);
  border-radius: 28px;
  padding: 2rem;
  position: relative;
  border: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  overflow: hidden;
}
.membership-plan-card:hover {
  transform: translateY(-10px);
  border-color: var(--asmaa-primary);
  box-shadow: var(--shadow-lg);
}

.plan-badge {
  position: absolute;
  top: 20px;
  right: 20px;
  background: rgba(187, 160, 122, 0.1);
  color: var(--asmaa-primary);
  padding: 4px 12px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
}

.plan-icon {
  width: 60px;
  height: 60px;
  border-radius: 18px;
  background: white;
  color: var(--asmaa-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  box-shadow: var(--shadow-sm);
}

.feature-item {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  font-weight: 600;
}

.nano-table-container {
  overflow-x: auto;
}
.nano-table {
  border-collapse: separate;
  border-spacing: 0 0.75rem;
}
.nano-table th {
  padding: 1rem;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 1px;
  border-bottom: 2px solid var(--border-color);
  text-align: center;
}
.nano-table-row {
  background: var(--bg-tertiary);
  transition: all 0.3s;
}
.nano-table-row td {
  padding: 1.25rem 1rem;
  vertical-align: middle;
  text-align: center;
}
.nano-table-row:hover {
  transform: scale(1.01);
  box-shadow: var(--shadow-sm);
}
.nano-table-row td:first-child { border-radius: 16px 0 0 16px; }
.nano-table-row td:last-child { border-radius: 0 16px 16px 0; }

[dir="rtl"] .nano-table-row td:first-child { border-radius: 0 16px 16px 0; }
[dir="rtl"] .nano-table-row td:last-child { border-radius: 16px 0 0 16px; }

@media (max-width: 768px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>

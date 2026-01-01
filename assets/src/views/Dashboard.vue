<template>
  <div class="asmaa-salon-dashboard p-4">
    <!-- Modern Hub Header -->
    <div class="dashboard-header-top d-flex justify-content-between align-items-center mb-5">
      <div class="header-left">
        <h1 class="fw-bold text-primary mb-1">{{ t('dashboard.title') }}</h1>
        <p class="text-muted mb-0"><CIcon icon="cil-sun" class="me-2 text-warning" />{{ t('dashboard.subtitle') }} — {{ todayDate }}</p>
      </div>
      <div class="header-right d-flex gap-3">
        <CButton color="primary" class="nano-btn shadow-lg" @click="$router.push('/pos')">
          <CIcon icon="cil-basket" class="me-2" />GO TO POS
        </CButton>
        <CButton color="secondary" variant="ghost" @click="refreshData">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
      </div>
    </div>

    <!-- Main Stats Bar -->
    <div class="nano-stats-bar mb-5">
      <div class="stat-card-nano primary shadow-hover" @click="$router.push('/customers')">
        <div class="stat-icon-bg customers"><CIcon icon="cil-people" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.customers || 0 }}</div>
          <div class="stat-label">Total Customers</div>
        </div>
      </div>
      <div class="stat-card-nano secondary shadow-hover" @click="$router.push('/bookings')">
        <div class="stat-icon-bg bookings"><CIcon icon="cil-calendar" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.bookingsToday || 0 }}</div>
          <div class="stat-label">Today's Bookings</div>
        </div>
      </div>
      <div class="stat-card-nano success shadow-hover">
        <div class="stat-icon-bg revenue"><CIcon icon="cil-money" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ formatCurrencyShort(stats.monthlyRevenue || 0) }}</div>
          <div class="stat-label">Monthly Revenue</div>
        </div>
      </div>
      <div class="stat-card-nano info shadow-hover" @click="$router.push('/loyalty')">
        <div class="stat-icon-bg points"><CIcon icon="cil-star" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.loyaltyPoints || '1.2K' }}</div>
          <div class="stat-label">Loyalty Points</div>
        </div>
      </div>
    </div>

    <!-- Operations Quick Hub -->
    <div class="ops-hub mb-5">
      <h4 class="fw-bold mb-4 d-flex align-items-center"><CIcon icon="cil-speedometer" class="me-2 text-primary" />Quick Operations</h4>
      <div class="ops-grid">
        <div class="ops-card pos" @click="$router.push('/pos')">
          <div class="card-glow"></div>
          <div class="card-icon"><CIcon icon="cil-cart" size="xl" /></div>
          <h5>Point of Sale</h5>
          <p>Process orders & payments</p>
        </div>
        <div class="ops-card bookings" @click="$router.push('/bookings')">
          <div class="card-glow"></div>
          <div class="card-icon"><CIcon icon="cil-calendar" size="xl" /></div>
          <h5>Bookings</h5>
          <p>Manage appointments</p>
        </div>
        <div class="ops-card queue" @click="$router.push('/queue')">
          <div class="card-glow"></div>
          <div class="card-icon"><CIcon icon="cil-list" size="xl" /></div>
          <h5>Queue</h5>
          <p>Live walk-in management</p>
        </div>
        <div class="ops-card staff" @click="$router.push('/worker-calls')">
          <div class="card-glow"></div>
          <div class="card-icon"><CIcon icon="cil-bell" size="xl" /></div>
          <h5>Staff Calls</h5>
          <p>Worker notifications</p>
        </div>
      </div>
    </div>

    <!-- Dashboard Content Grid -->
    <CRow class="g-4">
      <!-- Live Monitoring Column -->
      <CCol lg="8">
        <div class="nano-panel h-100 live-monitor">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Live Monitoring</h4>
            <CBadge color="danger" class="pulse-red">LIVE</CBadge>
          </div>
          
          <CTabs active-tab="today-queue">
            <CNav variant="pills" class="nano-tabs-sm mb-4">
              <CNavItem><CNavLink href="javascript:void(0)" active>Queue Waitlist</CNavLink></CNavItem>
              <CNavItem><CNavLink href="javascript:void(0)">Today's Bookings</CNavLink></CNavItem>
            </CNav>
            <CTabContent>
              <div class="d-flex flex-column gap-3">
                <div v-for="i in [1,2,3]" :key="i" class="live-activity-item p-3 rounded-4 bg-tertiary border d-flex align-items-center">
                  <div class="activity-time me-3 fw-bold text-primary">10:{{ i }}5</div>
                  <div class="activity-info flex-grow-1">
                    <div class="fw-bold">Huda Al-Alawi</div>
                    <div class="small text-muted">Hair Cut & Styling</div>
                  </div>
                  <CBadge color="warning" shape="rounded-pill">WAITING</CBadge>
                </div>
              </div>
            </CTabContent>
          </CTabs>
        </div>
      </CCol>

      <!-- Staff Performance Summary -->
      <CCol lg="4">
        <div class="nano-panel h-100 staff-performance">
          <h4 class="fw-bold mb-4">Top Stylists</h4>
          <div class="d-flex flex-column gap-4">
            <div v-for="s in [1,2,3]" :key="s" class="stylist-item d-flex align-items-center">
              <div class="stylist-rank me-3 fw-bold fs-4 text-muted">#{{ s }}</div>
              <div class="stylist-avatar-sm me-3">S{{ s }}</div>
              <div class="stylist-info flex-grow-1">
                <div class="fw-bold">Stylist Name</div>
                <div class="small text-success">45 Orders • 150 KWD</div>
              </div>
              <div class="stylist-rating text-warning"><CIcon icon="cil-star" class="me-1" />4.9</div>
            </div>
          </div>
          <CButton color="primary" variant="ghost" class="w-100 mt-5" @click="$router.push('/staff')">View All Staff</CButton>
        </div>
      </CCol>
    </CRow>

    <HelpSection page-key="dashboard" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
  CButton, CBadge, CRow, CCol, CSpinner, CNav, CNavItem, CNavLink, 
  CTabContent, CTabs
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
const loading = ref(false);
const stats = ref({
  customers: 0,
  bookingsToday: 0,
  monthlyRevenue: 0,
  activeStaff: 0,
  totalServices: 0,
  pendingOrders: 0
});

const todayDate = computed(() => {
  return new Date().toLocaleDateString('en-KW', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

// Methods
const refreshData = async () => {
  loading.value = true;
  try {
    const res = await api.get('/dashboard/stats');
    stats.value = res.data?.data || res.data;
  } catch (e) {
    console.error('Dashboard error:', e);
  } finally {
    loading.value = false;
  }
};

const formatCurrencyShort = (amount) => {
  return new Intl.NumberFormat('en-KW', {
    style: 'currency',
    currency: 'KWD',
    notation: 'compact',
    minimumFractionDigits: 1
  }).format(amount || 0);
};

onMounted(() => {
  refreshData();
});
</script>

<style scoped>
.asmaa-salon-dashboard {
  font-family: 'Cairo', sans-serif;
  background: var(--bg-primary);
  min-height: 100vh;
}

.nano-btn {
  border-radius: 16px;
  padding: 0.875rem 2rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.nano-btn:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 10px 25px rgba(187, 160, 122, 0.4);
}

.nano-stats-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 2rem;
}

.stat-card-nano {
  background: var(--bg-secondary);
  border-radius: 28px;
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid var(--border-color);
}
.stat-card-nano.shadow-hover:hover {
  transform: translateY(-8px);
  border-color: var(--asmaa-primary);
  box-shadow: var(--shadow-lg);
}

.stat-icon-bg {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
}
.stat-icon-bg.customers { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
.stat-icon-bg.bookings { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); }
.stat-icon-bg.revenue { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.points { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.stat-value { font-size: 2rem; font-weight: 900; line-height: 1; }
.stat-label { font-size: 0.875rem; color: var(--text-muted); font-weight: 700; margin-top: 6px; }

.ops-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.ops-card {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 2rem;
  text-align: center;
  position: relative;
  overflow: hidden;
  border: 1px solid var(--border-color);
  cursor: pointer;
  transition: all 0.3s;
}
.ops-card:hover {
  transform: translateY(-5px);
  border-color: var(--asmaa-primary);
}
.ops-card.pos { border-bottom: 4px solid #6366f1; }
.ops-card.bookings { border-bottom: 4px solid #ec4899; }
.ops-card.queue { border-bottom: 4px solid #10b981; }
.ops-card.staff { border-bottom: 4px solid #f59e0b; }

.card-icon {
  width: 60px;
  height: 60px;
  background: var(--bg-tertiary);
  color: var(--asmaa-primary);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
  transition: all 0.3s;
}
.ops-card:hover .card-icon {
  background: var(--asmaa-primary);
  color: white;
  transform: scale(1.1) rotate(5deg);
}

.ops-card h5 { font-weight: 800; margin-bottom: 0.5rem; }
.ops-card p { font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0; }

.nano-panel {
  background: var(--bg-secondary);
  border-radius: 32px;
  padding: 2.5rem;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
}

.nano-tabs-sm {
  background: var(--bg-tertiary);
  padding: 0.375rem;
  border-radius: 12px;
  gap: 0.25rem;
}
.nano-tabs-sm .nav-link {
  border-radius: 8px !important;
  font-weight: 700 !important;
  padding: 0.5rem 1.25rem !important;
  color: var(--text-muted) !important;
  border: none !important;
}
.nano-tabs-sm .nav-link.active {
  background: var(--asmaa-primary) !important;
  color: white !important;
}

.live-activity-item {
  transition: all 0.3s;
}
.live-activity-item:hover {
  transform: translateX(8px);
  border-color: var(--asmaa-primary) !important;
}

.stylist-avatar-sm {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--asmaa-primary) 0%, #d4b996 100%);
  color: white;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pulse-red {
  animation: pulse-red 2s infinite;
}
@keyframes pulse-red {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(229, 83, 83, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(229, 83, 83, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(229, 83, 83, 0); }
}

@media (max-width: 992px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>

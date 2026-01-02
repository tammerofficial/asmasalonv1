<template>
  <div class="dashboard-page p-4">
    <!-- Modern Nano Header -->
    <div class="pos-header-top d-flex justify-content-between align-items-center mb-4">
      <div class="header-left">
        <h2 class="mb-0 fw-bold text-primary">{{ t('dashboard.title') || 'لوحة التحكم' }}</h2>
        <p class="text-muted small mb-0">{{ todayDate }}</p>
      </div>
      <div class="header-right d-flex gap-2">
        <CButton color="primary" class="nano-btn" @click="$router.push('/pos')">
          <CIcon icon="cil-cart" class="me-2" />{{ t('dashboard.goToPOS') || 'انتقل إلى POS' }}
        </CButton>
        <CButton color="secondary" variant="ghost" @click="refreshData">
          <CIcon icon="cil-reload" :class="{ 'spinning': loading }" />
        </CButton>
      </div>
    </div>

    <!-- Quick Stats Bar (Nano Banana Style) -->
    <div class="nano-stats-bar mb-4">
      <div class="stat-card-nano">
        <div class="stat-icon-bg customers"><CIcon icon="cil-people" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats.customers || 0 }}</div>
          <div class="stat-label">{{ t('dashboard.totalCustomers') || 'إجمالي العملاء' }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg bookings"><CIcon icon="cil-calendar-check" /></div>
        <div class="stat-info">
          <div class="stat-value text-info">{{ stats.bookingsToday || 0 }}</div>
          <div class="stat-label">{{ t('dashboard.todaysBookings') || 'حجوزات اليوم' }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg revenue"><CIcon icon="cil-money" /></div>
        <div class="stat-info">
          <div class="stat-value text-success">{{ formatCurrencyShort(stats.monthlyRevenue || 0) }}</div>
          <div class="stat-label">{{ t('dashboard.monthlyRevenue') || 'إيرادات الشهر' }}</div>
        </div>
      </div>
      <div class="stat-card-nano">
        <div class="stat-icon-bg loyalty"><CIcon icon="cil-star" /></div>
        <div class="stat-info">
          <div class="stat-value text-warning">{{ stats.totalPoints || 0 }}</div>
          <div class="stat-label">{{ t('dashboard.loyaltyPoints') || 'نقاط الولاء' }}</div>
        </div>
      </div>
    </div>

    <!-- Quick Operations Panel -->
    <div class="nano-panel mb-4">
      <h4 class="fw-bold mb-4">{{ t('dashboard.quickOperations') || 'عمليات سريعة' }}</h4>
      <div class="nano-grid-actions">
        <div class="action-card-nano pos" @click="$router.push('/pos')">
          <div class="action-icon"><CIcon icon="cil-cart" size="xl" /></div>
          <div class="action-details">
            <h6>{{ t('nav.pos') || 'نقطة البيع' }}</h6>
            <p>{{ t('dashboard.processOrders') || 'معالجة الطلبات والمدفوعات' }}</p>
          </div>
        </div>
        <div class="action-card-nano bookings" @click="$router.push('/bookings')">
          <div class="action-icon"><CIcon icon="cil-calendar" size="xl" /></div>
          <div class="action-details">
            <h6>{{ t('nav.bookings') || 'الحجوزات' }}</h6>
            <p>{{ t('dashboard.manageAppointments') || 'إدارة المواعيد' }}</p>
          </div>
        </div>
        <div class="action-card-nano queue" @click="$router.push('/queue')">
          <div class="action-icon"><CIcon icon="cil-people" size="xl" /></div>
          <div class="action-details">
            <h6>{{ t('nav.queue') || 'الطابور' }}</h6>
            <p>{{ t('dashboard.liveWaitlist') || 'إدارة قائمة الانتظار' }}</p>
          </div>
        </div>
        <div class="action-card-nano calls" @click="$router.push('/worker-calls')">
          <div class="action-icon"><CIcon icon="cil-bullhorn" size="xl" /></div>
          <div class="action-details">
            <h6>{{ t('nav.workerCalls') || 'نداءات الموظفات' }}</h6>
            <p>{{ t('dashboard.workerNotifications') || 'تنبيهات الموظفات' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Dashboard Content Grid -->
    <CRow class="g-4">
      <!-- Live Monitoring Column -->
      <CCol lg="8">
        <div class="nano-panel h-100 live-monitor">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ t('dashboard.liveMonitoring') || 'المراقبة المباشرة' }}</h4>
            <CBadge color="danger" class="pulse-red">LIVE</CBadge>
          </div>
          
          <div v-if="!stats.recentActivity || stats.recentActivity.length === 0" class="text-center py-5 text-muted">
            <CIcon icon="cil-find-in-page" size="xl" class="mb-3 opacity-25" />
            <p>{{ t('dashboard.noRecentActivity') || 'لا توجد أنشطة حديثة' }}</p>
          </div>
          <div v-else class="d-flex flex-column gap-3">
            <div v-for="(item, index) in stats.recentActivity" :key="index" class="live-activity-item p-3 rounded-4 bg-tertiary border d-flex align-items-center">
              <div class="activity-time me-3 fw-bold text-primary">{{ formatTime(item.created_at) }}</div>
              <div class="activity-info flex-grow-1">
                <div class="fw-bold">{{ item.name || 'عميلة' }}</div>
                <div class="small text-muted">{{ item.type === 'booking' ? 'حجز موعد' : 'طلب جديد' }}</div>
              </div>
              <CBadge :color="item.type === 'booking' ? 'info' : 'success'" shape="rounded-pill">
                {{ item.type === 'booking' ? (t('dashboard.bookings') || 'حجوزات') : (t('dashboard.orders') || 'طلبات') }}
              </CBadge>
            </div>
          </div>
        </div>
      </CCol>

      <!-- Staff Performance Summary -->
      <CCol lg="4">
        <div class="nano-panel h-100 staff-performance">
          <h4 class="fw-bold mb-4">{{ t('dashboard.topStylists') || 'أفضل الموظفات' }}</h4>
          <div v-if="!stats.topStylists || stats.topStylists.length === 0" class="text-center py-5 text-muted">
             <p>{{ t('dashboard.noDataYet') || 'لا توجد بيانات بعد' }}</p>
          </div>
          <div v-else class="d-flex flex-column gap-4">
            <div v-for="(s, idx) in stats.topStylists" :key="idx" class="stylist-item d-flex align-items-center">
              <div class="stylist-rank me-3 fw-bold fs-4 text-muted">#{{ idx + 1 }}</div>
              <div class="stylist-avatar-sm me-3">{{ s.name.charAt(0) }}</div>
              <div class="stylist-info flex-grow-1">
                <div class="fw-bold">{{ s.name }}</div>
                <div class="small text-success">{{ s.bookings }} {{ t('dashboard.bookings') || 'حجوزات' }}</div>
              </div>
            </div>
          </div>
          <CButton color="primary" variant="ghost" class="w-100 mt-5" @click="$router.push('/staff')">
            {{ t('dashboard.viewAllStaff') || 'مشاهدة جميع الموظفات' }}
          </CButton>
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

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleTimeString('en-KW', { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
  refreshData();
});
</script>

<style scoped>
.dashboard-page {
  font-family: 'Cairo', sans-serif;
  background: var(--bg-primary);
  min-height: 100vh;
}

.nano-btn {
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(142, 126, 120, 0.3);
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
.stat-icon-bg.customers { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
.stat-icon-bg.bookings { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
.stat-icon-bg.revenue { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon-bg.loyalty { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; margin-top: 4px; }

.nano-panel {
  background: var(--bg-secondary);
  border-radius: 24px;
  padding: 2rem;
  box-shadow: var(--shadow-sm);
}

.nano-grid-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.action-card-nano {
  background: var(--bg-tertiary);
  border-radius: 20px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid var(--border-color);
}
.action-card-nano:hover {
  transform: scale(1.03);
  border-color: var(--asmaa-primary);
  box-shadow: var(--shadow-md);
}

.action-icon {
  width: 54px;
  height: 54px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  color: var(--asmaa-primary);
  box-shadow: var(--shadow-sm);
}

.action-details h6 { margin-bottom: 2px; font-weight: 800; }
.action-details p { margin-bottom: 0; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }

.pulse-red {
  animation: pulse-red 2s infinite;
}

@keyframes pulse-red {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.live-activity-item {
  transition: all 0.3s;
}
.live-activity-item:hover {
  background: var(--bg-secondary) !important;
  transform: translateX(5px);
}

.stylist-avatar-sm {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--asmaa-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

@media (max-width: 768px) {
  .nano-stats-bar { grid-template-columns: 1fr 1fr; }
}
</style>
